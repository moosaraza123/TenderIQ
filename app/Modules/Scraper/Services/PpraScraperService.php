<?php

namespace App\Modules\Scraper\Services;

use App\Modules\Tender\Jobs\SummarizeTender;
use App\Modules\Tender\Models\Tender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PpraScraperService extends ScraperService
{
    private const BASE_URL   = 'https://epms.ppra.gov.pk/public/tenders/active-tenders';
    private const SOURCE_URL = 'https://epms.ppra.gov.pk';

    public function getSourceName(): string
    {
        return 'PPRA';
    }

    public function fetchListingPage(int $page): array
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

    public function fetchTenderDetail(string $url): array
    {
        $response = $this->httpGet($url);
        return $this->parseDetailHtml($response->body(), $url);
    }

    protected function processTender(array $data): string
    {
        $existing = Tender::where('tender_number', $data['tender_number'])->first();

        if ($existing) {
            return 'exists';
        }

        DB::transaction(function () use ($data, &$result) {
            $detailData = [];
            if (! empty($data['detail_url'])) {
                try {
                    $detailData = $this->fetchTenderDetail($data['detail_url']);
                    $this->rateLimit();
                } catch (\Throwable $e) {
                    Log::warning("Detail fetch failed for {$data['tender_number']}", ['error' => $e->getMessage()]);
                }
            }

            $tender = Tender::create(array_merge($data, $detailData, [
                'source_url' => self::SOURCE_URL,
                'scraped_at' => now(),
            ]));

            if (! empty($tender->pdf_urls) || ! empty($tender->description)) {
                SummarizeTender::dispatch($tender->id);
            }

            $result = 'new';
        });

        return $result ?? 'new';
    }

    // ---------------------------------------------------------------------------
    // Listing page parser
    // Table columns: 0=Sr, 1=TenderNo, 2=TenderDetails, 3=OrgDetails,
    //                4=Status, 5=Advertised, 6=Closing, 7=Actions
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

            // Tender number: <strong class="text-primary fs-13">
            $tnNode       = $xpath->query('.//strong[contains(@class,"text-primary")]', $cells->item(1));
            $tenderNumber = trim($tnNode->item(0)?->textContent ?? '');
            if (empty($tenderNumber)) {
                continue;
            }

            // Title: first <strong> not nested inside a badge
            $titleNode = $xpath->query('.//strong[not(ancestor::*[contains(@class,"badge")])]', $cells->item(2));
            $title     = trim($titleNode->item(0)?->textContent ?? '');

            // Sector: first small.badge in the Tender Details cell
            $sectorNode = $xpath->query('.//small[contains(@class,"badge")]', $cells->item(2));
            $sector     = trim($sectorNode->item(0)?->textContent ?? '');

            // Organization name: <span class="tender-org">
            $orgNode = $xpath->query('.//span[contains(@class,"tender-org")]', $cells->item(3));
            $orgName = $this->cleanText($orgNode->item(0)?->textContent ?? '');

            // Ministry: small containing ri-organization-chart icon
            $ministryNode = $xpath->query('.//small[.//i[contains(@class,"ri-organization-chart")]]', $cells->item(3));
            $ministry     = $this->cleanText($ministryNode->item(0)?->textContent ?? '');

            // City: "Karachi - Pakistan" → "Karachi"
            $cityNode = $xpath->query('.//small[.//i[contains(@class,"ri-map-pin-line")]]', $cells->item(3));
            $cityRaw  = $this->cleanText($cityNode->item(0)?->textContent ?? '');
            $city     = $cityRaw ? explode(' - ', $cityRaw)[0] : null;

            // Status: <span class="tender-badge ...">
            $statusNode = $xpath->query('.//span[contains(@class,"tender-badge")]', $cells->item(4));
            $statusRaw  = trim($statusNode->item(0)?->textContent ?? 'Published');

            // Advertised date
            $advertised = $this->cleanText($cells->item(5)?->textContent ?? '');

            // Closing: <strong>Jun 18, 2026</strong> + <small>03:00 PM</small>
            $closingDateNode = $xpath->query('.//strong', $cells->item(6));
            $closingTimeNode = $xpath->query('.//small', $cells->item(6));
            $closingDate     = trim($closingDateNode->item(0)?->textContent ?? '');
            $closingTime     = trim($closingTimeNode->item(0)?->textContent ?? '');
            $closingRaw      = $closingDate . ($closingTime ? ' ' . $closingTime : '');

            // Detail URL: /public/tenders/tender-details/{number}
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
                'country'           => 'Pakistan',
            ];
        }

        return array_filter($tenders, fn ($t) => ! empty($t['tender_number']) && ! empty($t['title']));
    }

    // ---------------------------------------------------------------------------
    // Detail page parser
    // ---------------------------------------------------------------------------
    private function parseDetailHtml(string $html, string $url): array
    {
        $doc = new \DOMDocument();

        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);

        // PDF links: href="/pdf?file=..."
        $pdfUrls  = [];
        $pdfLinks = $xpath->query('//a[contains(@href,"/pdf?file=")]');
        foreach ($pdfLinks as $link) {
            $href = $link->getAttribute('href');
            if ($href) {
                $pdfUrls[] = str_starts_with($href, 'http') ? $href : self::SOURCE_URL . $href;
            }
        }

        // Description: div.bg-light inside the Description section
        $description  = '';
        $descNodes    = $xpath->query('//h6[contains(text(),"Description")]/following-sibling::div[contains(@class,"bg-light")]');
        if ($descNodes->length > 0) {
            $description = trim($descNodes->item(0)->textContent);
        }

        // Structured key→value pairs from detail-label / detail-value or flex-grow-1
        $details    = $this->extractDetailPairs($xpath);
        $category   = $this->normalizeCategory($details['Procurement Category'] ?? '');
        $sector     = $details['Sector'] ?? null;
        $tenderType = $details['Tender Type'] ?? null;

        // Budget from financial section
        $budget    = null;
        $budgetRaw = $details['Estimated Cost'] ?? $details['Budget'] ?? '';
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
                $key          = trim($labelNode->item(0)->textContent);
                $pairs[$key]  = $this->cleanText($valueNode->item(0)->textContent);
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

    // Infer procurement category from sector name (listing page only shows sector)
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
