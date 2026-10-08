<?php

namespace SeoSaas\LaravelSeo;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use SeoSaas\LaravelSeo\Commands\SyncAllSeoCommand;
use SeoSaas\LaravelSeo\Services\SeoApiClient;
use SeoSaas\LaravelSeo\View\Components\Tags;

if (!class_exists(SeoServiceProvider::class, false)) {

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

        $this->app->singleton(\SeoSaas\LaravelSeo\Services\PublishArticleService::class);
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
            $metadataMigration = 'create_seo_metadata_table.php';
            if (! $this->migrationExists($metadataMigration)) {
                $this->publishes([
                    __DIR__ . "/../database/migrations/{$metadataMigration}.stub" => database_path('migrations/' . date('Y_m_d_His') . "_{$metadataMigration}"),
                ], 'seo-migrations');
            }

            $articlesMigration = 'create_seo_published_articles_table.php';
            if (! $this->migrationExists($articlesMigration)) {
                $this->publishes([
                    __DIR__ . "/../database/migrations/{$articlesMigration}.stub" => database_path('migrations/' . date('Y_m_d_His', time() + 1) . "_{$articlesMigration}"),
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

        // 3. Register Publishing Contract Routes
        $this->registerPublishingRoutes();

        // 4. Register Public Blog Reading Routes
        $this->registerBlogRoutes();

        // 5. Register Blade Directive: @seoTags($model)
        Blade::directive('seoTags', function ($expression) {
            return "<?php echo view('seo::components.tags', ['model' => {$expression}])->render(); ?>";
        });

        // 6. Register Blade Component: <x-seo::tags :model="$product" />
        Blade::component('seo::tags', Tags::class);
        Blade::component('seo-tags', Tags::class);
    }

    /**
     * Register inbound publishing contract endpoints.
     */
    protected function registerPublishingRoutes(): void
    {
        $prefix = (string) config('seo-saas.publishing.route_prefix', 'api/rank212');

        $this->app['router']->group([
            'prefix'     => $prefix,
            'middleware' => [\SeoSaas\LaravelSeo\Http\Middleware\VerifyPublishSignature::class],
        ], function ($router) {
            $router->post('/articles/publish', [\SeoSaas\LaravelSeo\Http\Controllers\PublishArticleController::class, 'publish'])
                   ->name('rank212.articles.publish');

            $router->get('/articles/{article_id}/status', [\SeoSaas\LaravelSeo\Http\Controllers\PublishArticleController::class, 'status'])
                   ->name('rank212.articles.status');
        });
    }

    /**
     * Register public blog reading and sitemap endpoints.
     */
    protected function registerBlogRoutes(): void
    {
        $blogPrefix = trim((string) config('seo-saas.publishing.blog_prefix', 'blog'), '/');

        $this->app['router']->group([
            'middleware' => ['web'],
        ], function ($router) use ($blogPrefix) {
            $router->get("/{$blogPrefix}", [\SeoSaas\LaravelSeo\Http\Controllers\BlogController::class, 'index'])
                   ->name('seo.blog.index');

            $router->get("/{$blogPrefix}/sitemap.xml", [\SeoSaas\LaravelSeo\Http\Controllers\BlogController::class, 'sitemap'])
                   ->name('seo.blog.sitemap');

            $router->get("/{$blogPrefix}/{slug}", [\SeoSaas\LaravelSeo\Http\Controllers\BlogController::class, 'show'])
                   ->name('seo.blog.show');
        });
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

}

if (!class_exists('Rank212\LaravelSeo\SeoServiceProvider', false)) {
    class_alias(SeoServiceProvider::class, 'Rank212\LaravelSeo\SeoServiceProvider');
}
