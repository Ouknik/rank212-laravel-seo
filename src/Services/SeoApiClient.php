<?php

namespace SeoSaas\LaravelSeo\Services;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SeoSaas\LaravelSeo\Exceptions\SeoApiException;
use SeoSaas\LaravelSeo\Exceptions\SeoAuthenticationException;
use SeoSaas\LaravelSeo\Exceptions\SeoQuotaExceededException;
use SeoSaas\LaravelSeo\Models\SeoMetadata;

class SeoApiClient
{
    protected string $apiKey;
    protected string $apiUrl;
    protected int $timeout;
    protected int $retryTimes;
    protected int $retrySleepMs;

    public function __construct(?string $apiKey = null, ?string $apiUrl = null)
    {
        $this->apiKey = $apiKey ?: (string) config('seo-saas.api_key', '');
        $this->apiUrl = rtrim($apiUrl ?: (string) config('seo-saas.api_url', 'http://localhost:8000'), '/');
        $this->timeout = (int) config('seo-saas.http.timeout', 10);
        $this->retryTimes = (int) config('seo-saas.http.retry_times', 2);
        $this->retrySleepMs = (int) config('seo-saas.http.retry_sleep_ms', 500);
    }

    /**
     * Create an authenticated HTTP request client.
     */
    protected function client()
    {
        return Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout)
          ->retry($this->retryTimes, $this->retrySleepMs, function (Exception $exception) {
              if ($exception instanceof RequestException && $exception->response) {
                  $status = $exception->response->status();
                  // Do NOT retry permanent client, auth, or quota limit errors
                  if (in_array($status, [400, 401, 402, 403, 422, 429])) {
                      return false;
                  }
              }
              Log::warning("SeoApiClient transient failure, retrying: " . $exception->getMessage());
              return true;
          }, throw: false);
    }

    /**
     * Send product payload to SaaS API for optimization.
     *
     * @param array $payload
     * @param bool $forceRefresh
     * @return array
     * @throws SeoApiException
     */
    public function optimizeProduct(array $payload, bool $forceRefresh = false): array
    {
        if (empty($this->apiKey)) {
            throw new SeoAuthenticationException('Rank212 / SeoSaas: Missing API Key. Set SEO_SAAS_API_KEY in your .env file.');
        }

        $payload['force_refresh'] = $forceRefresh;

        $response = $this->client()->post("{$this->apiUrl}/api/v1/optimize/product", $payload);

        if ($response->failed()) {
            $status = $response->status();
            $errorMsg = $response->json('message') ?? "HTTP {$status} - {$response->body()}";
            Log::error("SeoApiClient: Failed to optimize product. Error: {$errorMsg}");

            if ($status === 401 || $status === 403) {
                throw new SeoAuthenticationException("Authentication Error ({$status}): {$errorMsg}", $status, $status);
            }

            if ($status === 429 || $status === 402 || str_contains(strtolower($errorMsg), 'quota')) {
                throw new SeoQuotaExceededException("Quota Limit Error ({$status}): {$errorMsg}", $status, $status);
            }

            throw new SeoApiException("SEO SaaS API Error ({$status}): {$errorMsg}", $status, $status);
        }

        return $response->json('data') ?? [];
    }

    /**
     * Retrieve existing cached SEO metadata from the SaaS API.
     *
     * @param string $externalProductId
     * @return array|null
     */
    public function fetchSeoMetadata(string $externalProductId): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $response = $this->client()->get("{$this->apiUrl}/api/v1/optimizations/{$externalProductId}");

        if ($response->successful()) {
            return $response->json('data');
        }

        return null;
    }

    /**
     * Check connected store quota and usage metrics.
     *
     * @return array|null
     */
    public function checkUsage(): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $response = $this->client()->get("{$this->apiUrl}/api/v1/store/usage");

        if ($response->successful()) {
            return $response->json('data');
        }

        return null;
    }

    /**
     * Synchronize and store local SEO metadata for an Eloquent model.
     *
     * @param Model $model
     * @param bool $forceRefresh
     * @return SeoMetadata|null
     */
    public function syncModel(Model $model, bool $forceRefresh = false): ?SeoMetadata
    {
        if (!method_exists($model, 'toSeoPayload')) {
            Log::warning("SeoApiClient: Model " . get_class($model) . " does not use HasDynamicSeo trait.");
            return null;
        }

        $payload = $model->toSeoPayload();

        $apiResult = $this->optimizeProduct($payload, $forceRefresh);
        $seoData = $apiResult['seo'] ?? [];

        $metadata = $model->seoMetadata()->updateOrCreate(
            [
                'seoable_type' => get_class($model),
                'seoable_id' => $model->getKey(),
            ],
            [
                'meta_title' => $seoData['meta_title'] ?? null,
                'meta_description' => $seoData['meta_description'] ?? null,
                'focus_keywords' => $seoData['focus_keywords'] ?? [],
                'schema_json_ld' => $seoData['schema_json_ld'] ?? [],
                'og_tags' => $seoData['og_tags'] ?? [],
                'image_alt_text' => $seoData['image_alt_text'] ?? null,
                'synced_at' => now(),
            ]
        );

        return $metadata;
    }
}
