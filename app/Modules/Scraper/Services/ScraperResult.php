<?php

namespace App\Modules\Scraper\Services;

readonly class ScraperResult
{
    public function __construct(
        public int    $total,
        public int    $newCount,
        public int    $updatedCount,
        public int    $failedCount,
        public string $source,
    ) {}
}
