You are building TenderIQ — an AI-powered global government tender 
aggregation platform.

## REVISED BUSINESS MODEL
Free tier:    Pakistan PPRA tenders (traffic + SEO)
Paid $29/mo:  UK government tenders (Find a Tender + Contracts Finder)
Paid $49/mo:  USA federal tenders (SAM.gov)
Paid $99/mo:  All sources (UK + USA + World Bank + UN + ADB + Pakistan)

Reasoning:
- UAE and Saudi portals are NOT scrapeable (JS-heavy, login walls)
- UK has a completely FREE official API — no scraping risk
- USA SAM.gov has a FREE official API — world's largest procurement market
- World Bank API is FREE and covers 100+ countries
- These three sources alone justify $29-99/month subscriptions
- GovWin (main US competitor) charges $500-2000/month — we undercut massively

---

## PHASE 1 DATA SOURCES (ONLY THESE — NO UAE/SAUDI)

### FREE TIER — Pakistan

1. PPRA Federal (EPMS)
   Listing:    https://epms.ppra.gov.pk/public/tenders/active-tenders
   Detail:     https://epms.ppra.gov.pk/public/tenders/tender-details/{id}
   Pagination: ?page=N&tender_type=1
   Type:       HTML scraping
   Volume:     ~3,000/month
   Tier:       free
   Notes:      Clean HTML, no login, no CAPTCHA, server-side rendered
               Already verified accessible
               Unique index: tender_number from PPRA

---

### PAID TIER — International (Official Free APIs)

2. World Bank Procurement Notices
   Base URL:   http://search.worldbank.org/api/procnotices
   Format:     JSON — no auth required
   Pagination: &rows=50&os=0 (os = offset)
   Filters:    &country=GB for UK, &country=US for USA etc
   Volume:     ~500 active notices globally, updated daily
   Tier:       paid
   Notes:      COMPLETELY FREE official API
               Creative Commons licensed
               Covers infrastructure, health, education globally
               High value contracts ($1M-$100M+)
               Target users: development consultants, international contractors
   
   Example call:
   GET http://search.worldbank.org/api/procnotices?format=json&rows=50&os=0
   
   Response fields to extract:
   - id → source_id
   - project_name → title
   - borrower → organization_name
   - country → country
   - notice_type → tender_type
   - submission_date → closing_at
   - publication_date → advertised_at
   - contact_email, contact_phone
   - url → source_url
   - procurement_method
   - sector

3. UK Find a Tender (FTS)
   Base URL:   https://www.find-tender.service.gov.uk
   OCDS API:   https://www.find-tender.service.gov.uk/api/1.0/ocdsReleasePackages
   Format:     JSON (OCDS standard) — NO AUTH REQUIRED for reading
   Volume:     ~1,000 notices/week
   Tier:       paid
   Notes:      Official UK government API
               Open Government Licence (free to use commercially)
               Covers contracts above £139,688 including VAT
               Updated continuously
               All in English
               Target users: UK SMEs, contractors, consultants
   
   Pagination params:
   ?publishedFrom=YYYY-MM-DD&publishedTo=YYYY-MM-DD
   &stages=tender (for active tenders only)
   
   OCDS response structure:
   releases[].tender.title → title
   releases[].tender.description → description
   releases[].buyer.name → organization_name
   releases[].tender.value.amount → budget
   releases[].tender.value.currency → currency
   releases[].tender.tenderPeriod.endDate → closing_at
   releases[].date → advertised_at
   releases[].id → source_id
   releases[].tender.status → status

4. UK Contracts Finder
   Base URL:   https://www.contractsfinder.service.gov.uk
   API:        https://www.contractsfinder.service.gov.uk/Published/Notices/PublicSearch/Search
   Format:     JSON — no auth required
   Volume:     ~500/week (lower value contracts, £12,000+)
   Tier:       paid
   Notes:      Covers lower-value contracts FTS misses
               Combined with FTS = complete UK coverage
               Both APIs together = comprehensive UK market

