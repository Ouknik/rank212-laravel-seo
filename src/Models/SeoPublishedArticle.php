<?php

namespace SeoSaas\LaravelSeo\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class SeoPublishedArticle extends Model
{
    use HasFactory;

    // Explicit Status Constants
    public const STATUS_RECEIVED  = 'received';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_PUBLISHED = 'published';

    protected $table = 'seo_published_articles';

    protected $fillable = [
        'store_id',
        'remote_article_id',
        'idempotency_key',
        'content_version_hash',
        'slug',
        'title',
        'h1',
        'content_html',
        'meta_title',
        'meta_description',
        'primary_keyword',
        'secondary_keywords',
        'featured_image_url',
        'received_at',
        'published_at',
        'published_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'store_id'           => 'integer',
            'remote_article_id'  => 'integer',
            'secondary_keywords' => 'array',
            'received_at'        => 'datetime',
            'published_at'       => 'datetime',
        ];
    }

    /**
     * Check if article has actually been published live.
     */
    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && !empty($this->published_url);
    }

    /**
     * Scope to published articles only.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    /**
     * Scope to a specific authenticated store tenant.
     */
    public function scopeForStore($query, int $storeId)
    {
        return $query->where('store_id', $storeId);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // SEO & METADATA GETTERS
    // ──────────────────────────────────────────────────────────────────────────

    public function getSeoMetaTitle(): string
    {
        return $this->meta_title ?: $this->title;
    }

    public function getSeoMetaDescription(): string
    {
        return $this->meta_description ?: Str::limit(strip_tags($this->content_html), 160);
    }

    public function getSeoFocusKeywords(): array
    {
        return array_values(array_filter([
            $this->primary_keyword,
            ...($this->secondary_keywords ?? []),
        ]));
    }

    public function getSeoOgTags(): array
    {
        return [
            'title'       => $this->getSeoMetaTitle(),
            'description' => $this->getSeoMetaDescription(),
            'image_url'   => $this->featured_image_url,
            'type'        => 'article',
        ];
    }

    public function getSeoSchemaJson(): array
    {
        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'headline'         => $this->h1 ?: $this->title,
            'description'      => $this->getSeoMetaDescription(),
            'datePublished'    => $this->published_at?->toIso8601String(),
            'dateModified'     => ($this->updated_at ?? $this->published_at)?->toIso8601String(),
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $this->published_url ?: url('/blog/' . $this->slug),
            ],
        ];

        if (!empty($this->featured_image_url)) {
            $schema['image'] = [$this->featured_image_url];
        }

        $keywords = $this->getSeoFocusKeywords();
        if (!empty($keywords)) {
            $schema['keywords'] = implode(', ', $keywords);
        }

        return $schema;
    }
}
