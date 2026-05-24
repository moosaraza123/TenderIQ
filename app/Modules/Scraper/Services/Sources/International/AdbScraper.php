<?php

namespace App\Modules\Scraper\Services\Sources\International;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;

class AdbScraper extends ScraperService
{
    private const BASE_URL   = 'https://www.adb.org/projects/tenders/active';
    private const SOURCE_URL = 'https://www.adb.org';

    public function getSourceSlug(): string
    {
        return 'adb';
    }

    protected function fetchListingPage(int $page): array
    {
        $response = $this->httpGet(self::BASE_URL, [
            'page' => $page,
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
            'currency'     => 'USD',
            'tier'         => 'enterprise',
            'ministry'     => 'Asian Development Bank',
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
        $rows  = $xpath->query('//table//tbody/tr | //div[contains(@class,"views-row")] | //article');

        foreach ($rows as $row) {
            $title      = '';
            $refNumber  = '';
            $closingRaw = '';
            $detailUrl  = '';
            $country    = '';
            $orgName    = '';

            $cells = $xpath->query('.//td', $row);
            if ($cells->length >= 3) {
                $refNumber  = trim($cells->item(0)?->textContent ?? '');
                $title      = trim($cells->item(1)?->textContent ?? '');
                $country    = trim($cells->item(2)?->textContent ?? '');
                $closingRaw = trim($cells->item($cells->length - 1)?->textContent ?? '');
            } else {
                $titleNode = $xpath->query('.//h3 | .//h4 | .//*[contains(@class,"title")]', $row);
                $title     = trim($titleNode->item(0)?->textContent ?? '');

                $countryNode = $xpath->query('.//*[contains(@class,"country") or contains(@class,"location")]', $row);
                $country     = trim($countryNode->item(0)?->textContent ?? '');

                $dateNode = $xpath->query('.//*[contains(@class,"date") or contains(@class,"deadline")]', $row);
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
                $refNumber = 'ADB-' . md5($title . $closingRaw);
            }

            $tenders[] = [
                'tender_number'     => $refNumber,
                'title'             => $title,
                'organization_name' => $orgName ?: 'Asian Development Bank',
                'country'           => $country ?: null,
                'country_code'      => '*',
                'status'            => 'Published',
                'closing_at'        => $this->parseDateTime($closingRaw),
                'detail_url'        => $detailUrl ?: null,
                'tender_type'       => 'Procurement Notice',
                'category'          => 'Goods',
            ];
        }

        return array_filter($tenders, fn ($t) => ! empty($t['title']));
    }

    private function detectMorePages(string $html, int $page): bool
    {
        $nextPage = $page + 1;
        return str_contains($html, 'page=' . $nextPage)
            || str_contains($html, '?page=')
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