5. USA SAM.gov
   Base URL:   https://api.sam.gov/prod/opportunities/v2/search
   Auth:       Free API key from sam.gov (register at sam.gov/api)
               Takes 1-2 business days to get approved
               Registration is free, no cost
   Volume:     ~2,000+ active opportunities daily
   Tier:       paid
   Notes:      World's largest government procurement market ($673B/year)
               Completely free API, just needs registration
               GovWin charges $500-2000/month for same data
               You charge $49/month = massive price advantage
               English only, well-structured JSON
   
   Key params:
   ?api_key=YOUR_KEY
   &limit=100
   &postedFrom=MM/DD/YYYY
   &postedTo=MM/DD/YYYY
   &ptype=o (solicitations/opportunities only)
   &active=Yes
   
   Response fields:
   noticeId → source_id
   title → title
   fullParentPathName → organization_name + ministry
   naicsCode → sector
   type → tender_type
   responseDeadLine → closing_at
   postedDate → advertised_at
   baseType → category
   placeOfPerformance.city.name → city
   placeOfPerformance.state.name → region
   description → description (truncated, fetch detail separately)
   uiLink → source_url

6. UN Global Marketplace (UNGM)
   URL:        https://www.ungm.org/Public/Notice
   Type:       HTML scraping (public, no login)
   Volume:     ~200/month
   Tier:       paid
   Notes:      All UN agencies publish here (UNDP, UNICEF, WHO etc)
               English only, well structured
               High prestige, high value contracts
               Contractors globally pay premium for UN opportunities
   
   Scraping approach:
   - GET https://www.ungm.org/Public/Notice
   - Parse table: reference, title, organization, deadline, country
   - Detail page: https://www.ungm.org/Public/Notice/{id}
   - 3 second delay between requests (UN server is slow)

7. Asian Development Bank (ADB)
   URL:        https://www.adb.org/projects/tenders/active
   Type:       HTML scraping (public, no login)
   Volume:     ~300/month
   Tier:       paid
   Notes:      Heavy Pakistan/Asia focus
               ADB funds massive infrastructure projects in Pakistan
               Very relevant for your user base

8. African Development Bank (AfDB)
   RSS:        https://www.afdb.org/en/documents/project-related-procurement/procurement-notices/specific-procurement-notices
   Type:       RSS feed parsing
   Volume:     ~100/month
   Tier:       paid
   Notes:      Simple RSS — easiest implementation after World Bank
               Parse with SimpleXML in PHP

---

## REMOVED SOURCES (UAE/Saudi — NOT IN PHASE 1)

UAE (ADGPG, Dubai eSupply, RTA, DEWA, ADNOC, Sharjah):
  REMOVED — JavaScript-heavy portals, login required for details
  Add in Phase 2 only if you pay for a proxy service + headless browser

Saudi (Etimad):
  REMOVED — JavaScript-rendered, no public API
  Add in Phase 2 using TendersAlerts paid API ($100-200/month)
  Only worth it after $2,000+ MRR is proven

---

## REVISED PRICING

Free — $0
✓ Pakistan PPRA tenders (3,000+/month)
✓ 5 tender views/day
✓ 1 keyword alert (email, daily digest only)
✗ No AI summaries
✗ No international tenders

Starter — $29/month
✓ UK government tenders (Find a Tender + Contracts Finder)
✓ Unlimited tender views
✓ AI summaries + recommendations
✓ 5 keyword alerts (instant)
✓ CSV export
✗ No USA/International

Professional — $49/month
✓ Everything in Starter
✓ USA SAM.gov federal tenders
✓ World Bank + UN + ADB + AfDB tenders
✓ 20 keyword alerts
✓ Daily/weekly digest option
✓ Budget range alerts
✗ No API access

Enterprise — $99/month
✓ Everything in Professional
✓ Unlimited alerts
✓ API access (1,000 calls/day)
✓ Webhooks (POST to your URL on match)
✓ Priority email support
✓ Custom alert workflows

Payment: Stripe
Primary currency: USD (works globally)
Also offer: GBP for UK market (Stripe supports natively)

---

## REVISED DATABASE SCHEMA

**tenders** table — remove Arabic fields for Phase 1, add later:

