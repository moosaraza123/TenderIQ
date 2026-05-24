You are building an AI-powered Pakistan Government Tender aggregation platform called [TenderRadar]. 

## Stack
- Laravel 11 (backend + routing)
- Inertia.js v2 with React (frontend)
- Tailwind CSS
- MySQL (primary database)
- Redis (queues + cache)
- Meilisearch (full-text search)
- OpenAI API (AI summaries)
- Laravel Horizon (queue monitoring)



---

## PHASE 1 SCOPE ONLY

### 1. DATABASE SCHEMA

Create migrations for the following tables:

**tenders**
- id
- tender_number (string, unique) — e.g. "TS0000005552E"
- title (string)
- description (text, nullable)
- organization_name (string)
- ministry (string, nullable)
- category (string, nullable) — Goods, Works, Consultancy, Non-consultancy
- sector (string, nullable) — Electrical, Civil Works, Health/Medicines etc.
- city (string, nullable)
- country (string, default: Pakistan)
- budget (decimal, nullable)
- tender_type (string) — Tender Notice, RFP, EOI, PQ
- status (string) — Published, Corrigendum, Cancelled
- advertised_at (date)
- closing_at (datetime)
- source_url (string) — original PPRA URL
- detail_url (string) — PPRA detail page URL
- pdf_urls (json, nullable) — array of attached PDF URLs
- ai_summary (text, nullable) — AI generated summary
- ai_eligibility (text, nullable) — extracted eligibility criteria
- ai_budget_extracted (decimal, nullable) — budget extracted from PDF
- ai_recommendation (string, nullable) — "Apply" or "Review" or "Skip"
- is_summarized (boolean, default false)
- scraped_at (timestamp)
- created_at, updated_at

**users**
- id, name, email, password (standard)
- company_name (string, nullable)
- phone (string, nullable)
- subscription_plan (enum: free, basic, pro, default: free)
- subscription_expires_at (datetime, nullable)
- email_verified_at
- created_at, updated_at

**alert_subscriptions**
- id
- user_id (foreign key)
- keywords (json) — array of keyword strings
- categories (json, nullable)
- cities (json, nullable)
- min_budget (decimal, nullable)
- max_budget (decimal, nullable)
- is_active (boolean, default true)
- last_triggered_at (timestamp, nullable)
- created_at, updated_at

**tender_views** (for analytics)
- id
- tender_id (foreign key)
- user_id (foreign key, nullable)
- ip_address (string)
- created_at

---

### 2. SCRAPER

Create a Laravel console command `php artisan scrape:ppra` that:

1. Scrapes https://epms.ppra.gov.pk/public/tenders/active-tenders paginated listing
   - Loops through all pages (?page=1, ?page=2 ... until no more results)
   - For each tender on listing page extracts:
     * tender_number
     * title
     * organization_name
     * ministry
     * category/sector
     * city
     * status (Published/Corrigendum/Cancelled)
     * advertised_at date
     * closing_at datetime
     * detail_url (link to tender detail page)

2. For each new tender (not already in DB by tender_number), visits the detail page and extracts:
   - Full description
   - Any PDF attachment URLs
   - Budget if visible in HTML

3. Respects rate limiting:
   - 2 second delay between requests
   - Random jitter (1-3 seconds)
   - User-agent rotation from a list of 5 common browser agents
   - Retry with exponential backoff on failure (max 3 retries)

4. Uses Laravel HTTP client (not Guzzle directly)

5. Dispatches a SummarizeTender job to queue for each new tender that has a PDF or description

6. Logs scraping results: how many scraped, how many new, how many failed

Schedule this command to run every 6 hours in the Laravel scheduler.

---

### 3. AI SUMMARIZATION JOB

Create a queued job `SummarizeTender` that:

1. Takes a tender_id
2. If tender has PDF URLs, downloads the first PDF
3. Extracts text from PDF (use pdftotext via shell command, or Smalot PDF parser)
4. Sends text (truncated to 6000 words) to OpenAI API (gpt-4o-mini model) with this system prompt:

