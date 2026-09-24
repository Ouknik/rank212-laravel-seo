<?php

namespace SeoSaas\LaravelSeo\Traits;

use Illuminate\Database\Eloquent\Relations\MorphOne;
use SeoSaas\LaravelSeo\Jobs\SyncModelSeoJob;
use SeoSaas\LaravelSeo\Models\SeoMetadata;
use SeoSaas\LaravelSeo\Services\SeoApiClient;

trait HasDynamicSeo
{
    /**
     * Boot the dynamic SEO trait and register model lifecycle hooks.
     */
    public static function bootHasDynamicSeo(): void
    {
        static::saved(function ($model): void {
            if (config('seo-saas.auto_sync', true) && !app()->runningInConsole()) {
                $model->syncSeoAsync();
            }
        });
    }

    /**
     * Get the polymorphic SEO metadata record.
     */
    public function seoMetadata(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    /**
     * External Product ID used by the SaaS platform.
     */
    public function getSeoExternalId(): string
    {
        return (string) $this->getKey();
    }

    /**
     * Product / Item title.
     */
    public function getSeoTitle(): string
    {
        return (string) ($this->name ?? $this->title ?? '');
    }

    /**
     * Product / Item description.
     */
    public function getSeoDescription(): ?string
    {
        return $this->description ?? $this->content ?? $this->summary ?? null;
    }

    /**
     * Product price (if applicable).
     */
    public function getSeoPrice(): ?float
    {
        return isset($this->price) ? (float) $this->price : null;
    }

    /**
     * Product currency (defaults to config).
     */
    public function getSeoCurrency(): string
    {
        return (string) ($this->currency ?? config('seo-saas.default_currency', 'MAD'));
    }

    /**
     * In-stock availability status.
     */
    public function getSeoInStock(): bool
    {
        if (isset($this->in_stock)) {
            return (bool) $this->in_stock;
        }

        if (isset($this->stock_quantity)) {
            return (int) $this->stock_quantity > 0;
        }

        return true;
    }

    /**
     * Product image URL.
     */
    public function getSeoImageUrl(): ?string
    {
        return $this->image_url ?? $this->image ?? $this->thumbnail_url ?? null;
    }

    /**
     * Product category name.
     */
    public function getSeoCategory(): ?string
    {
        if (isset($this->category)) {
            return is_object($this->category) ? ($this->category->name ?? null) : (string) $this->category;
        }

        return null;
    }

    /**
     * Target language code (e.g., 'ar', 'fr', 'en').
     */
    public function getSeoLanguage(): string
    {
        return (string) ($this->language ?? app()->getLocale() ?? config('seo-saas.default_language', 'ar'));
    }

    /**
     * Build the standard payload for the SaaS API.
     */
    public function toSeoPayload(): array
    {
        return [
            'external_product_id' => $this->getSeoExternalId(),
            'title' => $this->getSeoTitle(),
            'description' => $this->getSeoDescription(),
            'price' => $this->getSeoPrice(),
            'currency' => $this->getSeoCurrency(),
            'in_stock' => $this->getSeoInStock(),
            'image_url' => $this->getSeoImageUrl(),
            'category' => $this->getSeoCategory(),
            'language' => $this->getSeoLanguage(),
        ];
    }

    /**
     * Synchronize SEO metadata synchronously right now.
     */
    public function syncSeoNow(bool $forceRefresh = false): ?SeoMetadata
    {
        /** @var SeoApiClient $client */
        $client = app(SeoApiClient::class);
        try {
            return $client->syncModel($this, $forceRefresh);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("HasDynamicSeo: syncSeoNow failed for " . get_class($this) . " #{$this->getKey()}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Dispatch an asynchronous background SEO sync job after DB commit.
     */
    public function syncSeoAsync(bool $forceRefresh = false): void
    {
        SyncModelSeoJob::dispatch($this, $forceRefresh)->afterCommit();
    }

    /**
     * Get the optimized meta title or fall back to the raw title.
     */
    public function getSeoMetaTitle(): string
    {
        return $this->seoMetadata?->meta_title ?: $this->getSeoTitle();
    }

    /**
     * Get the optimized meta description or fall back to raw description.
     */
    public function getSeoMetaDescription(): string
    {
        return $this->seoMetadata?->meta_description ?: ($this->getSeoDescription() ?? '');
    }

    /**
     * Get focus keywords array.
     */
    public function getSeoFocusKeywords(): array
    {
        return $this->seoMetadata?->focus_keywords ?? [];
    }

    /**
     * Get Schema.org JSON-LD structured data.
     */
    public function getSeoSchemaJson(): ?array
    {
        return $this->seoMetadata?->schema_json_ld;
    }

    /**
     * Get OpenGraph metadata tags.
     */
    public function getSeoOgTags(): array
    {
        return $this->seoMetadata?->og_tags ?? [
            'title' => $this->getSeoMetaTitle(),
            'description' => $this->getSeoMetaDescription(),
            'image_url' => $this->getSeoImageUrl(),
            'type' => 'product',
        ];
    }

    /**
     * Generate Yoast-style SEO Quality score & recommendations.
     */
    public function getSeoAnalysis(): array
    {
        $title = $this->getSeoMetaTitle();
        $description = $this->getSeoMetaDescription();
        $keywords = $this->getSeoFocusKeywords();
        $primaryKeyword = !empty($keywords) ? mb_strtolower(trim($keywords[0])) : '';

        $checks = [];
        $score = 100;

        // Title length
        $tLen = mb_strlen($title);
        if ($tLen >= 45 && $tLen <= 65) {
            $checks[] = ['status' => 'good', 'title' => 'Title Length', 'details' => "Length is {$tLen} chars (ideal 45-65)."];
        } elseif ($tLen > 0) {
            $score -= 10;
            $checks[] = ['status' => 'ok', 'title' => 'Title Length', 'details' => "Length is {$tLen} chars. Recommended 50-60."];
        } else {
            $score -= 25;
            $checks[] = ['status' => 'bad', 'title' => 'Title Length', 'details' => 'Meta title is missing.'];
        }

        // Keyphrase in title
        if (!empty($primaryKeyword)) {
            if (mb_stripos($title, $primaryKeyword) !== false) {
                $checks[] = ['status' => 'good', 'title' => 'Keyphrase in title', 'details' => "Primary keyphrase found in title."];
            } else {
                $score -= 15;
                $checks[] = ['status' => 'bad', 'title' => 'Keyphrase in title', 'details' => "Keyphrase '{$primaryKeyword}' missing from title."];
            }
        }

        // Description length
        $dLen = mb_strlen($description);
        if ($dLen >= 110 && $dLen <= 160) {
            $checks[] = ['status' => 'good', 'title' => 'Description Length', 'details' => "Length is {$dLen} chars (ideal 120-155)."];
        } elseif ($dLen > 0) {
            $score -= 10;
            $checks[] = ['status' => 'ok', 'title' => 'Description Length', 'details' => "Length is {$dLen} chars."];
        } else {
            $score -= 20;
            $checks[] = ['status' => 'bad', 'title' => 'Description Length', 'details' => 'Meta description is missing.'];
        }

        $finalScore = max(0, min(100, $score));
        $status = $finalScore >= 80 ? 'good' : ($finalScore >= 50 ? 'ok' : 'bad');

        return [
            'score' => $finalScore,
            'status' => $status,
            'checks' => $checks,
        ];
    }
}

if (!class_exists('Rank212\LaravelSeo\Traits\HasDynamicSeo')) {
    class_alias(HasDynamicSeo::class, 'Rank212\LaravelSeo\Traits\HasDynamicSeo');
}
