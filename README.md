# TenderIQ

**TenderIQ** is a government tender aggregation platform covering Pakistan, UK, USA, and international procurement sources. It aggregates tenders from 16+ sources, generates AI-powered summaries, and provides email alerts — all behind a 4-tier subscription model.

---

## Features

- **Multi-source scraping** — 16 scrapers across Pakistan (PPRA, KPPRA, BPPRA, SPPRA), UK (FTS, Contracts Finder), USA (SAM.gov), and International (World Bank, UNGM, ADB, AfDB, Saudi Etimad, UAE portals)
- **AI summaries** — GPT-4o-mini generates structured summaries, eligibility requirements, budget extraction, and bid recommendations
- **Smart alerts** — keyword/category/country filters with instant, daily, or weekly email digest delivery and webhook support
- **CSV export** — download filtered tender lists for Starter+ users
- **REST API** — token-authenticated JSON API for Enterprise users (1,000 calls/day)
- **Subscription billing** — Stripe Checkout with 4 tiers (Free / Starter $29 / Professional $49 / Enterprise $99)
- **Admin dashboard** — scraper logs, user management, tender moderation
- **Duplicate detection** — cross-source dedup by title similarity + organization + closing date
- **SEO landing pages** — `/tenders/pakistan`, `/tenders/uae`, `/tenders/saudi`, XML sitemap

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3, Laravel 13 |
| Frontend | React 19, Inertia.js v3, Tailwind CSS v3 |
| Build | Vite 8 |
| Database | MySQL / SQLite |
| Cache & Queue | Redis |
| Payments | Laravel Cashier 16 (Stripe) |
| Email | Resend (via Laravel Mail) |
| AI | OpenAI GPT-4o-mini |
| Search | Laravel Scout (Meilisearch-ready, null driver by default) |

---

## Subscription Plans

| Plan | Price | Tenders | AI Summaries | Alerts | CSV | API | Webhooks |
|---|---|---|---|---|---|---|---|
| Free | $0 | Pakistan only (5 views/day) | — | 1 (daily) | — | — | — |
| Starter | $29/mo | Pakistan + UK | Yes | 5 (instant) | Yes | — | — |
| Professional | $49/mo | + USA + International | Yes | 20 (instant) | Yes | — | — |
| Enterprise | $99/mo | All sources | Yes | Unlimited (instant) | Yes | 1,000/day | Yes |

---

## Data Sources

| Source | Country | Slug | Tier |
|---|---|---|---|
| PPRA Federal | Pakistan | `ppra_federal` | Free |
| KPPRA (KPK) | Pakistan | `kppra` | Free |
| BPPRA (Balochistan) | Pakistan | `bppra` | Free |
| SPPRA (Sindh) | Pakistan | `sppra` | Free |
| UK Find a Tender | UK | `uk_fts` | Starter+ |
| UK Contracts Finder | UK | `uk_cf` | Starter+ |
| SAM.gov | USA | `sam_gov` | Professional+ |
| World Bank | International | `world_bank` | Professional+ |
| UNGM (UN) | International | `ungm` | Professional+ |
| ADB | International | `adb` | Professional+ |
| AfDB | International | `afdb` | Professional+ |
| Etimad | Saudi Arabia | `etimad` | Enterprise |
| ADGPG | UAE | `adgpg` | Enterprise |
| DEWA | UAE | `dewa` | Enterprise |
| Dubai RTA | UAE | `dubai_rta` | Enterprise |
| Dubai eSUPPLY | UAE | `dubai_esupply` | Enterprise |

---

## Project Structure