"You are an expert in Pakistani government procurement. Analyze this tender document and extract:
1. SUMMARY: 3-4 sentence plain English summary of what is being procured
2. ELIGIBILITY: Key eligibility requirements (registration, experience, certifications)
3. BUDGET: Estimated budget/contract value if mentioned (in PKR)
4. DEADLINE: Submission deadline
5. RECOMMENDATION: One of 'Apply' (clear requirements, common goods/services), 'Review' (complex or specialized), or 'Skip' (very specialized or missing info)
6. KEY_REQUIREMENTS: 3-5 bullet points of most important requirements

Respond in JSON format only."

5. Parses the JSON response and updates the tender record:
   - ai_summary
   - ai_eligibility  
   - ai_budget_extracted
   - ai_recommendation
   - is_summarized = true

6. If no PDF, summarizes from title + description only

---

### 4. FRONTEND PAGES (Inertia + React)

#### 4a. Homepage (/)
- Hero section: headline "Find Pakistan Government Tenders Before Your Competitors"
- Live stats bar: "X active tenders | Updated every 6 hours"
- Search bar (prominent, centered)
- Recent tenders preview (latest 6, blurred/locked for non-logged-in users showing upgrade prompt)
- How it works section (3 steps)
- Pricing section (3 tiers)

#### 4b. Tender Listing Page (/tenders)
- Left sidebar filters:
  * Keyword search
  * Category (Goods/Works/Consultancy/Non-consultancy)
  * Sector (dropdown)
  * City (dropdown)
  * Status (Published/Corrigendum)
  * Closing date range
  * Tender type
- Main content: paginated tender cards (25 per page)
- Each tender card shows:
  * Tender number + title
  * Organization + Ministry
  * Category badge + Status badge (color coded)
  * City | Advertised date | Closing date (with countdown if < 7 days, shown in red)
  * AI Summary snippet (first 100 chars, blurred for free users after 5 views/day)
  * "View Details" button
- Sort options: Closing Soon, Newest, Relevance
- Meilisearch powers the keyword search with instant results

#### 4c. Tender Detail Page (/tenders/{tender_number})
- Full tender information
- AI Summary card (with lock icon for free users)
- Eligibility requirements (with lock for free users)
- AI Recommendation badge (Apply/Review/Skip with color coding)
- PDF attachments list with download links
- "Set Alert for Similar Tenders" button
- Share buttons
- SEO: meta title = "{tender_title} - {organization} Tender | [AppName]"
- SEO: meta description = AI summary first sentence

#### 4d. Auth Pages
- Register (/register): name, email, company, phone, password
- Login (/login)
- Email verification

#### 4e. Dashboard (/dashboard) — logged in users
- My saved tenders
- My alerts summary
- Recent activity
- Upgrade CTA for free users

#### 4f. Alerts Page (/alerts) — logged in users
- Create new alert form:
  * Keywords (tag input)
  * Category filter
  * City filter
  * Budget range
- List of existing alerts with toggle on/off + delete
- Free users: max 1 alert. Pro users: unlimited

---

### 5. FREE vs PAID LIMITS

Implement a middleware or service class `TenderAccessService` that enforces:

**Free users:**
- See tender listing (titles, org, dates only)
- 5 tender detail views per day (tracked by session/IP for guests, user_id for logged in)
- AI summary blurred/hidden
- Max 1 alert subscription
- No PDF downloads

**Basic plan ($19/month):**
- Unlimited tender views
- AI summaries visible
- Up to 5 alerts
- PDF downloads

**Pro plan ($49/month):**
- Everything in Basic
- Unlimited alerts
- Email alerts (instant)
- AI recommendation visible
- Export to CSV

---

### 6. EMAIL ALERTS

Create a queued job `SendTenderAlerts` that:
1. Runs after every scrape completes
2. For each new tender scraped:
   - Queries alert_subscriptions where is_active = true
   - Checks if tender matches: keywords (title/description LIKE match), category, city filters
   - Groups matching tenders per user
   - Sends one email per user with all matching tenders (not one email per tender)
3. Updates last_triggered_at on matched subscriptions

