<?php

namespace SeoSaas\LaravelSeo\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use SeoSaas\LaravelSeo\Exceptions\SeoAuthenticationException;
use SeoSaas\LaravelSeo\Exceptions\SeoQuotaExceededException;
use SeoSaas\LaravelSeo\Jobs\SyncModelSeoJob;
use SeoSaas\LaravelSeo\Services\SeoApiClient;

class SyncAllSeoCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seo:sync-all
                            {--model=App\\Models\\Product : The Eloquent model class to synchronize}
                            {--force : Force regenerate SEO metadata even if cached}
                            {--queue : Dispatch sync jobs to background queue instead of running synchronously}
                            {--chunk=50 : Number of records to process per batch}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize all products or Eloquent models with the SEO SaaS Platform';

    /**
     * Execute the console command.
     */
    public function handle(SeoApiClient $client): int
    {
        $modelClass = (string) $this->option('model');
        $force = (bool) $this->option('force');
        $shouldQueue = (bool) $this->option('queue');
        $chunkSize = (int) $this->option('chunk');

        if (!class_exists($modelClass)) {
            $this->error("Target model class [{$modelClass}] does not exist.");
            return self::FAILURE;
        }

        /** @var Model $sampleInstance */
        $sampleInstance = new $modelClass();
        if (!method_exists($sampleInstance, 'toSeoPayload')) {
            $this->error("The model [{$modelClass}] must use the [SeoSaas\\LaravelSeo\\Traits\\HasDynamicSeo] trait.");
            return self::FAILURE;
        }

        $totalRecords = $modelClass::count();
        if ($totalRecords === 0) {
            $this->info("No records found for model [{$modelClass}].");
            return self::SUCCESS;
        }

        $this->info("Starting SEO synchronization for [{$totalRecords}] record(s) of [{$modelClass}]...");
        $this->output->newLine();

        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->start();

        $syncedCount = 0;
        $failedCount = 0;

        $modelClass::chunk($chunkSize, function ($items) use ($shouldQueue, $force, $client, $progressBar, &$syncedCount, &$failedCount) {
            foreach ($items as $item) {
                if ($shouldQueue) {
                    SyncModelSeoJob::dispatch($item, $force)->afterCommit();
                    $syncedCount++;
                } else {
                    try {
                        $result = $client->syncModel($item, $force);
                        if ($result) {
                            $syncedCount++;
                        } else {
                            $failedCount++;
                        }
                    } catch (SeoAuthenticationException $e) {
                        $this->output->newLine();
                        $this->error("Authentication Error: Invalid API Key. Please verify your SEO_SAAS_API_KEY.");
                        return false;
                    } catch (SeoQuotaExceededException $e) {
                        $this->output->newLine();
                        $this->error("Monthly quota limit reached for this store on Rank212. Please upgrade your plan.");
                        return false;
                    } catch (\Exception $e) {
                        $failedCount++;
                    }
                    // Gentle delay to avoid hitting LLM API rate limits on bulk sync
                    usleep(500000); // 0.5s delay
                }
                $progressBar->advance();
            }
        });

        $progressBar->finish();
        $this->output->newLine(2);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Target Model', $modelClass],
                ['Total Records', $totalRecords],
                ['Mode', $shouldQueue ? 'Dispatched to Queue' : 'Synchronous'],
                ['Processed / Queued', $syncedCount],
                ['Failed', $failedCount],
            ]
        );

        $this->info('SEO synchronization process completed successfully.');

        return self::SUCCESS;
    }
}
