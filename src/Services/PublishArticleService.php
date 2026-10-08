<?php

namespace SeoSaas\LaravelSeo\Services;

use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use SeoSaas\LaravelSeo\Models\SeoPublishedArticle;

class PublishArticleService
{
    /**
     * Ingest and securely record a publishing contract in 'received' state.
     *
     * @param array $payload
     * @param string|null $idempotencyKey
     * @return array
     * @throws DomainException|InvalidArgumentException
     */
    public function receive(array $payload, ?string $idempotencyKey = null): array
    {
        $storeId = (int) ($payload['store_id'] ?? 0);
        $articleId = (int) ($payload['article_id'] ?? 0);
        $contentHash = (string) ($payload['content_version_hash'] ?? '');

        $this->enforceStoreTenant($storeId, $articleId);

        $idempotencyKey = $this->resolveIdempotencyKey($payload, $storeId, $articleId, $contentHash, $idempotencyKey);

        $existing = $this->findExistingRecord($idempotencyKey, $storeId, $articleId);

        if ($existing) {
            if ($existing->content_version_hash !== $contentHash) {
                return [
                    'success'    => false,
                    'error_code' => 'IDEMPOTENCY_PAYLOAD_MISMATCH',
                    'message'    => 'The idempotency key or remote article ID was previously registered with a different content version hash.',
                    'article'    => null,
                ];
            }

            return [
                'success'         => true,
                'status'          => $existing->status,
                'idempotent'      => true,
                'article_id'      => $existing->remote_article_id,
                'local_post_id'   => $existing->id,
                'slug'            => $existing->slug,
                'published_url'   => $existing->published_url,
                'published_at'    => $existing->published_at?->toIso8601String(),
                'received_at'     => $existing->received_at?->toIso8601String(),
                'idempotency_key' => $existing->idempotency_key,
                'article'         => $existing,
            ];
        }

        $article = DB::transaction(function () use ($payload, $storeId, $articleId, $idempotencyKey, $contentHash) {
            return SeoPublishedArticle::create([
                'store_id'             => $storeId,
                'remote_article_id'    => $articleId,
                'idempotency_key'      => $idempotencyKey,
                'content_version_hash' => $contentHash,
                'slug'                 => (string) ($payload['slug'] ?? ''),
                'title'                => (string) ($payload['title'] ?? ''),
                'h1'                   => (string) ($payload['h1'] ?? ''),
                'content_html'         => (string) ($payload['content_html'] ?? ''),
                'meta_title'           => $payload['seo']['meta_title'] ?? $payload['title'] ?? null,
                'meta_description'     => $payload['seo']['meta_description'] ?? null,
                'primary_keyword'      => $payload['seo']['primary_keyword'] ?? null,
                'secondary_keywords'   => $payload['seo']['secondary_keywords'] ?? [],
                'featured_image_url'   => $payload['featured_image_url'] ?? null,
                'received_at'          => now(),
                'published_at'         => null,
                'published_url'        => null,
                'status'               => SeoPublishedArticle::STATUS_RECEIVED,
            ]);
        });

        return [
            'success'         => true,
            'status'          => SeoPublishedArticle::STATUS_RECEIVED,
            'idempotent'      => false,
            'article_id'      => $article->remote_article_id,
            'local_post_id'   => $article->id,
            'slug'            => $article->slug,
            'published_url'   => null,
            'published_at'    => null,
            'received_at'     => $article->received_at?->toIso8601String(),
            'idempotency_key' => $article->idempotency_key,
            'article'         => $article,
        ];
    }

    /**
     * Publish an article payload or transition an existing received article into 'published' state.
     *
     * @param array|SeoPublishedArticle $contractOrArticle
     * @param string|null $idempotencyKey
     * @return array
     * @throws DomainException|InvalidArgumentException
     */
    public function publish(array|SeoPublishedArticle $contractOrArticle, ?string $idempotencyKey = null): array
    {
        if ($contractOrArticle instanceof SeoPublishedArticle) {
            return $this->publishExistingArticle($contractOrArticle);
        }

        $payload = $contractOrArticle;
        $storeId = (int) ($payload['store_id'] ?? 0);
        $articleId = (int) ($payload['article_id'] ?? 0);
        $contentHash = (string) ($payload['content_version_hash'] ?? '');

        $this->enforceStoreTenant($storeId, $articleId);

        $idempotencyKey = $this->resolveIdempotencyKey($payload, $storeId, $articleId, $contentHash, $idempotencyKey);

        $existing = $this->findExistingRecord($idempotencyKey, $storeId, $articleId);

        if ($existing) {
            if ($existing->content_version_hash !== $contentHash) {
                return [
                    'success'    => false,
                    'error_code' => 'IDEMPOTENCY_PAYLOAD_MISMATCH',
                    'message'    => 'The idempotency key or remote article ID was previously registered with a different content version hash.',
                    'article'    => null,
                ];
            }

            if ($existing->status === SeoPublishedArticle::STATUS_PUBLISHED) {
                return [
                    'success'         => true,
                    'status'          => SeoPublishedArticle::STATUS_PUBLISHED,
                    'idempotent'      => true,
                    'article_id'      => $existing->remote_article_id,
                    'local_post_id'   => $existing->id,
                    'slug'            => $existing->slug,
                    'published_url'   => $existing->published_url,
                    'published_at'    => $existing->published_at?->toIso8601String(),
                    'received_at'     => $existing->received_at?->toIso8601String(),
                    'idempotency_key' => $existing->idempotency_key,
                    'article'         => $existing,
                ];
            }
        }

        // Validate & Sanitize Slug
        $cleanSlug = $this->sanitizeSlug((string) ($payload['slug'] ?? ''));
        $canonicalUrl = $this->generateCanonicalUrl($cleanSlug);

        $publishedAt = !empty($payload['published_at'])
            ? Carbon::parse($payload['published_at'])
            : now();

        $article = DB::transaction(function () use ($existing, $payload, $storeId, $articleId, $idempotencyKey, $contentHash, $cleanSlug, $canonicalUrl, $publishedAt) {
            $data = [
                'store_id'             => $storeId,
                'remote_article_id'    => $articleId,
                'idempotency_key'      => $idempotencyKey,
                'content_version_hash' => $contentHash,
                'slug'                 => $cleanSlug,
                'title'                => (string) ($payload['title'] ?? ''),
                'h1'                   => (string) ($payload['h1'] ?? ''),
                'content_html'         => (string) ($payload['content_html'] ?? ''),
                'meta_title'           => $payload['seo']['meta_title'] ?? $payload['title'] ?? null,
                'meta_description'     => $payload['seo']['meta_description'] ?? null,
                'primary_keyword'      => $payload['seo']['primary_keyword'] ?? null,
                'secondary_keywords'   => $payload['seo']['secondary_keywords'] ?? [],
                'featured_image_url'   => $payload['featured_image_url'] ?? null,
                'received_at'          => $existing?->received_at ?: now(),
                'published_at'         => $publishedAt,
                'published_url'        => $canonicalUrl,
                'status'               => SeoPublishedArticle::STATUS_PUBLISHED,
            ];

            if ($existing) {
                $existing->update($data);
                return $existing->fresh();
            }

            return SeoPublishedArticle::create($data);
        });

        return [
            'success'         => true,
            'status'          => SeoPublishedArticle::STATUS_PUBLISHED,
            'idempotent'      => false,
            'article_id'      => $article->remote_article_id,
            'local_post_id'   => $article->id,
            'slug'            => $article->slug,
            'published_url'   => $article->published_url,
            'published_at'    => $article->published_at?->toIso8601String(),
            'received_at'     => $article->received_at?->toIso8601String(),
            'idempotency_key' => $article->idempotency_key,
            'article'         => $article,
        ];
    }