- id (bigint)
- tender_number (string, unique index: source + source_id)
- title (string)
- description (text, nullable)
- organization_name (string)
- ministry (string, nullable)
- department (string, nullable)        # USA: agency hierarchy
- category (string, nullable)          # Goods/Works/Services/IT etc
- sector (string, nullable)            # NAICS code or sector name
- naics_code (string, nullable)        # USA SAM.gov specific
- cpv_code (string, nullable)          # UK/EU specific
- city (string, nullable)
- state_region (string, nullable)
- country (string)
- country_code (char 2)
- currency (char 3, default: USD)
- budget (decimal 15,2, nullable)
- tender_type (string)
- status (string, default: active)
- source (string)                      # ppra_federal, world_bank, uk_fts, sam_gov etc
- source_id (string)
- source_url (string)
- detail_url (string, nullable)
- pdf_urls (json, nullable)
- contact_email (string, nullable)     # very useful for users
- contact_phone (string, nullable)
- advertised_at (date)
- closing_at (datetime, nullable)
- tier (enum: free, starter, professional, enterprise)
- ai_summary (text, nullable)
- ai_eligibility (text, nullable)
- ai_budget_extracted (decimal 15,2, nullable)
- ai_recommendation (enum: Apply, Review, Skip, nullable)
- ai_key_requirements (json, nullable)
- ai_sector_tags (json, nullable)
- is_summarized (boolean, default: false)
- quality_score (tinyint, default: 0)
- view_count (int, default: 0)
- scraped_at (timestamp)
- created_at, updated_at

UNIQUE INDEX: (source, source_id)
INDEX: (country_code, status, closing_at)
INDEX: (tier, closing_at)
INDEX: (is_summarized, closing_at)
INDEX: (source, scraped_at)

**data_sources** seeder data:
[
  {slug: 'ppra_federal',  name: 'PPRA Pakistan',         country: 'PK', tier: 'free',         type: 'html'},
  {slug: 'world_bank',    name: 'World Bank',             country: 'WB', tier: 'professional', type: 'api'},
  {slug: 'uk_fts',        name: 'UK Find a Tender',       country: 'GB', tier: 'starter',      type: 'api'},
  {slug: 'uk_cf',         name: 'UK Contracts Finder',    country: 'GB', tier: 'starter',      type: 'api'},
  {slug: 'sam_gov',       name: 'USA SAM.gov',            country: 'US', tier: 'professional', type: 'api'},
  {slug: 'ungm',          name: 'UN Procurement',         country: 'UN', tier: 'professional', type: 'html'},
  {slug: 'adb',           name: 'Asian Dev Bank',         country: 'ADB',tier: 'professional', type: 'html'},
  {slug: 'afdb',          name: 'African Dev Bank',       country: 'AFB',tier: 'professional', type: 'rss'},
]

---

## SCRAPER IMPLEMENTATIONS

### WorldBankScraper (implement FIRST — easiest)

class WorldBankScraper extends ScraperService {
  
  private string $baseUrl = 'http://search.worldbank.org/api/procnotices';
  
  public function getSourceSlug(): string { return 'world_bank'; }
  
  public function fetchListingPage(int $page): Collection {
    $offset = ($page - 1) * 50;
    $url = "{$this->baseUrl}?format=json&rows=50&os={$offset}&strdate=" 
         . now()->subDays(30)->format('Y-m-d');
    
    $response = $this->httpGet($url);
    $data = json_decode($response->body(), true);
    
    return collect($data['procnotices'] ?? []);
  }
  
  public function getTotalPages(): int {
    $response = $this->httpGet("{$this->baseUrl}?format=json&rows=1");
    $data = json_decode($response->body(), true);
    return (int) ceil(($data['total'] ?? 0) / 50);
  }
  
  public function fetchTenderDetail(string $url): array {
    // World Bank detail is all in the listing — no separate call needed
    return [];
  }
  