Create a Mailable `TenderAlertMail` with:
- Subject: "X new tenders match your alert"
- Clean HTML email listing matching tenders with title, org, closing date, and link
- Unsubscribe link

---

### 7. MEILISEARCH INTEGRATION

- Install Laravel Scout with Meilisearch driver
- Make Tender model searchable
- Index fields: title, description, organization_name, ministry, category, sector, city, tender_number
- Implement instant search on the listing page using Meilisearch JS (debounced 300ms)

---

### 8. SEO

- Each tender detail page has unique meta title and description
- Create a sitemap.xml route that lists all tender URLs (paginated, last 5000)
- Add robots.txt allowing all crawlers
- Use Inertia Head component for meta tags on every page

---

### 9. ADMIN

Create a simple admin panel at /admin (middleware: isAdmin role) with:
- Scraper status: last run time, total tenders, new today
- Manual trigger button for scraper
- Tender list with ability to manually mark as featured
- User list with subscription status
- Horizon link for queue monitoring

---

### 10. ENV VARIABLES NEEDED
OPENAI_API_KEY=
MEILISEARCH_HOST=
MEILISEARCH_KEY=
PPRA_SCRAPE_DELAY=2
SCRAPE_USER_AGENTS=["Mozilla/5.0...","Mozilla/5.0..."]

---

## IMPORTANT IMPLEMENTATION NOTES

1. All scraping runs through Laravel Queue (not synchronously)
2. Use database transactions when inserting tenders to avoid duplicates
3. Tender number is the unique identifier — always upsert on tender_number
4. PDF parsing is best-effort — if it fails, summarize from title+description
5. Cache the tender listing page for 15 minutes (Redis)
6. Rate limit the /tenders API to 60 requests/minute per IP
7. Mobile-first responsive design using Tailwind
8. Use Laravel Horizon for queue management
9. All money amounts in PKR internally, display with formatting
10. Closing date countdown should update client-side without page refresh

---

## FOLDER STRUCTURE EXPECTED
- app/Console/Commands/ScrapePpra.php
- app/Jobs/SummarizeTender.php
- app/Jobs/SendTenderAlerts.php
- app/Models/Tender.php
- app/Models/AlertSubscription.php
- app/Services/TenderAccessService.php
- app/Services/PpraScraperService.php
- app/Services/OpenAiService.php
- app/Http/Controllers/TenderController.php
- app/Http/Controllers/AlertController.php
- app/Mail/TenderAlertMail.php
- resources/js/Pages/Home.jsx
- resources/js/Pages/Tenders/Index.jsx
- resources/js/Pages/Tenders/Show.jsx
- resources/js/Pages/Dashboard.jsx
- resources/js/Pages/Alerts/Index.jsx
- resources/js/Components/TenderCard.jsx
- resources/js/Components/SearchBar.jsx
- resources/js/Components/FilterSidebar.jsx

Start by scaffolding the Laravel + Inertia project, then implement in this order:
1. Migrations
2. Models
3. Scraper command + service
4. AI summarization job
5. Frontend pages
6. Alert system
7. Access control
8. SEO + sitemap
9. Admin panel



---

### 11. ARCHITECTURE PRINCIPLES — MODULARITY & REUSABILITY

#### BACKEND: Module-based structure under app/Modules/

Organize all backend code into self-contained modules. Each module owns its
Models, Services, Jobs, Controllers, Requests, Mail, and Routes.

