<?php

namespace App\Jobs;

use App\Models\DataSource;
use App\Services\DataSource\NewsApiAIService;
use App\Services\DataSource\NewsAPIService;
use App\Services\DataSource\TheGuardianService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class AggregateData implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(private readonly DataSource $dataSource)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $dataSourceProvider = match ($this->dataSource->identifier) {
            'news-api-ai' => new NewsApiAIService,
            'the-guardian' => new TheGuardianService,
            'news-api' => new NewsAPIService,
            default => null,
        };

        if (! $dataSourceProvider) {
            Log::info("Data source provider not found for {$this->dataSource->identifier}");
        }

        $dataSourceProvider->processNews();
    }
}
