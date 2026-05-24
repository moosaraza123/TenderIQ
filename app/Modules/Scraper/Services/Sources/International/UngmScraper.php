<?php

namespace App\Modules\Scraper\Services\Sources\International;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;

class UngmScraper extends ScraperService
{
    private const BASE_URL   = 'https://www.ungm.org/Public/Notice';
    private const SOURCE_URL = 'https://www.ungm.org';

    public function getSourceSlug(): string
    {
        return 'ungm';
    }

    protected function fetchListingPage(int $page): array
    {
        $response = $this->httpGet(self::BASE_URL, [
            'pageIndex' => $page,
            'pageSize'  => 20,
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
        $rows  = $xpath->query('//table//tbody/tr | //div[contains(@class,"notice-list-item")] | //div[contains(@class,"tender-row")]');

        foreach ($rows as $row) {
            $title      = '';
            $refNumber  = '';
            $closingRaw = '';
            $detailUrl  = '';
            $orgName    = '';
            $country    = '';

            $cells = $xpath->query('.//td', $row);
            if ($cells->length >= 3) {
                $refNumber  = trim($cells->item(0)?->textContent ?? '');
                $title      = trim($cells->item(1)?->textContent ?? '');
                $orgName    = trim($cells->item(2)?->textContent ?? '');
                $closingRaw = trim($cells->item($cells->length - 1)?->textContent ?? '');
            } else {
                $titleNode = $xpath->query('.//*[contains(@class,"title") or contains(@class,"notice-title")]', $row);
                $title     = trim($titleNode->item(0)?->textContent ?? '');

                $orgNode = $xpath->query('.//*[contains(@class,"agency") or contains(@class,"organization")]', $row);
                $orgName = trim($orgNode->item(0)?->textContent ?? '');

                $dateNode = $xpath->query('.//*[contains(@class,"deadline") or contains(@class,"closing")]', $row);
                $closingRaw = trim($dateNode->item(0)?->textContent ?? '');

                $countryNode = $xpath->query('.//*[contains(@class,"country")]', $row);
                $country     = trim($countryNode->item(0)?->textContent ?? '');
            }

            $links = $xpath->query('.//a', $row);
            foreach ($links as $link) {
                $href = $link->getAttribute('href');
                if ($href && str_contains($href, 'Notice') && ! str_contains($href, '#')) {
                    $detailUrl = str_starts_with($href, 'http') ? $href : self::SOURCE_URL . $href;
                    break;
                }
            }
            if (empty($detailUrl)) {
                foreach ($links as $link) {
                    $href = $link->getAttribute('href');
                    if ($href && ! str_contains($href, '#')) {
                        $detailUrl = str_starts_with($href, 'http') ? $href : self::SOURCE_URL . $href;
                        break;
                    }
                }
            }

            if (empty($title)) {
                continue;
            }

            if (empty($refNumber)) {
                $refNumber = 'UNGM-' . md5($title . $closingRaw);
            }

            $tenders[] = [
                'tender_number'     => $refNumber,
                'title'             => $title,
                'organization_name' => $orgName ?: 'United Nations',
                'ministry'          => 'United Nations',
                'country'           => $country ?: null,
                'country_code'      => '*',
                'status'            => 'Published',
                'closing_at'        => $this->parseDateTime($closingRaw),
                'detail_url'        => $detailUrl ?: null,
                'tender_type'       => 'Procurement Notice',
            ];
        }

        return array_filter($tenders, fn ($t) => ! empty($t['title']));
    }

    private function detectMorePages(string $html, int $page): bool
    {
        $nextPage = $page + 1;
        return str_contains($html, 'pageIndex=' . $nextPage)
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
