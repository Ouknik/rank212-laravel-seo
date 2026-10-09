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

    public function getReadingTimeMinutes(): int
    {
        $wordCount = str_word_count(strip_tags($this->content_html ?: ''));
        return max(1, (int) ceil($wordCount / 200));
    }

    public function getTableOfContents(): array
    {
        if (empty($this->content_html)) {
            return [];
        }

        $toc = [];
        if (preg_match_all('/<h2[^>]*>(.*?)<\/h2>/is', $this->content_html, $matches)) {
            foreach ($matches[1] as $idx => $content) {
                $title = trim(strip_tags($content));
                if (!empty($title)) {
                    $slug = Str::slug($title);
                    $toc[] = [
                        'id'    => 'section-' . ($idx + 1) . '-' . $slug,
                        'title' => $title,
                    ];
                }
            }
        }

        return $toc;
    }

    public function getRenderedContentHtml(): string
    {
        if (empty($this->content_html)) {
            return '';
        }

        $idx = 0;
        return preg_replace_callback('/<h2([^>]*)>(.*?)<\/h2>/is', function ($matches) use (&$idx) {
            $idx++;
            $attributes = $matches[1];
            $content = $matches[2];
            $title = trim(strip_tags($content));
            $id = 'section-' . $idx . '-' . Str::slug($title);

            if (str_contains($attributes, 'id=')) {
                return "<h2{$attributes}>{$content}</h2>";
            }

            return "<h2 id=\"{$id}\"{$attributes}>{$content}</h2>";
        }, $this->content_html);
    }

    public function getSeoSchemaJson(): array
    {
        $canonicalUrl = $this->published_url ?: url('/blog/' . $this->slug);
        $siteName = config('app.name', 'Boutique');

        $schema = [
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id'   => $canonicalUrl,
            ],
            'headline'         => $this->h1 ?: $this->title,
            'description'      => $this->getSeoMetaDescription(),
            'datePublished'    => $this->published_at?->toIso8601String(),
            'dateModified'     => ($this->updated_at ?? $this->published_at)?->toIso8601String(),
            'author'           => [
                '@type' => 'Organization',
                'name'  => $siteName,
                'url'   => url('/'),
            ],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => $siteName,
                'url'   => url('/'),
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
