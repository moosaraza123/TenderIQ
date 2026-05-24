<?php

namespace App\Modules\Scraper\Services\Sources\UK;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class UkContractsFinderScraper extends ScraperService
{
    private const API_URL    = 'https://www.contractsfinder.service.gov.uk/Published/Notices/PublicSearch/Search';
    private const SOURCE_URL = 'https://www.contractsfinder.service.gov.uk';
    private const PAGE_SIZE  = 100;

    public function getSourceSlug(): string
    {
        return 'uk_cf';
    }

    protected function fetchListingPage(int $page): array
    {
        $from = now()->subDays(7)->format('Y-m-d');
        $to   = now()->format('Y-m-d');

        $response = Http::withHeaders([
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'User-Agent'   => 'TenderIQ/1.0 (+https://tenderiq.com)',
        ])->timeout(30)->post(self::API_URL, [
            'searchCriteria' => [
                'keyword'       => '',
                'status'        => ['published'],
                'publishedFrom' => $from,
                'publishedTo'   => $to,
                'noticeType'    => [],
                'sector'        => [],
            ],
            'size' => self::PAGE_SIZE,
            'from' => ($page - 1) * self::PAGE_SIZE,
        ]);

        if (! $response->successful()) {
            return ['tenders' => [], 'has_more' => false];
        }

        $body   = $response->json();
        $items  = $body['results'] ?? $body['notices'] ?? [];
        $total  = (int) ($body['totalResults'] ?? $body['total'] ?? 0);

        return [
            'tenders'  => $this->parseItems($items),
            'has_more' => ($page * self::PAGE_SIZE) < min($total, 2000),
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

    private function parseItems(array $items): array
    {
        $tenders = [];

        foreach ($items as $item) {
            $title = trim($item['title'] ?? $item['name'] ?? '');
            if (empty($title)) {
                continue;
            }

            $id     = $item['id'] ?? $item['noticeIdentifier'] ?? null;
            $refNum = $id ? 'CF-' . $id : 'CF-' . md5($title);

            $closingRaw = $item['closeDate']
                ?? $item['closingDate']
                ?? $item['deadline']
                ?? '';

            $postedRaw = $item['publishDate']
                ?? $item['postedDate']
                ?? $item['publishedDate']
                ?? '';

            $budgetMin = (float) ($item['value']['minValue'] ?? $item['estimatedValue'] ?? 0);
            $budgetMax = (float) ($item['value']['maxValue'] ?? 0);
            $budget    = $budgetMax ?: ($budgetMin ?: null);

            $org = $item['contracting_authority']['name']
                ?? $item['contractingAuthority']['name']
                ?? $item['organisation']
                ?? null;

            $detailUrl = self::SOURCE_URL . '/Notice/' . $id;

            $tenders[] = [
                'tender_number'     => $refNum,
                'title'             => $title,
                'description'       => isset($item['description']) ? strip_tags($item['description']) : null,
                'organization_name' => $org,
                'status'            => 'Published',
                'closing_at'        => $this->parseDateTime($closingRaw),
                'advertised_at'     => $this->parseDate($postedRaw),
                'budget'            => $budget,
                'detail_url'        => $detailUrl,
                'tender_type'       => 'Tender Notice',
                'category'          => 'Non-Consultancy Services',
            ];
        }

        return $tenders;
    }

    private function parseDate(string $raw): ?string
    {
        if (empty($raw)) return null;
        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
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
