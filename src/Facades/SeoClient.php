<?php

namespace SeoSaas\LaravelSeo\Facades;

use Illuminate\Support\Facades\Facade;
use SeoSaas\LaravelSeo\Services\SeoApiClient;

/**
 * @method static array optimizeProduct(array $payload, bool $forceRefresh = false)
 * @method static array|null fetchSeoMetadata(string $externalProductId)
 * @method static array|null checkUsage()
 * @method static \SeoSaas\LaravelSeo\Models\SeoMetadata|null syncModel(\Illuminate\Database\Eloquent\Model $model, bool $forceRefresh = false)
 *
 * @see \SeoSaas\LaravelSeo\Services\SeoApiClient
 */
class SeoClient extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return SeoApiClient::class;
    }
}
