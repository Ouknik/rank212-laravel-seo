<?php

namespace SeoSaas\LaravelSeo\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SeoMetadata extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'seo_metadata';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'seoable_type',
        'seoable_id',
        'meta_title',
        'meta_description',
        'focus_keywords',
        'schema_json_ld',
        'og_tags',
        'image_alt_text',
        'synced_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'focus_keywords' => 'array',
            'schema_json_ld' => 'array',
            'og_tags' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    /**
     * Get the owning seoable model (Product, Post, etc.).
     */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if valid Schema JSON-LD data exists.
     */
    public function hasSchema(): bool
    {
        return !empty($this->schema_json_ld) && is_array($this->schema_json_ld);
    }

    /**
     * Render the JSON-LD <script> tag for Blade output.
     */
    public function renderSchemaJsonLd(): string
    {
        if (!$this->hasSchema()) {
            return '';
        }

        $json = json_encode(
            $this->schema_json_ld,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        return "<script type=\"application/ld+json\">\n{$json}\n</script>";
    }
}
