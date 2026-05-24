<?php

namespace App\Modules\Scraper\Services\Sources\UAE;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;

class AdgpgScraper extends ScraperService
{
    private const BASE_URL   = 'https://www.adgpg.gov.ae/en/For-Suppliers/Public-Tenders';
    private const SOURCE_URL = 'https://www.adgpg.gov.ae';

    public function getSourceSlug(): string
    {
        return 'adgpg';
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
            'country'      => 'United Arab Emirates',
            'country_code' => 'AE',
            'currency'     => 'AED',
            'tier'         => 'paid',
            'region'       => 'Abu Dhabi',
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

        // ADGPG uses a table or card-based layout
        $rows = $xpath->query('//table//tbody/tr | //div[contains(@class,"tender-item")] | //article[contains(@class,"tender")]');

        foreach ($rows as $row) {
            $title      = '';
            $orgName    = '';
            $closingRaw = '';
            $detailUrl  = '';
            $refNumber  = '';

            // Try table cells first
            $cells = $xpath->query('.//td', $row);
            if ($cells->length >= 3) {
                $refNumber  = trim($cells->item(0)?->textContent ?? '');
                $title      = trim($cells->item(1)?->textContent ?? '');
                $orgName    = trim($cells->item(2)?->textContent ?? '');
                $closingRaw = trim($cells->item($cells->length - 1)?->textContent ?? '');
            } else {
                // Card layout
                $titleNode = $xpath->query('.//*[contains(@class,"title") or contains(@class,"heading") or contains(@class,"name")]', $row);
                $title     = trim($titleNode->item(0)?->textContent ?? '');
            }

            $links = $xpath->query('.//a', $row);
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if ($href && ! str_contains($href, '#')) {
                    $detailUrl = str_starts_with($href, 'http') ? $href : self::SOURCE_URL . $href;
                    break;
                }
            }

            if (empty($title)) {
                continue;
            }

            if (empty($refNumber)) {
                $refNumber = 'ADGPG-' . md5($title . $closingRaw);
            }

            $tenders[] = [
                'tender_number'     => $refNumber,
                'title'             => $title,
                'organization_name' => $orgName ?: 'Abu Dhabi Government',
                'status'            => 'Published',
                'closing_at'        => $this->parseDateTime($closingRaw),
                'detail_url'        => $detailUrl ?: null,
                'tender_type'       => 'Tender Notice',
                'category'          => 'Goods',
                'city'              => 'Abu Dhabi',
            ];
        }

        return array_filter($tenders, fn ($t) => ! empty($t['title']));
    }

    private function detectMorePages(string $html, int $page): bool
    {
        $nextPage = $page + 1;
        return str_contains($html, 'page=' . $nextPage)
            || str_contains($html, 'PageIndex=' . $nextPage)
            || str_contains($html, 'Next');
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