app/Modules/
├── Tender/
│   ├── Models/
│   │   └── Tender.php
│   ├── Services/
│   │   ├── TenderService.php          # CRUD, queries, filtering logic
│   │   ├── TenderAccessService.php    # Free/paid gate logic
│   │   └── TenderSearchService.php    # Meilisearch wrapper
│   ├── Jobs/
│   │   └── SummarizeTender.php
│   ├── Controllers/
│   │   └── TenderController.php
│   ├── Requests/
│   │   └── TenderFilterRequest.php
│   └── Routes/
│       └── tender.routes.php
│
├── Scraper/
│   ├── Services/
│   │   ├── ScraperService.php         # Abstract base scraper
│   │   ├── PpraScraperService.php     # Extends ScraperService (PPRA-specific)
│   │   └── PdfExtractorService.php    # PDF text extraction logic
│   ├── Jobs/
│   │   └── RunScraper.php
│   ├── Commands/
│   │   └── ScrapePpra.php
│   └── Routes/
│       └── scraper.routes.php
│
├── Alert/
│   ├── Models/
│   │   └── AlertSubscription.php
│   ├── Services/
│   │   └── AlertMatcherService.php    # Matches tenders to user alerts
│   ├── Jobs/
│   │   └── SendTenderAlerts.php
│   ├── Controllers/
│   │   └── AlertController.php
│   ├── Mail/
│   │   └── TenderAlertMail.php
│   ├── Requests/
│   │   └── CreateAlertRequest.php
│   └── Routes/
│       └── alert.routes.php
│
├── AI/
│   ├── Services/
│   │   ├── OpenAiService.php          # Raw OpenAI API wrapper
│   │   └── TenderAiService.php        # Tender-specific AI prompts & parsing
│   └── DTOs/
│       └── AiSummaryResult.php        # Typed DTO for AI response
│
├── User/
│   ├── Models/
│   │   └── User.php
│   ├── Services/
│   │   └── SubscriptionService.php    # Plan checks, upgrade logic
│   ├── Controllers/
│   │   └── UserController.php
│   └── Routes/
│       └── user.routes.php
│
└── Admin/
    ├── Services/
    │   └── AdminStatsService.php
    ├── Controllers/
    │   └── AdminController.php
    └── Routes/
        └── admin.routes.php

---

#### BACKEND RULES

1. **Controllers are thin** — only handle HTTP input/output. Zero business logic.
   Controllers call Services. Services call Models/Jobs/external APIs.

   // WRONG
   class TenderController {
     public function index() {
       $tenders = Tender::where('status', 'Published')
         ->where('closing_at', '>', now())
         ->orderBy('closing_at')
         ->paginate(25);
     }
   }

   // CORRECT
   class TenderController {
     public function index(TenderFilterRequest $request, TenderService $service) {
       $tenders = $service->getFilteredTenders($request->validated());
       return inertia('Tenders/Index', compact('tenders'));
     }
   }

2. **Services are the brain** — all business logic lives here. Services are
   injectable via constructor. Never instantiate services with `new` inside
   controllers — always use dependency injection.

3. **Use DTOs for structured data** — when passing data between layers
   (especially AI responses, scraper results), use simple readonly PHP classes:

   readonly class AiSummaryResult {
     public function __construct(
       public string $summary,
       public string $eligibility,
       public ?float $budget,
       public string $recommendation,
       public array $keyRequirements,
     ) {}
   }

4. **Abstract base scraper** — ScraperService defines the interface.
   Each source (PPRA, Punjab, Sindh etc.) implements it. This means adding
   a new scraper in Phase 2 requires only creating a new class, zero changes
   to existing code:

   abstract class ScraperService {
     abstract public function fetchListingPage(int $page): array;
     abstract public function fetchTenderDetail(string $url): array;
     abstract public function getSourceName(): string;
     abstract public function getTotalPages(): int;

     public function run(): ScraperResult {
       // shared pagination + rate limiting + logging logic here
       // calls fetchListingPage() and fetchTenderDetail() internally
     }
   }

   class PpraScraperService extends ScraperService {
     // PPRA-specific selectors and parsing only
   }

   // Phase 2: just add this, nothing else changes
   class PunjabEpadsScraperService extends ScraperService {
     // Punjab-specific selectors and parsing only  
   }

5. **Route files per module** — each module registers its own routes.
   In bootstrap/app.php or RouteServiceProvider, auto-load all module route files:

   foreach (glob(app_path('Modules/*/Routes/*.php')) as $routeFile) {
     require $routeFile;
   }

6. **Use Form Request classes** — never validate directly in controllers.
   Every form submission or filter query has its own Request class with rules().

---

#### FRONTEND: Component-based structure under resources/js/