  public function normalizeTender(array $raw): array {
    return [
      'tender_number'     => 'WB-' . ($raw['id'] ?? uniqid()),
      'title'             => $raw['project_name'] ?? 'Untitled',
      'description'       => $raw['procurement_method'] ?? null,
      'organization_name' => $raw['borrower'] ?? 'World Bank',
      'ministry'          => $raw['project_id'] ?? null,
      'category'          => 'Services',
      'sector'            => $raw['sector'] ?? null,
      'city'              => null,
      'country'           => $raw['country'] ?? 'Global',
      'country_code'      => $raw['countrycode'] ?? 'WB',
      'currency'          => 'USD',
      'budget'            => null,
      'tender_type'       => $raw['notice_type'] ?? 'Procurement Notice',
      'status'            => 'active',
      'source'            => 'world_bank',
      'source_id'         => $raw['id'] ?? null,
      'source_url'        => $raw['url'] ?? '',
      'detail_url'        => $raw['url'] ?? null,
      'pdf_urls'          => [],
      'contact_email'     => $raw['contact_email'] ?? null,
      'advertised_at'     => substr($raw['publication_date'] ?? now()->toDateString(), 0, 10),
      'closing_at'        => $raw['submission_date'] 
                             ? Carbon::parse($raw['submission_date'])->toDateTimeString() 
                             : null,
      'tier'              => 'professional',
    ];
  }
}

### UkFtsScraper

class UkFtsScraper extends ScraperService {
  
  private string $baseUrl = 'https://www.find-tender.service.gov.uk/api/1.0/ocdsReleasePackages';
  
  public function getSourceSlug(): string { return 'uk_fts'; }
  
  public function fetchListingPage(int $page): Collection {
    // FTS uses date-based pagination not page numbers
    // Fetch last 7 days on each run, dedup handles duplicates
    $from = now()->subDays(7)->format('Y-m-d');
    $to = now()->format('Y-m-d');
    
    $url = "{$this->baseUrl}?publishedFrom={$from}&publishedTo={$to}&stages=tender";
    $response = $this->httpGet($url);
    $data = json_decode($response->body(), true);
    
    return collect($data['releases'] ?? []);
  }
  
  public function getTotalPages(): int { return 1; } // date-based, single fetch
  
  public function fetchTenderDetail(string $url): array { return []; }
  
  public function normalizeTender(array $raw): array {
    $tender = $raw['tender'] ?? [];
    $buyer = $raw['buyer'] ?? [];
    $value = $tender['value'] ?? [];
    
    return [
      'tender_number'     => 'UK-' . ($raw['id'] ?? uniqid()),
      'title'             => $tender['title'] ?? 'Untitled',
      'description'       => strip_tags($tender['description'] ?? ''),
      'organization_name' => $buyer['name'] ?? 'UK Government',
      'ministry'          => null,
      'category'          => $this->mapCpvToCategory($tender['classification']['id'] ?? ''),
      'sector'            => $tender['classification']['description'] ?? null,
      'cpv_code'          => $tender['classification']['id'] ?? null,
      'city'              => $tender['deliveryLocation']['description'] ?? null,
      'country'           => 'United Kingdom',
      'country_code'      => 'GB',
      'currency'          => $value['currency'] ?? 'GBP',
      'budget'            => $value['amount'] ?? null,
      'tender_type'       => 'Tender Notice',
      'status'            => $tender['status'] ?? 'active',
      'source'            => 'uk_fts',
      'source_id'         => $raw['id'] ?? null,
      'source_url'        => 'https://www.find-tender.service.gov.uk/Notice/' . ($raw['id'] ?? ''),
      'detail_url'        => 'https://www.find-tender.service.gov.uk/Notice/' . ($raw['id'] ?? ''),
      'pdf_urls'          => [],
      'contact_email'     => $tender['contactPoint']['email'] ?? null,
      'advertised_at'     => substr($raw['date'] ?? now()->toDateString(), 0, 10),
      'closing_at'        => isset($tender['tenderPeriod']['endDate']) 
                             ? Carbon::parse($tender['tenderPeriod']['endDate'])->toDateTimeString()
                             : null,
      'tier'              => 'starter',
    ];
  }
  
