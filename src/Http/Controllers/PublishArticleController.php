<?php

namespace SeoSaas\LaravelSeo\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use SeoSaas\LaravelSeo\Http\Requests\PublishArticleRequest;
use SeoSaas\LaravelSeo\Models\SeoPublishedArticle;
use SeoSaas\LaravelSeo\Services\PublishArticleService;
use Symfony\Component\HttpFoundation\Response;

class PublishArticleController extends Controller
{
    /**
     * Inbound publishing contract endpoint.
     */
    public function publish(PublishArticleRequest $request, PublishArticleService $publishService): JsonResponse
    {
        $payload = $request->validated();

        // Enforce verified store identity from middleware attributes
        if ($verifiedStoreId = $request->attributes->get('rank212_store_id')) {
            $payload['store_id'] = $verifiedStoreId;
        }

        $idempotencyKey = $request->header('X-Rank212-Idempotency-Key')
            ?: ($payload['idempotency_key'] ?? null);

        // Check if publication is explicitly authorized or requested strictly within the verified signed contract
        $shouldPublish = (bool) (
            (!empty($payload['publish']) && filter_var($payload['publish'], FILTER_VALIDATE_BOOLEAN))
            || (($payload['action'] ?? '') === 'publish')
            || config('seo-saas.publishing.auto_publish', false)
        );

        if ($shouldPublish) {
            $result = $publishService->publish($payload, $idempotencyKey);
        } else {
            $result = $publishService->receive($payload, $idempotencyKey);
        }

        if (! $result['success']) {
            if (($result['error_code'] ?? '') === 'IDEMPOTENCY_PAYLOAD_MISMATCH') {
                return response()->json([
                    'success'    => false,
                    'error_code' => $result['error_code'],
                    'message'    => $result['message'],
                ], Response::HTTP_CONFLICT);
            }

            return response()->json([
                'success'    => false,
                'error_code' => $result['error_code'] ?? 'OPERATION_FAILED',
                'message'    => $result['message'] ?? 'Failed to process contract.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $statusCode = !empty($result['idempotent']) ? Response::HTTP_OK : Response::HTTP_CREATED;

        // Clean internal article model reference before JSON response
        unset($result['article']);

        return response()->json($result, $statusCode);
    }

    /**
     * Inbound publishing status query endpoint.
     */
    public function status(Request $request, int $articleId): JsonResponse
    {
        $query = SeoPublishedArticle::where('remote_article_id', $articleId);

        if ($storeId = config('seo-saas.publishing.store_id')) {
            $query->where('store_id', $storeId);
        }

        $article = $query->first();

        if (! $article) {
            return response()->json([
                'success'    => false,
                'error_code' => 'NOT_FOUND',
                'message'    => "Article with remote ID {$articleId} not found.",
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'success'         => true,
            'status'          => $article->status,
            'article_id'      => $article->remote_article_id,
            'local_post_id'   => $article->id,
            'slug'            => $article->slug,
            'published_url'   => $article->published_url,
            'published_at'    => $article->published_at?->toIso8601String(),
            'received_at'     => $article->received_at?->toIso8601String(),
            'idempotency_key' => $article->idempotency_key,
        ], Response::HTTP_OK);
    }
}