resources/js/
├── Pages/
│   ├── Home.jsx
│   ├── Tenders/
│   │   ├── Index.jsx
│   │   └── Show.jsx
│   ├── Alerts/
│   │   └── Index.jsx
│   └── Dashboard.jsx
│
├── Components/
│   ├── Tender/
│   │   ├── TenderCard.jsx             # Used on listing + dashboard
│   │   ├── TenderBadge.jsx            # Status/category colored badge
│   │   ├── TenderDeadlineTimer.jsx    # Countdown component
│   │   ├── TenderAiSummary.jsx        # Summary with blur/lock state
│   │   └── TenderPdfList.jsx          # PDF download list
│   ├── Search/
│   │   ├── SearchBar.jsx              # Instant search input
│   │   └── FilterSidebar.jsx          # All filter controls
│   ├── Alert/
│   │   ├── AlertForm.jsx              # Create/edit alert
│   │   └── AlertCard.jsx              # Alert list item with toggle
│   ├── UI/
│   │   ├── Badge.jsx                  # Generic reusable badge
│   │   ├── Card.jsx                   # Generic card wrapper
│   │   ├── Modal.jsx                  # Generic modal
│   │   ├── Pagination.jsx             # Reusable pagination
│   │   ├── EmptyState.jsx             # Empty results state
│   │   ├── LoadingSkeleton.jsx        # Loading placeholders
│   │   └── LockedOverlay.jsx          # Blur + upgrade CTA overlay
│   └── Layout/
│       ├── AppLayout.jsx              # Main layout with nav + footer
│       ├── Navbar.jsx
│       └── Footer.jsx
│
├── hooks/
│   ├── useTenderSearch.js             # Search state + debounce logic
│   ├── useFilters.js                  # Filter state management
│   ├── useCountdown.js                # Deadline countdown logic
│   └── useSubscription.js            # Check user plan, gate features
│
├── lib/
│   ├── api.js                         # Inertia router helpers
│   ├── formatters.js                  # Date, currency, text formatters
│   └── constants.js                  # CATEGORIES, SECTORS, CITIES arrays
│
└── Layouts/
    └── AppLayout.jsx

---

#### FRONTEND RULES

1. **Pages are thin** — Pages only compose components and pass props from
   Inertia. No business logic, no direct API calls, no formatting inside Pages.

   // WRONG — logic inside Page
   export default function Index({ tenders }) {
     const filtered = tenders.filter(t => t.status === 'Published');
     const formatted = filtered.map(t => ({
       ...t,
       closing: new Date(t.closing_at).toLocaleDateString('en-PK')
     }));
     return <div>{formatted.map(...)}</div>
   }

   // CORRECT — Page just composes
   export default function Index({ tenders, filters }) {
     return (
       <AppLayout>
         <FilterSidebar filters={filters} />
         <div>
           {tenders.data.map(tender => (
             <TenderCard key={tender.id} tender={tender} />
           ))}
           <Pagination meta={tenders.meta} />
         </div>
       </AppLayout>
     );
   }

2. **All formatting in lib/formatters.js** — dates, currency, truncation.
   Import and use everywhere consistently:

   // lib/formatters.js
   export const formatDate = (date) => ...
   export const formatPKR = (amount) => ...
   export const truncate = (text, length) => ...
   export const daysUntil = (date) => ...

3. **All constants in lib/constants.js** — categories, sectors, cities,
   plan limits. Never hardcode these in components:

   export const CATEGORIES = ['Goods', 'Works', 'Consultancy Services', ...]
   export const PLAN_LIMITS = {
     free: { dailyViews: 5, alerts: 1 },
     basic: { dailyViews: Infinity, alerts: 5 },
     pro: { dailyViews: Infinity, alerts: Infinity }
   }

4. **Custom hooks for all stateful logic** — search, filters, countdown,
   subscription checks. Components stay purely presentational:

   // hooks/useTenderSearch.js
   export function useTenderSearch(initialFilters) {
     const [filters, setFilters] = useState(initialFilters);
     const debouncedSearch = useDebounce(filters.keyword, 300);
     // handles Inertia router.get with filter params
     // returns: { filters, setFilter, isSearching, reset }
   }