  private function mapCpvToCategory(string $cpv): string {
    // CPV codes starting with:
    // 03-19: Goods
    // 45: Works (Construction)
    // 50-98: Services
    $prefix = (int) substr($cpv, 0, 2);
    if ($prefix === 45) return 'Works';
    if ($prefix >= 3 && $prefix <= 19) return 'Goods';
    return 'Services';
  }
}

### SamGovScraper

class SamGovScraper extends ScraperService {
  
  private string $baseUrl = 'https://api.sam.gov/prod/opportunities/v2/search';
  
  public function getSourceSlug(): string { return 'sam_gov'; }
  
  public function fetchListingPage(int $page): Collection {
    $from = now()->subDays(1)->format('m/d/Y'); // daily incremental
    $to = now()->format('m/d/Y');
    
    $url = $this->baseUrl . '?' . http_build_query([
      'api_key'    => config('scrapers.sam_gov_api_key'),
      'limit'      => 100,
      'offset'     => ($page - 1) * 100,
      'postedFrom' => $from,
      'postedTo'   => $to,
      'ptype'      => 'o',    // solicitations only
      'active'     => 'Yes',
    ]);
    
    $response = $this->httpGet($url);
    $data = json_decode($response->body(), true);
    
    return collect($data['opportunitiesData'] ?? []);
  }
  
  public function getTotalPages(): int {
    // SAM.gov returns totalRecords in response
    // Cache this after first call
    return (int) ceil(Cache::get('sam_gov_total', 100) / 100);
  }
  
  public function fetchTenderDetail(string $url): array {
    // SAM.gov description is truncated in list
    // Fetch full description from detail endpoint
    // GET https://api.sam.gov/prod/opportunities/v2/search?noticeid={id}&api_key=...
    return [];
  }
  
  public function normalizeTender(array $raw): array {
    $place = $raw['placeOfPerformance'] ?? [];
    
    return [
      'tender_number'     => 'US-' . ($raw['noticeId'] ?? uniqid()),
      'title'             => $raw['title'] ?? 'Untitled',
      'description'       => $raw['description'] ?? null,
      'organization_name' => $raw['fullParentPathName'] ?? ($raw['organizationHierarchy'][0]['name'] ?? 'US Government'),
      'ministry'          => $raw['organizationHierarchy'][1]['name'] ?? null,
      'department'        => $raw['fullParentPathName'] ?? null,
      'category'          => $this->mapType($raw['type'] ?? ''),
      'sector'            => $raw['naicsCode'] ?? null,
      'naics_code'        => $raw['naicsCode'] ?? null,
      'city'              => $place['city']['name'] ?? null,
      'state_region'      => $place['state']['name'] ?? null,
      'country'           => 'United States',
      'country_code'      => 'US',
      'currency'          => 'USD',
      'budget'            => null, // not always available at listing stage
      'tender_type'       => $raw['type'] ?? 'Solicitation',
      'status'            => 'active',
      'source'            => 'sam_gov',
      'source_id'         => $raw['noticeId'] ?? null,
      'source_url'        => $raw['uiLink'] ?? '',
      'detail_url'        => $raw['uiLink'] ?? null,
      'pdf_urls'          => [],
      'contact_email'     => $raw['pointOfContact'][0]['email'] ?? null,
      'contact_phone'     => $raw['pointOfContact'][0]['phone'] ?? null,
      'advertised_at'     => Carbon::parse($raw['postedDate'])->toDateString(),
      'closing_at'        => isset($raw['responseDeadLine']) 
                             ? Carbon::parse($raw['responseDeadLine'])->toDateTimeString()
                             : null,
      'tier'              => 'professional',
    ];
  }
  
  private function mapType(string $type): string {
    return match($type) {
      'o'    => 'Solicitation',
      'p'    => 'Pre-Solicitation',
      'k'    => 'Combined Synopsis',
      'r'    => 'Sources Sought',
      default => 'Opportunity',
    };
  }
}

### PpraFederalScraper

class PpraFederalScraper extends ScraperService {
  
  private string $baseUrl = 'https://epms.ppra.gov.pk/public/tenders/active-tenders';
  
  public function getSourceSlug(): string { return 'ppra_federal'; }
  
