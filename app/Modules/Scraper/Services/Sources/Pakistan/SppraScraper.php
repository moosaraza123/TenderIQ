<?php

namespace App\Modules\Scraper\Services\Sources\Pakistan;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;

class SppraScraper extends ScraperService
{
    private const BASE_URL   = 'https://www.pprasindh.gov.pk/tendersdepartment.php';
    private const SOURCE_URL = 'https://www.pprasindh.gov.pk';

    public function getSourceSlug(): string
    {
        return 'sppra';
    }

    protected function fetchListingPage(int $page): array
    {
        $response = $this->httpGet(self::BASE_URL, ['page' => $page]);
        $html     = $response->body();

        return [
            'tenders'  => $this->parseListingHtml($html),
            'has_more' => $this->detectMorePages($html, $page),
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
            'country'      => 'Pakistan',
            'country_code' => 'PK',
            'currency'     => 'PKR',
            'tier'         => 'free',
        ]), fn ($v) => $v !== null);
    }

    private function parseListingHtml(string $html): array
    {
        $tenders = [];
        $doc     = new \DOMDocument();

        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $rows  = $xpath->query('//table[contains(@class,"table")]//tbody/tr');

        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);
            if ($cells->length < 4) {
                continue;
            }

            $tenderNumber = trim($cells->item(0)?->textContent ?? '');
            $title        = trim($cells->item(1)?->textContent ?? '');
            $orgName      = trim($cells->item(2)?->textContent ?? '');
            $closingRaw   = trim($cells->item(3)?->textContent ?? '');

            $detailHref = '';
            $links      = $xpath->query('.//a', $row);
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if ($href) {
                    $detailHref = str_starts_with($href, 'http') ? $href : self::SOURCE_URL . '/' . ltrim($href, '/');
                    break;
                }
            }

            if (empty($tenderNumber) || empty($title)) {
                continue;
            }

            $tenders[] = [
                'tender_number'     => $tenderNumber,
                'title'             => $title,
                'organization_name' => $orgName,
                'status'            => 'Published',
                'closing_at'        => $this->parseDateTime($closingRaw),
                'detail_url'        => $detailHref ?: null,
                'tender_type'       => 'Tender Notice',
                'category'          => 'Goods',
            ];
        }

        return array_filter($tenders, fn ($t) => ! empty($t['tender_number']) && ! empty($t['title']));
    }

    private function detectMorePages(string $html, int $page): bool
    {
        $nextPage = $page + 1;
        return str_contains($html, 'page=' . $nextPage) || str_contains($html, 'Next');
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