5. **LockedOverlay is a wrapper component** — apply it around any
   content that requires a paid plan:

   <LockedOverlay
     isLocked={!user.canViewSummary}
     message="Upgrade to see AI summaries"
     plan="basic"
   >
     <TenderAiSummary summary={tender.ai_summary} />
   </LockedOverlay>

6. **TenderCard is the single source of truth** for how a tender looks
   in any listing context (search results, dashboard, alerts email preview).
   It accepts a tender prop and renders consistently everywhere.

---

#### SHARED LOGIC BETWEEN FRONTEND AND BACKEND

1. **Tender status colors** — define once in constants.js (frontend) and
   in a TenderPresenter or enum (backend). Never define colors in multiple places.

2. **Filter parameters** — the exact same keys used in TenderFilterRequest
   (backend) must match what FilterSidebar sends via Inertia router (frontend).
   Document the contract:

   Accepted filter params:
   - keyword (string)
   - category (string)
   - sector (string)
   - city (string)
   - status (string)
   - closing_from (date Y-m-d)
   - closing_to (date Y-m-d)
   - tender_type (string)
   - sort (string: closing_soon|newest|relevance)

3. **Pagination** — always return paginated results from Laravel in the
   standard format { data: [], meta: { current_page, last_page, total } }.
   The frontend Pagination component reads this exact shape everywhere.

   ---

### 12. DESIGN LANGUAGE & UI/UX SYSTEM

#### BRAND IDENTITY
- Name feel: Professional, trustworthy, modern government-tech
- Personality: Clean, data-dense but not overwhelming, confident
- Inspired by: Linear.app (clean), Notion (typography), 
  Stripe (trust + polish), Dawn (Pakistani context warmth)

---

#### COLOR SYSTEM

Use these exact CSS custom properties in tailwind.config.js:

