<?php

namespace SeoSaas\LaravelSeo;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use SeoSaas\LaravelSeo\Commands\SyncAllSeoCommand;
use SeoSaas\LaravelSeo\Services\SeoApiClient;
use SeoSaas\LaravelSeo\View\Components\Tags;

class SeoServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/seo-saas.php',
            'seo-saas'
        );

        $this->app->singleton(SeoApiClient::class, function ($app) {
            return new SeoApiClient(
                apiKey: config('seo-saas.api_key'),
                apiUrl: config('seo-saas.api_url')
            );
        });

        $this->app->alias(SeoApiClient::class, 'seo-client');
    }

    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        // 1. Views
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'seo');

        // 2. Publishable Assets
        if ($this->app->runningInConsole()) {
            // Publish Config
            $this->publishes([
                __DIR__ . '/../config/seo-saas.php' => config_path('seo-saas.php'),
            ], 'seo-config');

            // Publish Migrations
            $migrationFileName = 'create_seo_metadata_table.php';
            if (! $this->migrationExists($migrationFileName)) {
                $this->publishes([
                    __DIR__ . "/../database/migrations/{$migrationFileName}.stub" => database_path('migrations/' . date('Y_m_d_His') . "_{$migrationFileName}"),
                ], 'seo-migrations');
            }

            // Publish Views (Optional for custom frontend styling)
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/seo'),
            ], 'seo-views');

            // Register Commands
            $this->commands([
                SyncAllSeoCommand::class,
            ]);
        }

        // 3. Register Blade Directive: @seoTags($model)
        Blade::directive('seoTags', function ($expression) {
            return "<?php echo view('seo::components.tags', ['model' => {$expression}])->render(); ?>";
        });

        // 4. Register Blade Component: <x-seo::tags :model="$product" />
        Blade::component('seo::tags', Tags::class);
        Blade::component('seo-tags', Tags::class);
    }

    /**
     * Check if a migration file already exists.
     */
    protected function migrationExists(string $filename): bool
    {
        $path = database_path('migrations/');
        $files = glob($path . '*_' . $filename);

        return ! empty($files);
    }
}

if (!class_exists('Rank212\LaravelSeo\SeoServiceProvider')) {
    class_alias(SeoServiceProvider::class, 'Rank212\LaravelSeo\SeoServiceProvider');
}
