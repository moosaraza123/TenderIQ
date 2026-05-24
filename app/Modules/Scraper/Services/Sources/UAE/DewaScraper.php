<?php

namespace App\Modules\Scraper\Services\Sources\UAE;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;

class DewaScraper extends ScraperService
{
    private const BASE_URL   = 'https://www.dewa.gov.ae/en/about-dewa/procurement/current-tenders';
    private const SOURCE_URL = 'https://www.dewa.gov.ae';

    public function getSourceSlug(): string
    {
        return 'dewa';
    }

    protected function fetchListingPage(int $page): array
    {
        $response = $this->httpGet(self::BASE_URL);
        $html     = $response->body();

        return [
            'tenders'  => $this->parseListingHtml($html),
            'has_more' => false,
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
            'region'       => 'Dubai',
            'city'         => 'Dubai',
            'category'     => 'Goods',
            'sector'       => 'Electrical Items',
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
        $rows  = $xpath->query('//table//tbody/tr | //div[contains(@class,"tender")] | //article');

        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);

            $title      = '';
            $refNumber  = '';
            $closingRaw = '';
            $detailUrl  = '';

            if ($cells->length >= 2) {
                $refNumber  = trim($cells->item(0)?->textContent ?? '');
                $title      = trim($cells->item(1)?->textContent ?? '');
                $closingRaw = $cells->length > 2 ? trim($cells->item($cells->length - 1)?->textContent ?? '') : '';
            } else {
                $titleNode = $xpath->query('.//*[contains(@class,"title") or contains(@class,"heading")]', $row);
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
                $refNumber = 'DEWA-' . md5($title . $closingRaw);
            }

            $tenders[] = [
                'tender_number'     => $refNumber,
                'title'             => $title,
                'organization_name' => 'Dubai Electricity and Water Authority (DEWA)',
                'ministry'          => 'Dubai Government',
                'status'            => 'Published',
                'closing_at'        => $this->parseDateTime($closingRaw),
                'detail_url'        => $detailUrl ?: null,
                'tender_type'       => 'Tender Notice',
            ];
        }

        return array_filter($tenders, fn ($t) => ! empty($t['title']));
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
