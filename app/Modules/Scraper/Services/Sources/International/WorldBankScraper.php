<?php

namespace App\Modules\Scraper\Services\Sources\International;

use App\Modules\Scraper\Services\ScraperService;
use Illuminate\Support\Carbon;

class WorldBankScraper extends ScraperService
{
    private const API_URL    = 'http://search.worldbank.org/api/procnotices';
    private const SOURCE_URL = 'https://projects.worldbank.org';
    private const PAGE_SIZE  = 20;

    public function getSourceSlug(): string
    {
        return 'world_bank';
    }

    protected function fetchListingPage(int $page): array
    {
        $offset   = ($page - 1) * self::PAGE_SIZE;
        $response = $this->httpGet(self::API_URL, [
            'format'    => 'json',
            'rows'      => self::PAGE_SIZE,
            'os'        => $offset,
            'fl'        => 'id,notice_no,project_name,borrower_country,deadline_date,submission_date,publication_date,procurement_method,major_sector,notice_type,contact_email,url_project_page',
            'fq'        => 'notice_status_exact:Active',
            'sort'      => 'deadline_date desc',
        ]);

        if (! $response->successful()) {
            return ['tenders' => [], 'has_more' => false];
        }

        $json  = $response->json();
        $items = $json['procnotices'] ?? [];
        $total = (int) ($json['facets']['count'] ?? 0);

        return [
            'tenders'  => $this->parseApiItems($items),
            'has_more' => ($offset + self::PAGE_SIZE) < $total,
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
            'tier'         => 'professional',
        ]), fn ($v) => $v !== null);
    }

    private function parseApiItems(array $items): array
    {
        $tenders = [];

        foreach ($items as $item) {
            $title     = trim($item['project_name'] ?? '');
            $noticeNo  = trim($item['notice_no'] ?? '');
            $country   = trim($item['borrower_country'] ?? '');
            $deadline  = trim($item['deadline_date'] ?? '');
            $sector    = trim($item['major_sector'] ?? '');
            $type      = trim($item['notice_type'] ?? '');
            $detailUrl = trim($item['url_project_page'] ?? '');
            $contactEmail = trim($item['contact_email'] ?? '');

            if (empty($title)) {
                continue;
            }

            if (empty($noticeNo)) {
                $noticeNo = 'WB-' . md5($title . $deadline);
            }

            $pubDate = trim($item['publication_date'] ?? '');

            $tenders[] = [
                'tender_number'     => $noticeNo,
                'title'             => $title,
                'organization_name' => 'World Bank',
                'ministry'          => 'World Bank Group',
                'country'           => $country ?: null,
                'country_code'      => $this->mapCountryCode($country),
                'status'            => 'Published',
                'advertised_at'     => $pubDate ? $this->parseDate($pubDate) : now()->toDateString(),
                'closing_at'        => $this->parseDateTime($deadline),
                'detail_url'        => $detailUrl ?: null,
                'tender_type'       => $type ?: 'Procurement Notice',
                'category'          => $sector ?: null,
                'contact_email'     => $contactEmail ?: null,
            ];
        }

        return $tenders;
    }

    private function mapCountryCode(string $country): string
    {
        // Common mappings; full ISO list not needed for every case
        $map = [
            'Pakistan'     => 'PK',
            'Bangladesh'   => 'BD',
            'India'        => 'IN',
            'Nigeria'      => 'NG',
            'Kenya'        => 'KE',
            'Ethiopia'     => 'ET',
            'Ghana'        => 'GH',
            'Tanzania'     => 'TZ',
            'Indonesia'    => 'ID',
            'Philippines'  => 'PH',
            'Vietnam'      => 'VN',
            'Nepal'        => 'NP',
            'Cambodia'     => 'KH',
            'Jordan'       => 'JO',
            'Egypt'        => 'EG',
            'Morocco'      => 'MA',
            'Tunisia'      => 'TN',
            'Senegal'      => 'SN',
            'Rwanda'       => 'RW',
            'Mozambique'   => 'MZ',
        ];
        return $map[$country] ?? '*';
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