  public function fetchListingPage(int $page): Collection {
    $url = "{$this->baseUrl}?page={$page}&tender_type=1";
    $response = $this->httpGet($url);
    
    $crawler = new Crawler($response->body());
    $tenders = collect();
    
    $crawler->filter('table tbody tr')->each(function (Crawler $row) use ($tenders) {
      $tenders->push([
        'tender_number'  => trim($row->filter('td:nth-child(1)')->text('')),
        'title'          => trim($row->filter('td:nth-child(2)')->text('')),
        'organization'   => trim($row->filter('td:nth-child(3)')->text('')),
        'category'       => trim($row->filter('td:nth-child(4)')->text('')),
        'advertised_at'  => trim($row->filter('td:nth-child(5)')->text('')),
        'closing_at'     => trim($row->filter('td:nth-child(6)')->text('')),
        'status'         => trim($row->filter('td:nth-child(7)')->text('')),
        'detail_url'     => $row->filter('td a')->attr('href', ''),
      ]);
    });
    
    return $tenders;
  }
  
  public function getTotalPages(): int {
    $response = $this->httpGet($this->baseUrl . '?page=1&tender_type=1');
    $crawler = new Crawler($response->body());
    
    // Extract last page number from pagination
    $lastPage = 1;
    $crawler->filter('ul.pagination li a')->each(function (Crawler $link) use (&$lastPage) {
      if (is_numeric($link->text())) {
        $lastPage = max($lastPage, (int) $link->text());
      }
    });
    
    return $lastPage;
  }
  
  public function fetchTenderDetail(string $url): array {
    if (!$url) return [];
    $response = $this->httpGet('https://epms.ppra.gov.pk' . $url);
    $crawler = new Crawler($response->body());
    
    $pdfs = [];
    $crawler->filter('a[href$=".pdf"]')->each(function (Crawler $link) use (&$pdfs) {
      $pdfs[] = $link->attr('href');
    });
    
    return [
      'description' => trim($crawler->filter('.tender-description, .description')->text('')),
      'pdf_urls'    => $pdfs,
    ];
  }
  
  public function normalizeTender(array $raw): array {
    return [
      'tender_number'     => $raw['tender_number'] ?? ('PK-' . uniqid()),
      'title'             => $raw['title'],
      'description'       => $raw['description'] ?? null,
      'organization_name' => $raw['organization'],
      'category'          => $raw['category'],
      'country'           => 'Pakistan',
      'country_code'      => 'PK',
      'currency'          => 'PKR',
      'budget'            => null,
      'tender_type'       => 'Tender Notice',
      'status'            => strtolower($raw['status']) === 'active' ? 'active' : 'closed',
      'source'            => 'ppra_federal',
      'source_id'         => $raw['tender_number'],
      'source_url'        => 'https://epms.ppra.gov.pk/public/tenders/active-tenders',
      'detail_url'        => $raw['detail_url'] ? 'https://epms.ppra.gov.pk' . $raw['detail_url'] : null,
      'pdf_urls'          => $raw['pdf_urls'] ?? [],
      'advertised_at'     => Carbon::parse($raw['advertised_at'])->toDateString(),
      'closing_at'        => $raw['closing_at'] 
                             ? Carbon::parse($raw['closing_at'])->toDateTimeString() 
                             : null,
      'tier'              => 'free',
    ];
  }
}

---

## REVISED FRONTEND

### Homepage (/)
Hero headline: 
"Find Government Tenders from UK, USA & Global Markets"
Sub: "AI-powered summaries. Instant alerts. 10,000+ tenders from 
      USA SAM.gov, UK government, World Bank and Pakistan PPRA."

Stats bar: 
"10,000+ active tenders | USA + UK + World Bank + Pakistan | Updated daily"

Country showcase (4 cards):
┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐
│ 🇺🇸 USA  │ │ 🇬🇧 UK   │ │ 🌍 World │ │ 🇵🇰 PK   │
│ SAM.gov  │ │ Gov FTS  │ │  Bank    │ │  Free    │
│ $49/mo   │ │ $29/mo   │ │ $49/mo   │ │  $0      │
│ 2,000+   │ │ 1,000+   │ │  500+    │ │ 3,000+   │
│ /week    │ │ /week    │ │ /month   │ │ /month   │
└──────────┘ └──────────┘ └──────────┘ └──────────┘

