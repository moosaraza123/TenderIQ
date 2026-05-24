<?php

namespace App\Modules\Scraper\Services\Sources\Pakistan;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;

class PpraFederalScraper extends ScraperService
{
    private const BASE_URL   = 'https://epms.ppra.gov.pk/public/tenders/active-tenders';
    private const SOURCE_URL = 'https://epms.ppra.gov.pk';
    private const DETAIL_BASE = 'https://epms.ppra.gov.pk/public/tenders/tender-details';

    public function getSourceSlug(): string
    {
        return 'ppra_federal';
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
        // Fetch detail page for description, PDFs, category
        $detailData = [];
        if (! empty($raw['detail_url'])) {
            try {
                $detailData = $this->parseDetailHtml(
                    $this->httpGet($raw['detail_url'])->body(),
                    $raw['detail_url']
                );
                $this->rateLimit();
            } catch (\Throwable) {
                // proceed without detail data
            }
        }

        return array_filter(array_merge($raw, $detailData, [
            'source'       => $this->getSourceSlug(),
            'source_url'   => self::SOURCE_URL,
            'country'      => 'Pakistan',
            'country_code' => 'PK',
            'currency'     => 'PKR',
            'tier'         => 'free',
        ]), fn ($v) => $v !== null);
    }

    // ---------------------------------------------------------------------------
    // Listing parser — table cols: 0=Sr, 1=TenderNo, 2=Details, 3=Org, 4=Status,
    //                              5=Advertised, 6=Closing, 7=Actions
    // ---------------------------------------------------------------------------
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
            if ($cells->length < 8) {
                continue;
            }

            $tnNode       = $xpath->query('.//strong[contains(@class,"text-primary")]', $cells->item(1));
            $tenderNumber = trim($tnNode->item(0)?->textContent ?? '');
            if (empty($tenderNumber)) {
                continue;
            }

            $titleNode = $xpath->query('.//strong[not(ancestor::*[contains(@class,"badge")])]', $cells->item(2));
            $title     = trim($titleNode->item(0)?->textContent ?? '');

            $sectorNode = $xpath->query('.//small[contains(@class,"badge")]', $cells->item(2));
            $sector     = trim($sectorNode->item(0)?->textContent ?? '');

            $orgNode = $xpath->query('.//span[contains(@class,"tender-org")]', $cells->item(3));
            $orgName = $this->cleanText($orgNode->item(0)?->textContent ?? '');

            $ministryNode = $xpath->query('.//small[.//i[contains(@class,"ri-organization-chart")]]', $cells->item(3));
            $ministry     = $this->cleanText($ministryNode->item(0)?->textContent ?? '');

            $cityNode = $xpath->query('.//small[.//i[contains(@class,"ri-map-pin-line")]]', $cells->item(3));
            $cityRaw  = $this->cleanText($cityNode->item(0)?->textContent ?? '');
            $city     = $cityRaw ? explode(' - ', $cityRaw)[0] : null;

            $statusNode = $xpath->query('.//span[contains(@class,"tender-badge")]', $cells->item(4));
            $statusRaw  = trim($statusNode->item(0)?->textContent ?? 'Published');

            $advertised = $this->cleanText($cells->item(5)?->textContent ?? '');

            $closingDateNode = $xpath->query('.//strong', $cells->item(6));
            $closingTimeNode = $xpath->query('.//small', $cells->item(6));
            $closingDate     = trim($closingDateNode->item(0)?->textContent ?? '');
            $closingTime     = trim($closingTimeNode->item(0)?->textContent ?? '');
            $closingRaw      = $closingDate . ($closingTime ? ' ' . $closingTime : '');

            $detailLinkNode = $xpath->query('.//a[contains(@href,"tender-details")]', $cells->item(7));
            $detailHref     = $detailLinkNode->item(0)?->getAttribute('href') ?? '';
            $detailUrl      = $detailHref
                ? (str_starts_with($detailHref, 'http') ? $detailHref : self::SOURCE_URL . $detailHref)
                : '';

            if (empty($title)) {
                continue;
            }

