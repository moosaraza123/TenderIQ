<?php

namespace App\Modules\Scraper\Services\Sources\UK;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class UkFindTenderScraper extends ScraperService
{
    private const API_BASE   = 'https://www.find-tender.service.gov.uk/api/1.0/ocdsReleasePackages';
    private const SOURCE_URL = 'https://www.find-tender.service.gov.uk';
    private const PAGE_SIZE  = 100;

    private ?string $nextCursor = null;

    public function getSourceSlug(): string
    {
        return 'uk_fts';
    }

    protected function fetchListingPage(int $page): array
    {
        $query = ['limit' => self::PAGE_SIZE];

        if ($page === 1) {
            // API requires ISO datetime format for updatedFrom
            $query['updatedFrom'] = now()->subDays(7)->format('Y-m-d\TH:i:s');
            $query['stages']      = 'tender';
        } elseif ($this->nextCursor) {
            $query['cursor'] = $this->nextCursor;
        } else {
            return ['tenders' => [], 'has_more' => false];
        }

        $response = $this->httpGetJson(self::API_BASE, $query);

        if (! $response->successful()) {
            return ['tenders' => [], 'has_more' => false];
        }

        $body = $response->json();

        // Extract next cursor from links
        $nextLink = $body['links']['next'] ?? null;
        if ($nextLink && preg_match('/cursor=([^&]+)/', $nextLink, $m)) {
            $this->nextCursor = urldecode($m[1]);
        } else {
            $this->nextCursor = null;
        }

        $releases = $body['releases'] ?? [];

        return [
            'tenders'  => $this->parseReleases($releases),
            'has_more' => ! empty($this->nextCursor) && count($releases) >= self::PAGE_SIZE,
        ];
    }

    protected function hasMorePages(int $page, array $pageData): bool
    {
        return $pageData['has_more'] ?? false;
    }

    public function normalizeTender(array $raw): array
    {
        return array_filter(array_merge($raw, [
            'source'       => $this->getSourceSlug(),
            'source_url'   => self::SOURCE_URL,
            'country'      => 'United Kingdom',
            'country_code' => 'GB',
            'currency'     => 'GBP',
            'tier'         => 'starter',
        ]), fn ($v) => $v !== null);
    }

    private function parseReleases(array $releases): array
    {
        $tenders = [];

        foreach ($releases as $release) {
            $tender = $release['tender'] ?? [];
            $buyer  = $release['buyer']  ?? [];

            $title = trim($tender['title'] ?? '');
            if (empty($title)) {
                continue;
            }

            $ocid   = $release['ocid'] ?? '';
            $refNum = $tender['id'] ?? ($ocid ? 'UK-' . substr($ocid, -12) : null);
            if (empty($refNum)) {
                $refNum = 'UK-' . md5($title . ($tender['tenderPeriod']['endDate'] ?? ''));
            }

            $closingRaw = $tender['tenderPeriod']['endDate']
                ?? $tender['tenderPeriod']['dueDate']
                ?? '';

            $budget = null;
            if (! empty($tender['value']['amount'])) {
                $budget = (float) $tender['value']['amount'];
            }

            $description = $tender['description'] ?? null;
            $status      = $this->mapStatus($tender['status'] ?? 'active');
            $method      = $tender['procurementMethod'] ?? null;
            $category    = $this->mapCategory($tender['mainProcurementCategory'] ?? '');

            // Build canonical notice URL
            $noticeUrl = self::SOURCE_URL . '/Search/Results?Notices=' . urlencode($ocid);

            $tenders[] = [
                'tender_number'     => $refNum,
                'title'             => $title,
                'description'       => $description,
                'organization_name' => $buyer['name'] ?? null,
                'status'            => $status,
                'closing_at'        => $this->parseDateTime($closingRaw),
                'advertised_at'     => $this->parseDateTime($release['date'] ?? ''),
                'budget'            => $budget,
                'detail_url'        => $noticeUrl,
                'tender_type'       => $this->mapProcurementMethod($method),
                'category'          => $category,
            ];
        }

        return $tenders;
    }

    private function mapStatus(string $status): string
    {
        return match (strtolower($status)) {
            'active'    => 'Published',
            'cancelled' => 'Cancelled',
            'complete'  => 'Cancelled',
            default     => 'Published',
        };
    }

    private function mapCategory(string $raw): string
    {
        return match (strtolower($raw)) {
            'goods'    => 'Goods',
            'works'    => 'Works',
            'services' => 'Non-Consultancy Services',
            default    => 'Goods',
        };
    }

    private function mapProcurementMethod(string $method): string
    {
        if (str_contains(strtolower($method), 'framework')) {
            return 'Framework';
        }
        return match (strtolower($method)) {
            'open'      => 'Open Tender',
            'selective' => 'RFP',
            'limited'   => 'RFQ',
            default     => 'Tender Notice',
        };
    }

    protected function httpGetJson(string $url, array $query = []): \Illuminate\Http\Client\Response
    {
        $attempt = 0;

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::withHeaders([
                    'Accept'     => 'application/json',
                    'User-Agent' => 'TenderIQ/1.0 (+https://tenderiq.com)',
                ])->timeout(30)->get($url, $query);

                if ($response->successful()) {
                    return $response;
                }
            } catch (\Throwable) {
                // fall through to retry
            }

            $attempt++;
            if ($attempt < $this->maxRetries) {
                sleep((int) (2 ** $attempt));
            }
        }

        throw new \RuntimeException("Failed to fetch {$url} after {$this->maxRetries} attempts");
    }

    private function parseDateTime(string $raw): ?string
    {
        if (empty($raw)) return null;
        try {
            return Carbon::parse($raw)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