Why TenderIQ section:
- "GovWin charges $2,000/month. We charge $49."
- "AI summarizes 40-page RFPs in 10 seconds."  
- "Set an alert, never check a portal again."

### Tender Listing (/tenders)
Country tabs:
[🌍 All] [🇺🇸 USA] [🇬🇧 UK] [🌐 World Bank] [🇵🇰 Pakistan] [🇺🇳 UN]

### Country SEO Pages
/tenders/usa          — "USA Federal Government Tenders (SAM.gov) 2026"
/tenders/uk           — "UK Government Tenders 2026"
/tenders/world-bank   — "World Bank Procurement Notices 2026"
/tenders/pakistan     — "Pakistan Government Tenders (PPRA) 2026"
/tenders/un           — "UN Procurement Opportunities 2026"

---

## REVISED POSITIONING

Primary headline: 
"The AI-powered alternative to GovWin — at 1/20th the price"

Target customers:
1. US federal contractors (SAM.gov users) — pay $49 vs $2000 for GovWin
2. UK SMEs bidding on government contracts — pay $29, currently check manually
3. International development consultants — World Bank/UN opportunities
4. Pakistani contractors — free tier, build brand loyalty

Marketing angles:
- "GovWin costs $2,000/month. TenderIQ costs $49."
- "Stop checking SAM.gov every morning."  
- "AI reads the 40-page RFP. You read the summary."
- "Get UK government tender alerts before your competitors."

---

## IMPLEMENTATION ORDER (REVISED)

### Week 1 — Data Foundation
1. Laravel + Inertia project setup
2. All database migrations
3. DataSource seeder (8 sources)
4. Abstract ScraperService base class
5. WorldBankScraper → run it, verify data flows into DB
6. PpraFederalScraper → run it, verify Pakistan data

### Week 2 — Paid Sources
7. UkFtsScraper (no auth needed, start immediately)
8. Register sam.gov API key (free, do Day 1 of Week 2)
9. SamGovScraper (implement while waiting for key approval)
10. UngmScraper (HTML, simple table)
11. AfdbScraper (RSS, 1 hour work)
12. AdbScraper (HTML scraping)

### Week 3 — AI + Product
13. SummarizeTender job (GPT-4o-mini)
14. ProcessAiBatch command
15. Basic frontend: listing + detail + search
16. Auth: register, login, email verify
17. Access control middleware (tier gating)

### Week 4 — Revenue
18. Stripe + Laravel Cashier integration
19. Pricing page
20. Email alerts (instant + digest)
21. Dashboard for logged-in users
22. Admin panel

### Post-Launch
23. SEO landing pages per country
24. Blog content (SEO)
25. UK Contracts Finder scraper
26. Phase 2: UAE (if budget for proxy/headless)
27. Phase 2: Saudi via TendersAlerts API

---

## ENV VARIABLES (REVISED)

APP_NAME=TenderIQ
APP_URL=https://tenderiq.com

DB_CONNECTION=mysql
DB_DATABASE=tenderiq_prod

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

OPENAI_API_KEY=
OPENAI_MODEL=gpt-4o-mini
AI_DAILY_SPEND_LIMIT=8

MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=

RESEND_API_KEY=
MAIL_FROM_ADDRESS=alerts@tenderiq.com

STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=

# SAM.gov — register free at sam.gov/api
SAM_GOV_API_KEY=

# No key needed for these:
WORLD_BANK_API_URL=http://search.worldbank.org/api/procnotices
UK_FTS_API_URL=https://www.find-tender.service.gov.uk/api/1.0/ocdsReleasePackages
UK_CF_API_URL=https://www.contractsfinder.service.gov.uk/Published/Notices/PublicSearch/Search

SCRAPER_DEFAULT_DELAY=2
SCRAPER_USE_PROXY=false

# Phase 2 only — not needed now
# TENDERS_ALERTS_API_KEY=   (Saudi Etimad)