```
app/
├── Modules/
│   ├── AI/              # GPT summaries, translation, batch processor
│   ├── Admin/           # Admin dashboard controller + stats
│   ├── Alert/           # Alert CRUD, matcher, email jobs, digest
│   ├── Api/             # REST API controller, token auth middleware
│   ├── Payment/         # Stripe checkout, webhook handler, subscription service
│   ├── Scraper/         # Base scraper, orchestrator, 16 source scrapers
│   ├── Tender/          # Tender model, controller, access control, search
│   └── User/            # User model, profile controller
resources/js/
├── Components/
│   ├── Alert/           # AlertCard, AlertForm
│   ├── Layout/          # Navbar, Footer
│   ├── Payment/         # PricingCard, UpgradeModal, SubscriptionCard
│   ├── Search/          # SearchBar, FilterSidebar, CountryTabs
│   ├── Tender/          # TenderCard, TenderBadge, TenderAiSummary, etc.
│   └── UI/              # LockedOverlay, CurrencyDisplay, EmptyState, etc.
├── Pages/
│   ├── Auth/            # Login, Register, VerifyEmail
│   ├── Tenders/         # Index (browse), Show (detail)
│   ├── Alerts/          # Alert management
│   ├── Countries/       # UAE, Saudi, Pakistan SEO pages
│   ├── Api/             # API token management
│   └── Pricing.jsx      # Pricing page with Stripe CTA
└── hooks/               # useSubscription, useTenderSearch, useCountdown
```

---

## Installation

### Prerequisites

- PHP 8.3+
- Node.js 20+
- Redis
- MySQL 8+ (or SQLite for local dev)
- Composer

### Setup

```bash
# Clone the repository
git clone https://github.com/moosaraza123/TenderIQ.git
cd TenderIQ

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Edit .env with your DB, Redis, Stripe, OpenAI, and mail credentials

# Run migrations
php artisan migrate

# Seed data sources
php artisan db:seed --class=DataSourceSeeder

# Build frontend assets
npm run build

# Create storage symlink
php artisan storage:link
```

### Environment Variables

Key variables to set in `.env`:

```env
APP_NAME=TenderIQ
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tenderiq
DB_USERNAME=root
DB_PASSWORD=

# Redis (cache + queue)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis

# OpenAI
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
AI_DAILY_SPEND_LIMIT=8

# Stripe
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_STARTER_PRICE_ID=price_...
STRIPE_PROFESSIONAL_PRICE_ID=price_...
STRIPE_ENTERPRISE_PRICE_ID=price_...

# Email (Resend)
MAIL_MAILER=resend
RESEND_API_KEY=re_...
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME=TenderIQ

# Scraper
SCRAPER_DEFAULT_DELAY=2
```

---

## Running the Application

```bash
# Development server
php artisan serve
npm run dev

# Queue worker — required for alerts and AI jobs
php artisan queue:work --queue=default

# Scheduler — scraping and daily digest
php artisan schedule:work
```

---

## Artisan Commands

```bash
# Scrape all active sources
php artisan scrape:all

# Scrape a specific source
php artisan scrape:source ppra_federal
php artisan scrape:source world_bank
php artisan scrape:source kppra

# Process AI summaries (runs automatically every 30 min)
php artisan ai:process-batch

# Send daily alert digest manually
php artisan alerts:send-daily
```

---

## Scheduler

Configured in `routes/console.php` — run `php artisan schedule:work` to activate:

| Task | Frequency |
|---|---|
| `scrape:all` | Every 6 hours |
| `scrape:source world_bank` | Every 4 hours |
| `ai:process-batch` | Every 30 minutes |
| `alerts:send-daily` | Daily at 08:00 |
| `alerts:send-weekly` | Monday at 08:00 |

---

## API Reference

Enterprise users can authenticate with a Bearer token generated at `/api-access`:

```bash
# List tenders
GET /api/v1/tenders
Authorization: Bearer <your-token>

# Get a single tender
GET /api/v1/tenders/{tender_number}
Authorization: Bearer <your-token>
```

Rate limit: 1,000 calls/day per token.

---

## Stripe Webhook Setup

Point your Stripe webhook endpoint to:

```
POST https://yourdomain.com/stripe/webhook
```

Events handled: `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`, `invoice.payment_succeeded`, `invoice.payment_failed`

---

## Deployment Notes

- Set `APP_ENV=production` and `APP_DEBUG=false`
- Run `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- Use Supervisor to keep `queue:work` and `schedule:work` running as persistent processes
- Verify your sending domain in Resend for production email delivery
- Set `SESSION_DRIVER=redis` in production for better performance

---

## License

MIT
