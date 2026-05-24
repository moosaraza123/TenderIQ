<?php

namespace App\Modules\Scraper\Services\Sources\International;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;

class AfdbScraper extends ScraperService
{
    private const RSS_URL    = 'https://www.afdb.org/en/rss/procurement-notices';
    private const SOURCE_URL = 'https://www.afdb.org';

    public function getSourceSlug(): string
    {
        return 'afdb';
    }

    protected function fetchListingPage(int $page): array
    {
        if ($page > 1) {
            return ['tenders' => [], 'has_more' => false];
        }

        $response = $this->httpGet(self::RSS_URL);
        if (! $response->successful()) {
            return ['tenders' => [], 'has_more' => false];
        }

        return [
            'tenders'  => $this->parseRss($response->body()),
            'has_more' => false,
        ];
    }

    protected function hasMorePages(int $page, array $pageData): bool
    {
        return false;
    }

    public function normalizeTender(array $raw): array
    {
        return array_filter(array_merge($raw, [
            'source'       => $this->getSourceSlug(),
            'source_url'   => self::SOURCE_URL,
            'currency'     => 'USD',
            'tier'         => 'professional',
        ]), fn ($v) => $v !== null);
    }

    private function parseRss(string $xml): array
    {
        $tenders = [];

        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($xml);
        libxml_clear_errors();

        if (! $feed) {
            return $tenders;
        }

        $items = $feed->channel->item ?? $feed->item ?? [];

        foreach ($items as $item) {
            $title = trim((string) $item->title);
            $link  = trim((string) $item->link);
            $desc  = trim(strip_tags((string) ($item->description ?? '')));
            $pubDate = trim((string) ($item->pubDate ?? ''));

            if (empty($title)) {
                continue;
            }

            $refNum = 'AFDB-' . md5($title . $link);

            // Extract country and deadline from description if present
            $country     = $this->extractCountry($desc);
            $countryCode = $this->mapCountryCode($country);

            $tenders[] = [
                'tender_number'     => $refNum,
                'title'             => $title,
                'description'       => $desc ?: null,
                'organization_name' => 'African Development Bank',
                'ministry'          => 'African Development Bank Group',
                'country'           => $country ?: 'Africa',
                'country_code'      => $countryCode,
                'status'            => 'Published',
                'advertised_at'     => $this->parseDate($pubDate),
                'closing_at'        => null,
                'detail_url'        => $link ?: null,
                'tender_type'       => 'Procurement Notice',
                'category'          => 'Non-Consultancy Services',
            ];
        }

        return $tenders;
    }

    private function extractCountry(string $text): string
    {
        // Common AfDB borrower countries
        $countries = [
            'Nigeria', 'Ethiopia', 'Egypt', 'Kenya', 'Ghana', 'Tanzania',
            'Mozambique', 'Uganda', 'Zambia', 'Cameroon', 'Senegal', 'Rwanda',
            'Côte d\'Ivoire', 'Ivory Coast', 'South Africa', 'Morocco', 'Tunisia',
            'Madagascar', 'Malawi', 'Niger', 'Burkina Faso', 'Mali',
        ];

        foreach ($countries as $country) {
            if (stripos($text, $country) !== false) {
                return $country;
            }
        }

        return '';
    }

    private function mapCountryCode(string $country): string
    {
        $map = [
            'Nigeria'       => 'NG', 'Ethiopia'  => 'ET', 'Egypt'    => 'EG',
            'Kenya'         => 'KE', 'Ghana'     => 'GH', 'Tanzania' => 'TZ',
            'Mozambique'    => 'MZ', 'Uganda'    => 'UG', 'Zambia'   => 'ZM',
            'Cameroon'      => 'CM', 'Senegal'   => 'SN', 'Rwanda'   => 'RW',
            'South Africa'  => 'ZA', 'Morocco'   => 'MA', 'Tunisia'  => 'TN',
            'Madagascar'    => 'MG', 'Malawi'    => 'MW', 'Niger'    => 'NE',
            'Burkina Faso'  => 'BF', 'Mali'      => 'ML',
        ];

        return $map[$country] ?? 'AF';
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
}
