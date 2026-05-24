<?php

namespace App\Modules\Scraper\Commands;

use App\Modules\Scraper\Services\ScraperOrchestratorService;
use Illuminate\Console\Command;

class ScrapeSource extends Command
{
    protected $signature   = 'scrape:source {slug : The data source slug to scrape}';
    protected $description = 'Run a single scraper by its source slug';

    public function handle(ScraperOrchestratorService $orchestrator): int
    {
        $slug = $this->argument('slug');
        $this->info("Starting scraper for source: {$slug}");

        try {
            $orchestrator->runSource($slug);
            $this->info("Scraper completed for: {$slug}");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Scraper failed for {$slug}: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
