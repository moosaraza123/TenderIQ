<?php

namespace App\Modules\Scraper\Services;

use App\Modules\Scraper\Models\DataSource;
use Illuminate\Support\Facades\Log;

class ScraperOrchestratorService
{
    public function runAll(): void
    {
        $sources = DataSource::active()->get();

        foreach ($sources as $source) {
            $this->runSource($source->slug);
        }
    }

    public function runSource(string $slug): void
    {
        $source = DataSource::where('slug', $slug)->where('is_active', true)->first();

        if (! $source) {
            Log::warning("[Orchestrator] Source not found or inactive: {$slug}");
            return;
        }

        $scraperClass = $source->scraper_class;

        if (! class_exists($scraperClass)) {
            Log::error("[Orchestrator] Scraper class not found: {$scraperClass}");
            return;
        }

        try {
            $scraper = app($scraperClass);
            $scraper->run();
        } catch (\Throwable $e) {
            Log::error("[Orchestrator] Failed to run {$slug}: " . $e->getMessage());
        }
    }
}