            $tenders[] = [
                'tender_number'     => $tenderNumber,
                'title'             => $title,
                'organization_name' => $orgName ?: $ministry,
                'ministry'          => $ministry ?: null,
                'sector'            => $sector ?: null,
                'category'          => $this->categoryFromSector($sector),
                'city'              => $city,
                'status'            => $this->normalizeStatus($statusRaw),
                'advertised_at'     => $this->parseDate($advertised),
                'closing_at'        => $this->parseDateTime($closingRaw),
                'detail_url'        => $detailUrl,
                'tender_type'       => 'Tender Notice',
            ];
        }

        return array_filter($tenders, fn ($t) => ! empty($t['tender_number']) && ! empty($t['title']));
    }

    private function parseDetailHtml(string $html, string $url): array
    {
        $doc = new \DOMDocument();

        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        $pdfUrls  = [];
        $pdfLinks = $xpath->query('//a[contains(@href,"/pdf?file=")]');
        foreach ($pdfLinks as $link) {
            $href = $link->getAttribute('href');
            if ($href) {
                $pdfUrls[] = str_starts_with($href, 'http') ? $href : self::SOURCE_URL . $href;
            }
        }

        $description = '';
        $descNodes   = $xpath->query('//h6[contains(text(),"Description")]/following-sibling::div[contains(@class,"bg-light")]');
        if ($descNodes->length > 0) {
            $description = trim($descNodes->item(0)->textContent);
        }

        $details    = $this->extractDetailPairs($xpath);
        $category   = $this->normalizeCategory($details['Procurement Category'] ?? '');
        $sector     = $details['Sector'] ?? null;
        $tenderType = $details['Tender Type'] ?? null;

        $budget    = null;
        $budgetRaw = $details['Estimated Cost'] ?? '';
        if ($budgetRaw && preg_match('/[\d,]+/', str_replace(['Rs.', 'PKR', ' '], '', $budgetRaw), $matches)) {
            $budget = (float) str_replace(',', '', $matches[0]);
        }

        return array_filter([
            'description' => $description ?: null,
            'pdf_urls'    => ! empty($pdfUrls) ? $pdfUrls : null,
            'budget'      => $budget,
            'detail_url'  => $url,
            'category'    => $category ?: null,
            'sector'      => $sector ?: null,
            'tender_type' => $tenderType ?: null,
        ], fn ($v) => $v !== null);
    }

    private function extractDetailPairs(\DOMXPath $xpath): array
    {
        $pairs = [];
        $items = $xpath->query('//*[contains(@class,"list-group-item")]');

        foreach ($items as $item) {
            $labelNode = $xpath->query('.//*[contains(@class,"detail-label")]', $item);
            $valueNode = $xpath->query('.//*[contains(@class,"detail-value") or contains(@class,"flex-grow-1")]', $item);

            if ($labelNode->length > 0 && $valueNode->length > 0) {
                $key         = trim($labelNode->item(0)->textContent);
                $pairs[$key] = $this->cleanText($valueNode->item(0)->textContent);
            }
        }

        return $pairs;
    }

    private function detectMorePages(string $html, int $currentPage): bool
    {
        $nextPage = $currentPage + 1;
        return str_contains($html, '?page=' . $nextPage);
    }

    private function cleanText(string $text): string
    {
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private function normalizeCategory(string $raw): string
    {
        $lower = strtolower($raw);
        if (str_contains($lower, 'non-consult') || str_contains($lower, 'non consult')) {
            return 'Non-Consultancy Services';
        }
        if (str_contains($lower, 'consult')) return 'Consultancy Services';
        if (str_contains($lower, 'works'))   return 'Works';
        if (str_contains($lower, 'goods'))   return 'Goods';
        return $raw ?: 'Goods';
    }

    private function categoryFromSector(string $sector): string
    {
        $lower = strtolower($sector);
        if (str_contains($lower, 'civil works')) return 'Works';
        if (str_contains($lower, 'consult'))     return 'Consultancy Services';
        if (str_contains($lower, 'service'))     return 'Non-Consultancy Services';
        return 'Goods';
    }

    private function normalizeStatus(string $raw): string
    {
        $lower = strtolower($raw);
        if (str_contains($lower, 'corrig'))  return 'Corrigendum';
        if (str_contains($lower, 'cancel'))  return 'Cancelled';
        return 'Published';
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
