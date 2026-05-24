<?php

namespace App\Modules\Scraper\Services\Sources\Pakistan;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;

class KppraScraper extends ScraperService
{
    private const BASE_URL   = 'http://www.kppra.gov.pk/kppra/activetenders.php';
    private const SOURCE_URL = 'http://www.kppra.gov.pk/kppra';

    public function getSourceSlug(): string
    {
        return 'kppra';
    }

    protected function fetchListingPage(int $page): array
    {
        $response = $this->httpGet(self::BASE_URL, ['p' => $page]);
        $html     = $response->body();

        return [
            'tenders'  => $this->parseListingHtml($html),
            'has_more' => $this->detectMorePages($html),
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
            'region'       => 'Khyber Pakhtunkhwa',
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

        // Main listing table has class "table-striped"; rows sit directly after thead (no tbody tag).
        // Skip corrigendum sub-rows which have an @id attribute.
        $rows = $xpath->query('//table[contains(@class,"table-striped")]//tr[td and not(@id)]');

        foreach ($rows as $row) {
            $cells = $xpath->query('.//td', $row);
            if ($cells->length < 5) {
                continue;
            }

            $tenderNumber  = trim($cells->item(0)?->textContent ?? '');
            $title         = trim($cells->item(1)?->textContent ?? '');
            $orgName       = preg_replace('/\s+/', ' ', trim($cells->item(2)?->textContent ?? ''));
            $advertisedRaw = trim($cells->item(3)?->textContent ?? '');
            $closingRaw    = trim($cells->item(4)?->textContent ?? '');

            if (empty($title)) {
                continue;
            }

            if (empty($tenderNumber)) {
                $tenderNumber = 'KPPRA-' . md5($title . $closingRaw);
            } else {
                $tenderNumber = 'KPPRA-' . $tenderNumber;
            }

            // Column 5 is the document download link
            $detailHref = null;
            if ($cells->length > 5) {
                $links = $xpath->query('.//a[@href]', $cells->item(5));
                foreach ($links as $link) {
                    if (! ($link instanceof \DOMElement)) continue;
                    $href = $link->getAttribute('href');
                    if ($href) {
                        $detailHref = str_starts_with($href, 'http')
                            ? $href
                            : self::SOURCE_URL . '/' . ltrim($href, '/');
                        break;
                    }
                }
            }

            $tenders[] = [
                'tender_number'     => $tenderNumber,
                'title'             => $title,
                'organization_name' => $orgName ?: 'KPK Government',
                'status'            => 'Published',
                'advertised_at'     => $this->parseDate($advertisedRaw),
                'closing_at'        => $this->parseDate($closingRaw),
                'detail_url'        => $detailHref,
                'tender_type'       => 'Tender Notice',
                'category'          => 'Goods',
            ];
        }

        return $tenders;
    }

    private function detectMorePages(string $html): bool
    {
        return (bool) preg_match('/href=["\'][^"\']*[?&]p=\d+[^"\']*["\'][^>]*>\s*Next\s*</i', $html);
    }

    private function parseDate(string $raw): ?string
    {
        if (empty($raw)) return null;
        try {
            return Carbon::parse($raw)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
