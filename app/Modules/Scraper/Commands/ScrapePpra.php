<?php

namespace App\Modules\Scraper\Commands;

use App\Modules\Alert\Jobs\SendTenderAlerts;
use App\Modules\Scraper\Services\PpraScraperService;
use App\Modules\Tender\Models\Tender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScrapePpra extends Command
{
    protected $signature   = 'scrape:ppra';
    protected $description = 'Scrape active tenders from PPRA ePMS portal';

    public function handle(PpraScraperService $scraper): int
    {
        $this->info('Starting PPRA scrape...');

        $beforeCount = Tender::count();

        $result = $scraper->run();

        $newTenderIds = Tender::where('scraped_at', '>=', now()->subMinutes(30))
            ->pluck('id')
            ->toArray();

        Cache::put('scraper.last_run', now()->toIso8601String(), 86400);
        Cache::put('scraper.total_tenders', Tender::count(), 86400);
        Cache::increment('scraper.today_count', $result->newCount);

        if (! empty($newTenderIds)) {
            SendTenderAlerts::dispatch($newTenderIds);
        }

        $this->info("Scrape complete: {$result->total} found, {$result->newCount} new, {$result->failedCount} failed.");
        Log::info('PPRA scrape complete', (array) $result);

        return self::SUCCESS;
    }
}
