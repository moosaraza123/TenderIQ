<?php

namespace App\Modules\Scraper\Services\Sources\USA;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class SamGovScraper extends ScraperService
{
    private const API_BASE   = 'https://api.sam.gov/prod/opportunities/v2/search';
    private const SOURCE_URL = 'https://sam.gov';
    private const PAGE_SIZE  = 100;

    public function getSourceSlug(): string
    {
        return 'sam_gov';
    }

    protected function fetchListingPage(int $page): array
    {
        $apiKey = config('services.sam_gov.api_key');

        if (empty($apiKey)) {
            throw new \RuntimeException('SAM_GOV_API_KEY is not configured');
        }

        $offset = ($page - 1) * self::PAGE_SIZE;

        $response = $this->httpGetJson(self::API_BASE, [
            'api_key'      => $apiKey,
            'limit'        => self::PAGE_SIZE,
            'offset'       => $offset,
            'active'       => 'true',
            'postedFrom'   => now()->subDays(30)->format('m/d/Y'),
            'postedTo'     => now()->format('m/d/Y'),
            'typeOfSetAside' => '',
        ]);

        if (! $response->successful()) {
            return ['tenders' => [], 'has_more' => false];
        }

        $body  = $response->json();
        $items = $body['opportunitiesData'] ?? [];
        $total = (int) ($body['totalRecords'] ?? 0);

        return [
            'tenders'  => $this->parseItems($items),
            'has_more' => ($offset + self::PAGE_SIZE) < min($total, 1000), // cap at 1000/run
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
            'country'      => 'United States',
            'country_code' => 'US',
            'currency'     => 'USD',
            'tier'         => 'professional',
        ]), fn ($v) => $v !== null);
    }

    private function parseItems(array $items): array
    {
        $tenders = [];

        foreach ($items as $item) {
            $title    = trim($item['title'] ?? '');
            $noticeId = $item['noticeId'] ?? '';
            $solNum   = $item['solicitationNumber'] ?? '';

            if (empty($title)) {
                continue;
            }

            $refNum = $solNum ?: ($noticeId ? 'SAM-' . substr($noticeId, 0, 16) : null);
            if (empty($refNum)) {
                continue;
            }

            $deadline = $item['responseDeadLine'] ?? '';
            $posted   = $item['postedDate'] ?? '';

            $orgPath = $item['fullParentPathName'] ?? '';
            $orgName = $item['organizationName'] ?? '';
            if (empty($orgName) && $orgPath) {
                $parts   = explode('.', $orgPath);
                $orgName = trim(end($parts));
            }

            $naics      = $item['naicsCode'] ?? '';
            $classCode  = $item['classificationCode'] ?? '';
            $noticeType = $item['noticeType'] ?? '';
            $detailUrl  = $item['uiLink'] ?? (self::SOURCE_URL . '/opp/' . $noticeId . '/view');

            $tenders[] = [
                'tender_number'     => $refNum,
                'title'             => $title,
                'description'       => $item['description'] ?? null,
                'organization_name' => $orgName ?: null,
                'ministry'          => $orgPath ? explode('.', $orgPath)[0] : null,
                'sector'            => $naics ?: null,
                'category'          => $this->mapClassCode($classCode),
                'status'            => 'Published',
                'closing_at'        => $this->parseDateTime($deadline),
                'advertised_at'     => $this->parseDate($posted),
                'detail_url'        => $detailUrl,
                'tender_type'       => $this->mapNoticeType($noticeType),
            ];
        }

        return $tenders;
    }

    private function mapClassCode(string $code): string
    {
        return match (strtoupper($code)) {
            'A', 'B'       => 'Works',
            'D', 'R', 'T'  => 'Non-Consultancy Services',
            'A1'           => 'Works',
            default        => 'Goods',
        };
    }

    private function mapNoticeType(string $type): string
    {
        return match (strtolower($type)) {
            'solicitation', 'sol'      => 'Open Tender',
            'presolicitation', 'presol' => 'Pre-Solicitation',
            'sources sought', 'ss'     => 'EOI',
            'combined synopsis'        => 'Tender Notice',
            'rfq'                      => 'RFQ',
            default                    => 'Tender Notice',
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
                ])->timeout(45)->get($url, $query);

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
