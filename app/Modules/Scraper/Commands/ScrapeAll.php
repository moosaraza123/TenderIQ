<?php

namespace App\Modules\Scraper\Commands;

use App\Modules\Scraper\Services\ScraperOrchestratorService;
use Illuminate\Console\Command;

class ScrapeAll extends Command
{
    protected $signature   = 'scrape:all';
    protected $description = 'Run all active scrapers via the orchestrator';

    public function handle(ScraperOrchestratorService $orchestrator): int
    {
        $this->info('Starting all active scrapers...');
        $orchestrator->runAll();
        $this->info('All scrapers completed.');
        return self::SUCCESS;
    }
}
