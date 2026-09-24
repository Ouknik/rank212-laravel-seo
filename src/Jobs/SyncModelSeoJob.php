<?php

namespace SeoSaas\LaravelSeo\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use SeoSaas\LaravelSeo\Exceptions\SeoAuthenticationException;
use SeoSaas\LaravelSeo\Exceptions\SeoQuotaExceededException;
use SeoSaas\LaravelSeo\Services\SeoApiClient;

class SyncModelSeoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Exponential backoff in seconds for transient network errors.
     */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    /**
     * The model instance.
     */
    public Model $model;

    /**
     * Whether to force refresh cache on SaaS API.
     */
    public bool $forceRefresh;

    /**
     * Create a new job instance.
     */
    public function __construct(Model $model, bool $forceRefresh = false)
    {
        $this->model = $model;
        $this->forceRefresh = $forceRefresh;
        $this->onQueue(config('seo-saas.queue', 'default'));
    }

    /**
     * Execute the job.
     */
    public function handle(SeoApiClient $client): void
    {
        try {
            $client->syncModel($this->model, $this->forceRefresh);
        } catch (SeoAuthenticationException $e) {
            // Permanent authentication error: do NOT retry
            Log::error("SyncModelSeoJob cancelled: Invalid API Key. Check SEO_SAAS_API_KEY. " . $e->getMessage());
            $this->fail($e);
        } catch (SeoQuotaExceededException $e) {
            // Quota exhausted: clean deletion to avoid clogging failed_jobs
            Log::warning("SyncModelSeoJob skipped: Store monthly quota reached. Upgrade your plan on Rank212. " . $e->getMessage());
            $this->delete();
        } catch (\Exception $e) {
            Log::error("SyncModelSeoJob transient failure for model " . get_class($this->model) . " #{$this->model->getKey()}: " . $e->getMessage());
            throw $e;
        }
    }
}