    /**
     * Transition an existing received article record to published.
     */
    protected function publishExistingArticle(SeoPublishedArticle $article): array
    {
        $this->enforceStoreTenant((int) $article->store_id, (int) $article->remote_article_id);

        $cleanSlug = $this->sanitizeSlug((string) $article->slug);
        $canonicalUrl = $this->generateCanonicalUrl($cleanSlug);
        $publishedAt = $article->published_at ?: now();

        $updatedArticle = DB::transaction(function () use ($article, $cleanSlug, $canonicalUrl, $publishedAt) {
            $article->update([
                'slug'          => $cleanSlug,
                'published_url' => $canonicalUrl,
                'published_at'  => $publishedAt,
                'status'        => SeoPublishedArticle::STATUS_PUBLISHED,
            ]);

            return $article->fresh();
        });

        return [
            'success'         => true,
            'status'          => SeoPublishedArticle::STATUS_PUBLISHED,
            'idempotent'      => false,
            'article_id'      => $updatedArticle->remote_article_id,
            'local_post_id'   => $updatedArticle->id,
            'slug'            => $updatedArticle->slug,
            'published_url'   => $updatedArticle->published_url,
            'published_at'    => $updatedArticle->published_at?->toIso8601String(),
            'received_at'     => $updatedArticle->received_at?->toIso8601String(),
            'idempotency_key' => $updatedArticle->idempotency_key,
            'article'         => $updatedArticle,
        ];
    }

    /**
     * Validate store tenant boundaries.
     */
    protected function enforceStoreTenant(int $storeId, int $articleId): void
    {
        if ($storeId <= 0 || $articleId <= 0) {
            throw new InvalidArgumentException('Store ID and remote Article ID must be positive integers.');
        }

        $configuredStoreId = config('seo-saas.publishing.store_id');
        if ($configuredStoreId !== null && (int) $configuredStoreId !== $storeId) {
            throw new DomainException("Tenant mismatch: Article belongs to Store #{$storeId}, but this integration is configured for Store #{$configuredStoreId}.");
        }
    }

    /**
     * Resolve unique idempotency key.
     */
    protected function resolveIdempotencyKey(array $payload, int $storeId, int $articleId, string $contentHash, ?string $key): string
    {
        return $key
            ?: ($payload['idempotency_key'] ?? null)
            ?: "pub_s{$storeId}_a{$articleId}_" . substr($contentHash, 0, 16);
    }

    /**
     * Find existing record by idempotency key or composite store + remote_article_id.
     */
    protected function findExistingRecord(string $idempotencyKey, int $storeId, int $articleId): ?SeoPublishedArticle
    {
        return SeoPublishedArticle::where('idempotency_key', $idempotencyKey)
            ->orWhere(function ($query) use ($storeId, $articleId) {
                $query->where('store_id', $storeId)
                      ->where('remote_article_id', $articleId);
            })
            ->first();
    }

    /**
     * Validate and sanitize slug to prevent path traversal or malformed routes.
     */
    public function sanitizeSlug(string $slug): string
    {
        $clean = Str::slug($slug);
        if (empty($clean)) {
            throw new InvalidArgumentException("Invalid article slug: '{$slug}' cannot be converted to a URL-safe slug.");
        }

        return $clean;
    }

    /**
     * Generate canonical public URL according to configured route prefix.
     */
    public function generateCanonicalUrl(string $slug): string
    {
        $blogPrefix = trim((string) config('seo-saas.publishing.blog_prefix', 'blog'), '/');
        return url($blogPrefix . '/' . $slug);
    }
}
