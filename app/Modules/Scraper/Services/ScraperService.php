<?php

namespace App\Modules\Scraper\Services;

use App\Modules\Alert\Jobs\SendTenderAlerts;
use App\Modules\Scraper\Models\DataSource;
use App\Modules\Scraper\Models\ScraperLog;
use App\Modules\Tender\Jobs\SummarizeTender;
use App\Modules\Tender\Models\Tender;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class ScraperService
{
    protected int   $delaySeconds = 2;
    protected int   $maxRetries   = 3;

    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:123.0) Gecko/20100101 Firefox/123.0',
    ];

    abstract public function getSourceSlug(): string;
    abstract public function normalizeTender(array $raw): array;
    abstract protected function fetchListingPage(int $page): array;
    abstract protected function hasMorePages(int $page, array $pageData): bool;

    public function run(): void
    {
        $startedAt  = now();
        $total      = 0;
        $new        = 0;
        $updated    = 0;
        $failed     = 0;
        $newIds     = [];
        $page       = 1;
        $errors     = [];

        $source = DataSource::where('slug', $this->getSourceSlug())->first();
        $log    = ScraperLog::create([
            'source_slug' => $this->getSourceSlug(),
            'started_at'  => $startedAt,
        ]);

        Log::info("[{$this->getSourceSlug()}] Scraper started");

        do {
            try {
                $pageData = $this->fetchListingPage($page);
                $items    = $pageData['tenders'] ?? [];
                $total   += count($items);

                foreach ($items as $raw) {
                    try {
                        $normalized = $this->normalizeTender($raw);
                        $isNew      = $this->upsertTender($normalized);

                        if ($isNew) {
                            $new++;
                            $tender = Tender::where('tender_number', $normalized['tender_number'])
                                ->where('source', $normalized['source'])
                                ->value('id');
                            if ($tender) {
                                $newIds[] = $tender;
                            }
                        } else {
                            $updated++;
                        }
                    } catch (\Throwable $e) {
                        $failed++;
                        $errors[] = $e->getMessage();
                        Log::error("[{$this->getSourceSlug()}] Tender failed", ['error' => $e->getMessage(), 'raw' => $raw]);
                    }

                    $this->rateLimit();
                }

                $hasMore = $this->hasMorePages($page, $pageData);
                $page++;
            } catch (\Throwable $e) {
                Log::error("[{$this->getSourceSlug()}] Page {$page} failed", ['error' => $e->getMessage()]);
                $errors[] = "Page {$page}: " . $e->getMessage();
                break;
            }
        } while ($hasMore);

        $log->update([
            'completed_at' => now(),
            'total_found'  => $total,
            'new_inserted' => $new,
            'updated'      => $updated,
            'failed'       => $failed,
            'error_log'    => $errors ?: null,
        ]);

        if ($source) {
            $source->update([
                'last_scraped_at'       => now(),
                'last_success_at'       => $failed < $total ? now() : $source->last_success_at,
                'total_tenders_scraped' => $source->total_tenders_scraped + $new,
            ]);
        }

        Log::info("[{$this->getSourceSlug()}] Done", compact('total', 'new', 'updated', 'failed'));

        if (! empty($newIds)) {
            SendTenderAlerts::dispatch($newIds);
        }
    }

    protected function upsertTender(array $normalized): bool
    {
        $existing = Tender::where('tender_number', $normalized['tender_number'])
            ->where('source', $normalized['source'] ?? $this->getSourceSlug())
            ->first();

        if ($existing) {
            $existing->update(array_filter($normalized, fn ($v) => $v !== null));
            return false;
        }

        $tender = Tender::create(array_merge($normalized, [
            'source'     => $normalized['source'] ?? $this->getSourceSlug(),
            'scraped_at' => now(),
        ]));

        if (! empty($tender->pdf_urls) || ! empty($tender->description)) {
            SummarizeTender::dispatch($tender->id);
        }

        return true;
    }

    protected function httpGet(string $url, array $query = []): \Illuminate\Http\Client\Response
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->randomUserAgent(),
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])->timeout(30)->get($url, $query);

                if ($response->successful()) {
                    return $response;
                }
            } catch (\Throwable $e) {
                // fall through to retry
            }

            $attempt++;
            if ($attempt < $this->maxRetries) {
                sleep((int) (2 ** $attempt));
            }
        }

        throw new \RuntimeException("Failed to fetch {$url} after {$this->maxRetries} attempts");
    }

    protected function rateLimit(): void
    {
        sleep($this->delaySeconds + rand(1, 3));
    }

    protected function randomUserAgent(): string
    {
        return $this->userAgents[array_rand($this->userAgents)];
    }
}