module.exports = {
  theme: {
    extend: {
      colors: {
        // Primary — Deep Teal (trust, government, professional)
        primary: {
          50:  '#f0fdfa',
          100: '#ccfbf1',
          200: '#99f6e4',
          300: '#5eead4',
          400: '#2dd4bf',
          500: '#14b8a6',  // main brand color
          600: '#0d9488',  // hover state
          700: '#0f766e',  // active/pressed
          800: '#115e59',
          900: '#134e4a',
        },
        // Accent — Warm Amber (CTAs, highlights, urgency)
        accent: {
          50:  '#fffbeb',
          100: '#fef3c7',
          400: '#fbbf24',
          500: '#f59e0b',  // main accent
          600: '#d97706',  // hover
        },
        // Surface — Cool Slate (backgrounds, cards)
        surface: {
          50:  '#f8fafc',  // page background
          100: '#f1f5f9',  // card background
          200: '#e2e8f0',  // borders
          300: '#cbd5e1',  // dividers
          700: '#334155',  // secondary text
          800: '#1e293b',  // primary text
          900: '#0f172a',  // headings
        },
        // Status colors
        status: {
          published: '#10b981',   // emerald
          corrigendum: '#f59e0b', // amber
          cancelled: '#ef4444',   // red
          closing: '#ef4444',     // closing soon (< 3 days)
          apply: '#10b981',       // AI says apply
          review: '#f59e0b',      // AI says review
          skip: '#94a3b8',        // AI says skip
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        mono: ['JetBrains Mono', 'monospace'],
      },
      fontSize: {
        'tender-title': ['1.0625rem', { lineHeight: '1.5', fontWeight: '600' }],
      },
      boxShadow: {
        'card': '0 1px 3px 0 rgb(0 0 0 / 0.04), 0 1px 2px -1px rgb(0 0 0 / 0.04)',
        'card-hover': '0 4px 12px 0 rgb(0 0 0 / 0.08)',
        'card-focus': '0 0 0 3px rgb(20 184 166 / 0.15)',
      },
      borderRadius: {
        'card': '12px',
        'badge': '6px',
        'button': '8px',
      }
    }
  }
}

---

#### TYPOGRAPHY SCALE

Install Inter from Google Fonts. Use this hierarchy consistently:

Page title (h1):        text-3xl font-bold text-surface-900 tracking-tight
Section heading (h2):   text-xl font-semibold text-surface-800
Card title:             text-[tender-title] text-surface-900
Body text:              text-sm text-surface-700 leading-relaxed
Caption/meta:           text-xs text-surface-400 font-medium
Badge text:             text-xs font-semibold uppercase tracking-wide

---

#### COMPONENT DESIGN SPECS

**TenderCard**
- Background: white
- Border: 1px solid surface-200
- Border-radius: card (12px)
- Shadow: shadow-card
- Hover: shadow-card-hover + border-primary-200 + translateY(-1px)
- Transition: all 200ms ease
- Padding: p-5
- Layout:

┌─────────────────────────────────────────────────┐
│ [Category Badge] [Status Badge]      [Deadline] │
│                                                  │
│ Tender Title (2 lines max, ellipsis)             │
│ Organization Name · Ministry                     │
│                                                  │
│ AI Summary snippet (blurred if locked)           │
│                                                  │
│ [City icon + city] [PKR budget if known]         │
│                           [View Details →]       │
└─────────────────────────────────────────────────┘

**Badges**
- Published:     bg-emerald-50  text-emerald-700  border border-emerald-200
- Corrigendum:   bg-amber-50    text-amber-700    border border-amber-200
- Cancelled:     bg-red-50      text-red-600      border border-red-200
- Goods:         bg-blue-50     text-blue-700     border border-blue-200
- Works:         bg-orange-50   text-orange-700   border border-orange-200
- Consultancy:   bg-purple-50   text-purple-700   border border-purple-200
- APPLY badge:   bg-emerald-500 text-white        (solid, prominent)
- REVIEW badge:  bg-amber-500   text-white        (solid)
- SKIP badge:    bg-slate-200   text-slate-600    (muted)

All badges: px-2.5 py-0.5 rounded-badge text-xs font-semibold

**Deadline Countdown**
- > 14 days:  text-surface-400 (neutral)
- 7-14 days:  text-amber-600   (caution)
- 3-7 days:   text-orange-500  (warning)
- < 3 days:   text-red-600 font-bold animate-pulse (urgent)
- Format: "Closes in 4d 6h" not just a date

**Buttons**
Primary:   bg-primary-500 hover:bg-primary-600 text-white 
           px-4 py-2 rounded-button font-medium text-sm
           shadow-sm hover:shadow transition-all duration-150

Secondary: bg-white border border-surface-200 text-surface-700
           hover:bg-surface-50 hover:border-surface-300
           px-4 py-2 rounded-button font-medium text-sm

Danger:    bg-red-50 border border-red-200 text-red-600
           hover:bg-red-100

Ghost:     text-primary-600 hover:bg-primary-50
           px-4 py-2 rounded-button font-medium text-sm

**Search Bar (hero)**
- Height: h-14
- Border-radius: rounded-2xl
- Border: 2px solid surface-200
- Focus: border-primary-400 ring-4 ring-primary-500/10
- Shadow: shadow-lg
- Placeholder color: surface-300
- Search icon: left-4, text-surface-400
- Search button inside right side: bg-primary-500 text-white
  rounded-xl px-5 py-2 mr-1.5 my-1.5

**FilterSidebar**
- Background: white
- Border-right: 1px solid surface-200
- Width: w-64 (desktop), full-width drawer on mobile
- Section headings: text-xs uppercase tracking-widest text-surface-400
  font-semibold mb-2
- Filter items: checkboxes with custom styled boxes (primary-500 when checked)
- Active filter count badge on mobile toggle button

**LockedOverlay**
- Blur the content underneath: filter blur-sm pointer-events-none
- Overlay: absolute inset-0 bg-gradient-to-b from-transparent to-white/90
- Center card: white card with lock icon, message, upgrade CTA button
- Lock icon: outline style, text-surface-300, size 20px

---

#### PAGE LAYOUTS

**Homepage**
- Navbar: sticky, backdrop-blur-md bg-white/80, border-b border-surface-100
- Hero: min-h-[480px] bg-gradient-to-br from-surface-50 via-white to-primary-50/30
  with subtle dot grid pattern background (CSS, not image)
- Stats bar: bg-primary-500/5 border-y border-primary-100 py-3
  Shows: "1,536 active tenders · Updated 2 hours ago · 300+ organizations"
- Sections: max-w-7xl mx-auto px-4 sm:px-6 lg:px-8

**Tender Listing Page**
- Layout: sidebar (w-64) + main content, gap-6
- Sticky sidebar on scroll (position: sticky, top: 80px)
- Main: flex-1, min-w-0
- Tender grid: single column, gap-3 (cards are wide, not grid)
- Top bar: "Showing 1,536 tenders" + sort dropdown (right-aligned)
- Pagination: centered, previous/next + page numbers

**Tender Detail Page**
- Max width: max-w-4xl (narrower for readability)
- Layout: main content (2/3) + sidebar (1/3) on desktop
- Sidebar: sticky, contains deadline countdown, quick facts, PDF list
- Breadcrumb: Home > Tenders > [Tender Number]
- Back button: top-left, ghost style

**Dashboard**
- Greeting: "Good morning, [name]" with date
- Stats cards row: 4 cards (saved tenders, alerts active, tenders closing soon, viewed today)
- Each stat card: white, shadow-card, p-5, icon + number + label

---

#### MICRO-INTERACTIONS & ANIMATIONS

Use these sparingly and purposefully:

// tailwind.config.js animations section
animation: {
  'fade-in': 'fadeIn 200ms ease-out',
  'slide-up': 'slideUp 250ms ease-out',
  'pulse-soft': 'pulseSoft 2s ease-in-out infinite',
}

- Tender cards: fade-in on list load (stagger each card by 30ms)
- Search results: slide-up when new results appear
- Badge pulse: only on "closing soon" deadlines
- Skeleton loader: animated shimmer while tenders load
- Page transitions: Inertia progress bar (primary-500 color)
- Filter apply: subtle loading spinner on results area only (not full page)

---

#### EMPTY & LOADING STATES

Every list must have designed empty + loading states:

LoadingSkeleton (TenderCard shape):
- Animate shimmer: bg-gradient-to-r from-surface-100 via-surface-50 to-surface-100
- Show 5 skeleton cards on initial load

EmptyState:
- Centered, icon (outline style, text-surface-300, 48px)
- Heading: "No tenders found" text-surface-800 font-semibold
- Sub: "Try adjusting your filters or search term" text-surface-400
- Action button: "Clear filters" ghost style

---

#### MOBILE UX

- FilterSidebar becomes a bottom drawer on mobile (slide up)
- Trigger: "Filters (3)" button fixed at bottom of screen
- TenderCard: same design, full width, slightly reduced padding (p-4)
- Search bar: full width, simplified (no inline button, separate search button below)
- Navbar: hamburger menu, logo centered
- Detail page: sidebar moves below main content on mobile
- Bottom navigation bar (mobile only):
  Home | Search | Alerts | Dashboard
  Icons + labels, active state: primary-500

---

#### ICONOGRAPHY

Use Lucide React exclusively (already available in your stack).
Icon sizes: 
- Navigation: 20px
- Card icons: 16px  
- Empty states: 48px
- Badges: 12px

Common icons to use:
- Tender/document: FileText
- Organization: Building2
- Location: MapPin
- Deadline/time: Clock
- Alert/notification: Bell
- AI summary: Sparkles
- Lock/paywall: Lock
- Download PDF: Download
- Apply recommendation: CheckCircle2
- Review recommendation: AlertCircle
- Skip recommendation: MinusCircle
- Search: Search
- Filter: SlidersHorizontal
- Dashboard: LayoutDashboard

---

#### WHAT TO AVOID

- No gradients on buttons (flat colors only)
- No heavy drop shadows (keep shadows subtle, surface-level)
- No more than 2 font weights in any single component
- No red/green as primary brand colors (reserved for status only)
- No full-page loading spinners (use skeleton loaders instead)
- No modal overuse — filters go in sidebar, not modals
- No tooltips on mobile
- No horizontal scroll on any viewport
- No more than 3 colors in any single component
- No uppercase body text (only badges and section labels)