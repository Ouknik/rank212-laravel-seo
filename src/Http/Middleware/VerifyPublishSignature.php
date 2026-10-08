<?php

namespace SeoSaas\LaravelSeo\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyPublishSignature
{
    /**
     * Handle an incoming publishing contract request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Explicit Non-Publishing Guard: disabled by default
        if (! config('seo-saas.publishing.enabled', false)) {
            return response()->json([
                'success'    => false,
                'error_code' => 'PUBLISHING_DISABLED',
                'message'    => 'Publishing integration endpoint is disabled in this environment.',
            ], Response::HTTP_FORBIDDEN);
        }

        // 2. Secret check: strict fail-closed behavior, never falls back to API key
        $secret = config('seo-saas.publishing.secret');
        if (empty($secret)) {
            return response()->json([
                'success'    => false,
                'error_code' => 'MISCONFIGURED_SECRET',
                'message'    => 'Dedicated publishing secret is not configured on the client integration.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 3. Header check
        $signatureHeader = $request->header('X-Rank212-Signature');
        if (empty($signatureHeader)) {
            return response()->json([
                'success'    => false,
                'error_code' => 'MISSING_SIGNATURE',
                'message'    => 'Unauthorized: Missing X-Rank212-Signature header.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 4. Parse t={timestamp},v1={hash}
        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            $kv = explode('=', trim($pair), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }

        if (! isset($parts['t']) || ! isset($parts['v1']) || empty($parts['t']) || empty($parts['v1'])) {
            return response()->json([
                'success'    => false,
                'error_code' => 'MALFORMED_SIGNATURE',
                'message'    => 'Unauthorized: Malformed signature header. Expected t={timestamp},v1={hash}.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $timestamp = (int) $parts['t'];
        $receivedSignature = $parts['v1'];

        // 5. Timestamp replay check
        $tolerance = (int) config('seo-saas.publishing.tolerance_seconds', 300);
        $currentTime = time();
        if (abs($currentTime - $timestamp) > $tolerance) {
            return response()->json([
                'success'    => false,
                'error_code' => 'EXPIRED_SIGNATURE',
                'message'    => 'Unauthorized: Request timestamp expired or outside tolerance window.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 6. Verify HMAC-SHA256 signature against exact raw body bytes
        $rawPayload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', "{$timestamp}.{$rawPayload}", $secret);

        if (! hash_equals($expectedSignature, $receivedSignature)) {
            return response()->json([
                'success'    => false,
                'error_code' => 'INVALID_SIGNATURE',
                'message'    => 'Unauthorized: Cryptographic signature mismatch.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // 7. Store / Tenant identity verification
        $configuredStoreId = config('seo-saas.publishing.store_id');
        $headerStoreId = $request->header('X-Rank212-Store-Id') ? (int) $request->header('X-Rank212-Store-Id') : null;
        $payloadStoreId = $request->isJson() && $request->json('store_id') !== null
            ? (int) $request->json('store_id')
            : ($request->input('store_id') ? (int) $request->input('store_id') : null);

        // Disallow contradictory store IDs between header and body
        if ($headerStoreId !== null && $payloadStoreId !== null && $headerStoreId !== $payloadStoreId) {
            return response()->json([
                'success'    => false,
                'error_code' => 'STORE_ID_MISMATCH',
                'message'    => 'Store ID in header does not match payload store_id.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $incomingStoreId = $payloadStoreId ?? $headerStoreId;

        // If this installation is bound to a specific store_id, reject unauthorized foreign store IDs
        if ($configuredStoreId !== null && $incomingStoreId !== null && (int)$configuredStoreId !== $incomingStoreId) {
            return response()->json([
                'success'    => false,
                'error_code' => 'TENANT_MISMATCH',
                'message'    => 'Unauthorized: Incoming store ID does not match this installation\'s configured store identity.',
            ], Response::HTTP_FORBIDDEN);
        }

        $resolvedStoreId = $configuredStoreId ? (int)$configuredStoreId : $incomingStoreId;

        $request->attributes->set('rank212_verified', true);
        $request->attributes->set('rank212_timestamp', $timestamp);
        $request->attributes->set('rank212_store_id', $resolvedStoreId);

        return $next($request);
    }
}
