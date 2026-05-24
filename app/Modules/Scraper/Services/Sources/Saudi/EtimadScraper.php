<?php

namespace App\Modules\Scraper\Services\Sources\Saudi;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;

class EtimadScraper extends ScraperService
{
    private const BASE_URL   = 'https://tenders.etimad.sa/Tender/AllTendersForVistor';
    private const SOURCE_URL = 'https://tenders.etimad.sa';

    public function getSourceSlug(): string
    {
        return 'etimad';
    }

    protected function fetchListingPage(int $page): array
    {
        $response = $this->httpGet(self::BASE_URL, [
            'PageNumber' => $page,
            'PageSize'   => 20,
        ]);
        $html = $response->body();

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
            'country'      => 'Saudi Arabia',
            'country_code' => 'SA',
            'currency'     => 'SAR',
            'tier'         => 'premium',
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
        $rows  = $xpath->query('//table//tbody/tr | //div[contains(@class,"tender-card")] | //div[contains(@class,"tender-item")]');

        foreach ($rows as $row) {
            $title      = '';
            $refNumber  = '';
            $closingRaw = '';
            $detailUrl  = '';
            $orgName    = '';
            $city       = '';

            $cells = $xpath->query('.//td', $row);
            if ($cells->length >= 3) {
                $refNumber  = trim($cells->item(0)?->textContent ?? '');
                $title      = trim($cells->item(1)?->textContent ?? '');
                $orgName    = trim($cells->item(2)?->textContent ?? '');
                $closingRaw = trim($cells->item($cells->length - 1)?->textContent ?? '');
            } else {
                $titleNode = $xpath->query('.//*[contains(@class,"tender-title") or contains(@class,"title") or contains(@class,"heading")]', $row);
                $title     = trim($titleNode->item(0)?->textContent ?? '');

                $orgNode = $xpath->query('.//*[contains(@class,"organization") or contains(@class,"agency") or contains(@class,"ministry")]', $row);
                $orgName = trim($orgNode->item(0)?->textContent ?? '');

                $dateNode = $xpath->query('.//*[contains(@class,"date") or contains(@class,"closing") or contains(@class,"deadline")]', $row);
                $closingRaw = trim($dateNode->item(0)?->textContent ?? '');
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
                $refNumber = 'ETIMAD-' . md5($title . $closingRaw);
            }

            $tenders[] = [
                'tender_number'     => $refNumber,
                'title'             => $title,
                'organization_name' => $orgName ?: 'Saudi Government',
                'status'            => 'Published',
                'closing_at'        => $this->parseDateTime($closingRaw),
                'detail_url'        => $detailUrl ?: null,
                'tender_type'       => 'Tender Notice',
                'category'          => 'Goods',
                'city'              => $city ?: null,
            ];
        }

        return array_filter($tenders, fn ($t) => ! empty($t['title']));
    }

    private function detectMorePages(string $html, int $page): bool
    {
        $nextPage = $page + 1;
        return str_contains($html, 'PageNumber=' . $nextPage)
            || str_contains($html, 'page=' . $nextPage)
            || str_contains($html, 'Next');
    }

    private function parseDateTime(string $raw): ?string
    {
        if (empty($raw)) return null;
        try {
            // Etimad uses Hijri or Gregorian dates — try both
            return Carbon::parse($raw)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
