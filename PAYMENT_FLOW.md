# TenderIQ — Complete Payment Flow
## Claude Code Implementation Prompt

---

## OVERVIEW

Implement complete Stripe subscription payment system for TenderIQ.

**Stack:** Laravel 11 + Inertia.js + React + Stripe + Laravel Cashier

**Plans:**
| Plan | Price | Access |
|---|---|---|
| Free | $0 | Pakistan PPRA only |
| Starter | $29/month | + UK tenders |
| Professional | $49/month | + USA + World Bank + UN + ADB |
| Enterprise | $99/month | Everything + API access |

---

## STEP 1 — INSTALL DEPENDENCIES

```bash
composer require laravel/cashier
php artisan vendor:publish --tag="cashier-migrations"
php artisan migrate
```

Add `Billable` trait to User model:

```php
// app/Models/User.php
use Laravel\Cashier\Billable;

class User extends Authenticatable {
  use Billable;
  // ...
}
```

Add to `bootstrap/app.php`:

```php
Cashier::calculateTaxes();
```

---

## STEP 2 — ENVIRONMENT VARIABLES

Add to `.env`:

```env
# Stripe Keys
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...

# Stripe Price IDs (create these in Stripe Dashboard first)
STRIPE_STARTER_PRICE_ID=price_...
STRIPE_PROFESSIONAL_PRICE_ID=price_...
STRIPE_ENTERPRISE_PRICE_ID=price_...

# Cashier Settings
CASHIER_CURRENCY=usd
CASHIER_CURRENCY_LOCALE=en
```

---

## STEP 3 — DATABASE MIGRATION

Create migration: `add_subscription_fields_to_users_table`

```php
Schema::table('users', function (Blueprint $table) {
  $table->string('subscription_plan')
        ->default('free')
        ->after('email');

  $table->timestamp('trial_ends_at')
        ->nullable()
        ->after('subscription_plan');

  $table->integer('api_calls_today')
        ->default(0)
        ->after('trial_ends_at');

  $table->timestamp('api_calls_reset_at')
        ->nullable()
        ->after('api_calls_today');
});
```

---

## STEP 4 — PLAN CONFIGURATION

Create `config/plans.php`:

```php
<?php
return [

  'free' => [
    'name'            => 'Free',
    'price'           => 0,
    'stripe_price_id' => null,
    'color'           => 'slate',
    'badge'           => 'Free Forever',
    'features' => [
      'daily_views'     => 5,
      'alerts'          => 1,
      'alert_frequency' => 'daily',
      'api_calls'       => 0,
      'csv_export'      => false,
      'ai_summaries'    => false,
      'webhooks'        => false,
    ],
    'sources'   => ['ppra_federal'],
    'countries' => ['PK'],
  ],

  'starter' => [
    'name'            => 'Starter',
    'price'           => 29,
    'stripe_price_id' => env('STRIPE_STARTER_PRICE_ID'),
    'color'           => 'teal',
    'badge'           => 'Most Popular',
    'features' => [
      'daily_views'     => PHP_INT_MAX,
      'alerts'          => 5,
      'alert_frequency' => 'instant',
      'api_calls'       => 0,
      'csv_export'      => true,
      'ai_summaries'    => true,
      'webhooks'        => false,
    ],
    'sources'   => ['ppra_federal', 'uk_fts', 'uk_cf'],
    'countries' => ['PK', 'GB'],
  ],

  'professional' => [
    'name'            => 'Professional',
    'price'           => 49,
    'stripe_price_id' => env('STRIPE_PROFESSIONAL_PRICE_ID'),
    'color'           => 'blue',
    'badge'           => 'Best Value',
    'features' => [
      'daily_views'     => PHP_INT_MAX,
      'alerts'          => 20,
      'alert_frequency' => 'instant',
      'api_calls'       => 0,
      'csv_export'      => true,
      'ai_summaries'    => true,
      'webhooks'        => false,
    ],
    'sources'   => [
      'ppra_federal', 'uk_fts', 'uk_cf',
      'sam_gov', 'world_bank', 'ungm', 'adb', 'afdb'
    ],
    'countries' => ['PK', 'GB', 'US', 'WB', 'UN'],
  ],

  'enterprise' => [
    'name'            => 'Enterprise',
    'price'           => 99,
    'stripe_price_id' => env('STRIPE_ENTERPRISE_PRICE_ID'),
    'color'           => 'purple',
    'badge'           => 'Full Access',
    'features' => [
      'daily_views'     => PHP_INT_MAX,
      'alerts'          => PHP_INT_MAX,
      'alert_frequency' => 'instant',
      'api_calls'       => 1000,
      'csv_export'      => true,
      'ai_summaries'    => true,
      'webhooks'        => true,
    ],
    'sources'   => ['*'],
    'countries' => ['*'],
  ],

];
```

---

## STEP 5 — SUBSCRIPTION SERVICE

Create `app/Modules/Payment/Services/SubscriptionService.php`:

```php
<?php
namespace App\Modules\Payment\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SubscriptionService
{
  // Get current plan slug for user
  public function getUserPlan(User $user): string
  {
    if ($user->subscribed('default')) {
      $priceId = $user->subscription('default')->stripe_price;
      return match($priceId) {
        config('plans.starter.stripe_price_id')      => 'starter',
        config('plans.professional.stripe_price_id') => 'professional',
        config('plans.enterprise.stripe_price_id')   => 'enterprise',
        default => 'free',
      };
    }
    return 'free';
  }

  // Check if user can access a specific data source
  public function canAccessSource(User $user, string $source): bool
  {
    $plan  = $this->getUserPlan($user);
    $allowed = config("plans.{$plan}.sources", []);
    if ($allowed === ['*']) return true;
    return in_array($source, $allowed);
  }

  // Check if user can access a specific country
  public function canAccessCountry(User $user, string $countryCode): bool
  {
    if ($countryCode === 'PK') return true; // always free
    $plan    = $this->getUserPlan($user);
    $allowed = config("plans.{$plan}.countries", ['PK']);
    if ($allowed === ['*']) return true;
    return in_array($countryCode, $allowed);
  }

  // AI summaries gate
  public function canViewAiSummary(User $user): bool
  {
    $plan = $this->getUserPlan($user);
    return config("plans.{$plan}.features.ai_summaries", false);
  }

  // CSV export gate
  public function canExportCsv(User $user): bool
  {
    $plan = $this->getUserPlan($user);
    return config("plans.{$plan}.features.csv_export", false);
  }

  // Webhook gate
  public function canUseWebhooks(User $user): bool
  {
    $plan = $this->getUserPlan($user);
    return config("plans.{$plan}.features.webhooks", false);
  }

  // Daily view tracking for free users
  public function getRemainingDailyViews(User $user): int
  {
    $plan  = $this->getUserPlan($user);
    $limit = config("plans.{$plan}.features.daily_views", 5);
    if ($limit === PHP_INT_MAX) return PHP_INT_MAX;

    $key  = "user_views:{$user->id}:" . now()->toDateString();
    $used = Cache::get($key, 0);
    return max(0, $limit - $used);
  }

  public function recordTenderView(User $user): void
  {
    $plan  = $this->getUserPlan($user);
    $limit = config("plans.{$plan}.features.daily_views", 5);
    if ($limit === PHP_INT_MAX) return;

    $key = "user_views:{$user->id}:" . now()->toDateString();
    Cache::put($key, (Cache::get($key, 0) + 1), now()->endOfDay());
  }

  // Alert limit
  public function getMaxAlerts(User $user): int
  {
    $plan = $this->getUserPlan($user);
    $max  = config("plans.{$plan}.features.alerts", 1);
    return $max === PHP_INT_MAX ? 9999 : $max;
  }

  // Sync plan field on user record
  public function syncPlanToUser(User $user): void
  {
    $plan = $this->getUserPlan($user);
    $user->update(['subscription_plan' => $plan]);
  }

  // Check if subscription is active
  public function isActive(User $user): bool
  {
    return $user->subscribed('default') || $user->subscription_plan === 'free';
  }

  // Get plan config array for frontend
  public function getPlanConfig(string $plan): array
  {
    return config("plans.{$plan}", config('plans.free'));
  }

  // Get all plans for pricing page
  public function getAllPlans(): array
  {
    return config('plans');
  }
}
```

---

## STEP 6 — SUBSCRIPTION CONTROLLER

Create `app/Modules/Payment/Controllers/SubscriptionController.php`:

```php
<?php
namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
  public function __construct(
    private SubscriptionService $subscriptionService
  ) {}

  // Show pricing page
  public function pricing()
  {
    $plans = $this->subscriptionService->getAllPlans();
    $currentPlan = auth()->check()
      ? $this->subscriptionService->getUserPlan(auth()->user())
      : 'free';

    return Inertia::render('Pricing', [
      'plans'       => $plans,
      'currentPlan' => $currentPlan,
    ]);
  }

  // Redirect to Stripe Checkout
  public function checkout(Request $request)
  {
    $request->validate([
      'plan' => 'required|in:starter,professional,enterprise'
    ]);

    $user    = auth()->user();
    $plan    = $request->plan;
    $priceId = config("plans.{$plan}.stripe_price_id");

    if (!$priceId) {
      return back()->with('error', 'Invalid plan selected.');
    }

    // If already subscribed, redirect to billing portal instead
    if ($user->subscribed('default')) {
      return $this->portal($request);
    }

    $checkout = $user
      ->newSubscription('default', $priceId)
      ->allowPromotionCodes()
      ->checkout([
        'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => route('pricing'),
        'metadata'    => [
          'user_id' => $user->id,
          'plan'    => $plan,
        ],
      ]);

    return Inertia::location($checkout->url);
  }

  // Handle successful payment return
  public function success(Request $request)
  {
    $user = auth()->user();
    $this->subscriptionService->syncPlanToUser($user);

    return Inertia::render('Payment/Success', [
      'plan' => $this->subscriptionService->getUserPlan($user),
    ]);
  }

  // Stripe Billing Portal (manage/cancel subscription)
  public function portal(Request $request)
  {
    $user = auth()->user();

    if (!$user->stripe_id) {
      return redirect()->route('pricing')
        ->with('error', 'No active subscription found.');
    }

    $portalUrl = $user->billingPortalUrl(
      route('dashboard')
    );

    return Inertia::location($portalUrl);
  }

  // Cancel subscription
  public function cancel(Request $request)
  {
    $user = auth()->user();

    if ($user->subscribed('default')) {
      // Cancel at period end — user keeps access till billing date
      $user->subscription('default')->cancel();
      $this->subscriptionService->syncPlanToUser($user);
    }

    return back()->with('success',
      'Subscription cancelled. You have access until the end of your billing period.'
    );
  }

  // Resume a cancelled subscription
  public function resume(Request $request)
  {
    $user = auth()->user();

    if ($user->subscription('default')?->onGracePeriod()) {
      $user->subscription('default')->resume();
      $this->subscriptionService->syncPlanToUser($user);
    }

    return back()->with('success', 'Subscription resumed successfully.');
  }

  // Upgrade/downgrade plan
  public function swap(Request $request)
  {
    $request->validate([
      'plan' => 'required|in:starter,professional,enterprise'
    ]);

    $user    = auth()->user();
    $plan    = $request->plan;
    $priceId = config("plans.{$plan}.stripe_price_id");

    if ($user->subscribed('default')) {
      // Swap immediately, prorated
      $user->subscription('default')->swap($priceId);
      $this->subscriptionService->syncPlanToUser($user);
      return back()->with('success', "Switched to {$plan} plan.");
    }

    // Not subscribed yet — go to checkout
    return $this->checkout($request);
  }

  // Current subscription status (for dashboard)
  public function status()
  {
    $user         = auth()->user();
    $plan         = $this->subscriptionService->getUserPlan($user);
    $subscription = $user->subscription('default');

    return response()->json([
      'plan'            => $plan,
      'is_active'       => $this->subscriptionService->isActive($user),
      'on_grace_period' => $subscription?->onGracePeriod() ?? false,
      'ends_at'         => $subscription?->ends_at,
      'renews_at'       => $subscription?->asStripeSubscription()->current_period_end
                           ? \Carbon\Carbon::createFromTimestamp(
                               $subscription->asStripeSubscription()->current_period_end
                             )->toDateString()
                           : null,
    ]);
  }
}
```

---

## STEP 7 — WEBHOOK CONTROLLER

Create `app/Modules/Payment/Controllers/WebhookController.php`:

```php
<?php
namespace App\Modules\Payment\Controllers;

use App\Models\User;
use App\Modules\Payment\Services\SubscriptionService;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Illuminate\Http\Request;

class WebhookController extends CashierWebhookController
{
  public function __construct(
    private SubscriptionService $subscriptionService
  ) {}

  // Subscription successfully created
  protected function handleCustomerSubscriptionCreated(array $payload): void
  {
    parent::handleCustomerSubscriptionCreated($payload);
    $this->syncUserPlan($payload);
  }

  // Subscription updated (upgrade/downgrade)
  protected function handleCustomerSubscriptionUpdated(array $payload): void
  {
    parent::handleCustomerSubscriptionUpdated($payload);
    $this->syncUserPlan($payload);
  }

  // Subscription deleted/cancelled
  protected function handleCustomerSubscriptionDeleted(array $payload): void
  {
    parent::handleCustomerSubscriptionDeleted($payload);

    $stripeId = $payload['data']['object']['customer'];
    $user     = User::where('stripe_id', $stripeId)->first();

    if ($user) {
      $user->update(['subscription_plan' => 'free']);
    }
  }

  // Payment succeeded — send confirmation email
  protected function handleInvoicePaymentSucceeded(array $payload): void
  {
    $stripeId = $payload['data']['object']['customer'];
    $user     = User::where('stripe_id', $stripeId)->first();

    if ($user) {
      $this->subscriptionService->syncPlanToUser($user);

      // Send payment confirmation email
      \Mail::to($user->email)->queue(
        new \App\Modules\Payment\Mail\PaymentConfirmedMail($user, $payload)
      );
    }
  }

  // Payment failed — notify user
  protected function handleInvoicePaymentFailed(array $payload): void
  {
    $stripeId = $payload['data']['object']['customer'];
    $user     = User::where('stripe_id', $stripeId)->first();

    if ($user) {
      \Mail::to($user->email)->queue(
        new \App\Modules\Payment\Mail\PaymentFailedMail($user)
      );
    }
  }

  // Helper: sync plan on user record
  private function syncUserPlan(array $payload): void
  {
    $stripeId = $payload['data']['object']['customer'];
    $user     = User::where('stripe_id', $stripeId)->first();
    if ($user) {
      $this->subscriptionService->syncPlanToUser($user);
    }
  }
}
```

---

## STEP 8 — MIDDLEWARE

Create `app/Modules/Payment/Middleware/RequireSubscription.php`:

```php
<?php
namespace App\Modules\Payment\Middleware;

use App\Modules\Payment\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;

class RequireSubscription
{
  public function __construct(
    private SubscriptionService $subscriptionService
  ) {}

  // Usage: ->middleware('subscription:starter')
  // Usage: ->middleware('subscription:professional,enterprise')
  public function handle(Request $request, Closure $next, string ...$plans): mixed
  {
    $user        = $request->user();
    $currentPlan = $this->subscriptionService->getUserPlan($user);

    if (!in_array($currentPlan, $plans)) {
      if ($request->expectsJson()) {
        return response()->json([
          'error'    => 'upgrade_required',
          'message'  => 'This feature requires a ' . implode(' or ', $plans) . ' plan.',
          'required' => $plans,
          'current'  => $currentPlan,
        ], 403);
      }

      return redirect()->route('pricing')->with('upgrade_required', [
        'message'  => 'Upgrade your plan to access this feature.',
        'required' => $plans[0],
      ]);
    }

    return $next($request);
  }
}
```

Create `app/Modules/Payment/Middleware/TrackTenderView.php`:

```php
<?php
namespace App\Modules\Payment\Middleware;

use App\Modules\Payment\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;

class TrackTenderView
{
  public function __construct(
    private SubscriptionService $subscriptionService
  ) {}

  public function handle(Request $request, Closure $next): mixed
  {
    $user = $request->user();

    if (!$user) return $next($request); // guests handled separately

    $remaining = $this->subscriptionService->getRemainingDailyViews($user);

    if ($remaining <= 0) {
      if ($request->expectsJson()) {
        return response()->json([
          'error'   => 'daily_limit_reached',
          'message' => 'You have reached your daily view limit. Upgrade to view unlimited tenders.',
        ], 403);
      }
      return redirect()->route('pricing')->with('upgrade_required', [
        'message'  => 'You\'ve reached your 5 daily tender views. Upgrade for unlimited access.',
        'required' => 'starter',
      ]);
    }

    $response = $next($request);

    // Record view after successful response
    $this->subscriptionService->recordTenderView($user);

    return $response;
  }
}
```

Register both middleware in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
  $middleware->alias([
    'subscription'      => \App\Modules\Payment\Middleware\RequireSubscription::class,
    'track.tender.view' => \App\Modules\Payment\Middleware\TrackTenderView::class,
  ]);
})
```

---

## STEP 9 — ROUTES

Create `app/Modules/Payment/Routes/payment.routes.php`:

```php
<?php
use App\Modules\Payment\Controllers\SubscriptionController;
use App\Modules\Payment\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Public
Route::get('/pricing', [SubscriptionController::class, 'pricing'])
  ->name('pricing');

// Stripe webhook — NO auth middleware, uses Stripe signature verification
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])
  ->name('cashier.webhook');

// Authenticated subscription routes
Route::middleware(['auth', 'verified'])->group(function () {

  Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])
    ->name('subscription.checkout');

  Route::get('/subscription/success', [SubscriptionController::class, 'success'])
    ->name('subscription.success');

  Route::get('/subscription/portal', [SubscriptionController::class, 'portal'])
    ->name('subscription.portal');

  Route::post('/subscription/cancel', [SubscriptionController::class, 'cancel'])
    ->name('subscription.cancel');

  Route::post('/subscription/resume', [SubscriptionController::class, 'resume'])
    ->name('subscription.resume');

  Route::post('/subscription/swap', [SubscriptionController::class, 'swap'])
    ->name('subscription.swap');

  Route::get('/subscription/status', [SubscriptionController::class, 'status'])
    ->name('subscription.status');
});

// Apply middleware to tender routes
Route::middleware(['auth', 'track.tender.view'])->group(function () {
  Route::get('/tenders/{tender}', [\App\Modules\Tender\Controllers\TenderController::class, 'show'])
    ->name('tenders.show');
});

// UK tenders — Starter+
Route::middleware(['auth', 'subscription:starter,professional,enterprise'])->group(function () {
  Route::get('/tenders/uk', [\App\Modules\Tender\Controllers\TenderController::class, 'uk'])
    ->name('tenders.uk');
});

// USA + International — Professional+
Route::middleware(['auth', 'subscription:professional,enterprise'])->group(function () {
  Route::get('/tenders/usa', [\App\Modules\Tender\Controllers\TenderController::class, 'usa'])
    ->name('tenders.usa');
  Route::get('/tenders/world-bank', [\App\Modules\Tender\Controllers\TenderController::class, 'worldBank'])
    ->name('tenders.worldbank');
});

// API — Enterprise only
Route::middleware(['auth', 'subscription:enterprise'])->prefix('api/v1')->group(function () {
  Route::get('/tenders', [\App\Modules\Api\Controllers\TenderApiController::class, 'index']);
});
```

Load in `bootstrap/app.php`:

```php
->withRouting(function () {
  foreach (glob(app_path('Modules/*/Routes/*.php')) as $routeFile) {
    require $routeFile;
  }
})
```

---

## STEP 10 — MAILABLE

Create `app/Modules/Payment/Mail/PaymentConfirmedMail.php`:

```php
<?php
namespace App\Modules\Payment\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmedMail extends Mailable
{
  use Queueable, SerializesModels;

  public function __construct(
    public User  $user,
    public array $payload
  ) {}

  public function envelope(): Envelope
  {
    return new Envelope(
      subject: 'Your TenderIQ subscription is active 🎉',
    );
  }

  public function content(): Content
  {
    return new Content(
      view: 'emails.payment.confirmed',
      with: [
        'user'   => $this->user,
        'amount' => ($this->payload['data']['object']['amount_paid'] ?? 0) / 100,
      ]
    );
  }
}
```

Create `app/Modules/Payment/Mail/PaymentFailedMail.php`:

```php
<?php
namespace App\Modules\Payment\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
  use Queueable, SerializesModels;

  public function __construct(public User $user) {}

  public function envelope(): Envelope
  {
    return new Envelope(
      subject: 'Payment failed — action required',
    );
  }

  public function content(): Content
  {
    return new Content(
      view: 'emails.payment.failed',
      with: ['user' => $this->user]
    );
  }
}
```

---

## STEP 11 — FRONTEND: PRICING PAGE

Create `resources/js/Pages/Pricing.jsx`:

```jsx
import { Head, router, usePage } from '@inertiajs/react'
import AppLayout from '@/Layouts/AppLayout'
import { Check, X, Zap } from 'lucide-react'

const PLAN_FEATURES = {
  free:         ['Pakistan PPRA tenders', '5 views/day', '1 keyword alert', 'Daily digest only'],
  starter:      ['Everything in Free', 'UK government tenders', 'Unlimited views', 'AI summaries', '5 alerts (instant)', 'CSV export'],
  professional: ['Everything in Starter', 'USA SAM.gov tenders', 'World Bank + UN + ADB', '20 alerts', 'Budget range alerts', 'Daily/weekly digest'],
  enterprise:   ['Everything in Professional', 'Unlimited alerts', 'API access (1,000/day)', 'Webhooks', 'Priority support'],
}

const PLAN_COLORS = {
  free:         'border-slate-200',
  starter:      'border-teal-400 ring-2 ring-teal-400',
  professional: 'border-blue-400',
  enterprise:   'border-purple-400',
}

const BUTTON_COLORS = {
  free:         'bg-slate-100 text-slate-600 cursor-default',
  starter:      'bg-teal-500 hover:bg-teal-600 text-white',
  professional: 'bg-blue-500 hover:bg-blue-600 text-white',
  enterprise:   'bg-purple-500 hover:bg-purple-600 text-white',
}

export default function Pricing({ plans, currentPlan }) {
  const { auth } = usePage().props

  const handleCheckout = (plan) => {
    if (plan === 'free') return
    if (!auth.user) {
      router.visit('/register', { data: { redirect_plan: plan } })
      return
    }
    if (currentPlan === plan) return
    router.post('/subscription/checkout', { plan })
  }

  const handleManage = () => {
    router.visit('/subscription/portal')
  }

  return (
    <AppLayout>
      <Head title="Pricing — TenderIQ" />

      <div className="max-w-6xl mx-auto px-4 py-16">

        {/* Header */}
        <div className="text-center mb-12">
          <h1 className="text-4xl font-bold text-surface-900 tracking-tight mb-4">
            Simple, transparent pricing
          </h1>
          <p className="text-lg text-surface-500 max-w-xl mx-auto">
            GovWin charges $2,000/month for the same data.
            We charge $49. Cancel anytime.
          </p>
        </div>

        {/* Pricing Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
          {Object.entries(plans).map(([slug, plan]) => (
            <div
              key={slug}
              className={`
                bg-white rounded-2xl border-2 p-6 flex flex-col
                ${PLAN_COLORS[slug]}
                ${slug === 'starter' ? 'shadow-lg scale-105' : 'shadow-card'}
              `}
            >
              {/* Badge */}
              {plan.badge && (
                <div className="mb-3">
                  <span className={`
                    text-xs font-semibold px-2.5 py-1 rounded-full
                    ${slug === 'starter' ? 'bg-teal-50 text-teal-700' : ''}
                    ${slug === 'professional' ? 'bg-blue-50 text-blue-700' : ''}
                    ${slug === 'enterprise' ? 'bg-purple-50 text-purple-700' : ''}
                    ${slug === 'free' ? 'bg-slate-50 text-slate-600' : ''}
                  `}>
                    {plan.badge}
                  </span>
                </div>
              )}

              {/* Name + Price */}
              <h2 className="text-xl font-bold text-surface-900 mb-1">
                {plan.name}
              </h2>
              <div className="mb-6">
                {plan.price === 0 ? (
                  <span className="text-3xl font-bold text-surface-900">Free</span>
                ) : (
                  <>
                    <span className="text-3xl font-bold text-surface-900">
                      ${plan.price}
                    </span>
                    <span className="text-surface-400 text-sm">/month</span>
                  </>
                )}
              </div>

              {/* Features */}
              <ul className="space-y-2.5 mb-8 flex-1">
                {PLAN_FEATURES[slug].map((feature, i) => (
                  <li key={i} className="flex items-start gap-2 text-sm text-surface-700">
                    <Check size={16} className="text-teal-500 mt-0.5 shrink-0" />
                    {feature}
                  </li>
                ))}
              </ul>

              {/* CTA Button */}
              {currentPlan === slug ? (
                <div className="flex flex-col gap-2">
                  <span className="text-center py-2.5 rounded-lg text-sm font-medium bg-slate-100 text-slate-500">
                    Current Plan
                  </span>
                  {slug !== 'free' && (
                    <button
                      onClick={handleManage}
                      className="text-center py-2 text-xs text-surface-400 hover:text-surface-600 underline"
                    >
                      Manage subscription
                    </button>
                  )}
                </div>
              ) : (
                <button
                  onClick={() => handleCheckout(slug)}
                  disabled={slug === 'free'}
                  className={`
                    w-full py-2.5 rounded-lg text-sm font-semibold
                    transition-all duration-150
                    ${BUTTON_COLORS[slug]}
                  `}
                >
                  {slug === 'free'
                    ? 'Get Started Free'
                    : currentPlan !== 'free'
                      ? `Switch to ${plan.name}`
                      : `Start ${plan.name}`
                  }
                </button>
              )}
            </div>
          ))}
        </div>

        {/* Trust line */}
        <p className="text-center text-sm text-surface-400 mt-10">
          Secured by Stripe · Cancel anytime · No contracts
        </p>

      </div>
    </AppLayout>
  )
}
```

---

## STEP 12 — FRONTEND: SUCCESS PAGE

Create `resources/js/Pages/Payment/Success.jsx`:

```jsx
import { Head, Link } from '@inertiajs/react'
import AppLayout from '@/Layouts/AppLayout'
import { CheckCircle2 } from 'lucide-react'

export default function Success({ plan }) {
  const planNames = {
    starter:      'Starter',
    professional: 'Professional',
    enterprise:   'Enterprise',
  }

  return (
    <AppLayout>
      <Head title="Payment Successful — TenderIQ" />
      <div className="max-w-md mx-auto px-4 py-24 text-center">

        <div className="flex justify-center mb-6">
          <CheckCircle2 size={64} className="text-teal-500" />
        </div>

        <h1 className="text-2xl font-bold text-surface-900 mb-3">
          You're all set!
        </h1>

        <p className="text-surface-500 mb-8">
          Your <strong>{planNames[plan]}</strong> plan is now active.
          Start exploring tenders from your new sources.
        </p>

        <div className="flex flex-col gap-3">
          <Link
            href="/tenders"
            className="bg-teal-500 hover:bg-teal-600 text-white
                       py-3 px-6 rounded-lg font-semibold transition-colors"
          >
            Browse Tenders →
          </Link>
          <Link
            href="/alerts"
            className="bg-white border border-surface-200 text-surface-700
                       hover:bg-surface-50 py-3 px-6 rounded-lg font-medium
                       transition-colors"
          >
            Set Up Alerts
          </Link>
        </div>

      </div>
    </AppLayout>
  )
}
```

---

## STEP 13 — FRONTEND: UPGRADE MODAL COMPONENT

Create `resources/js/Components/Payment/UpgradeModal.jsx`:

```jsx
import { router } from '@inertiajs/react'
import { Lock, X, Zap } from 'lucide-react'

export default function UpgradeModal({ isOpen, onClose, feature, requiredPlan = 'starter' }) {
  if (!isOpen) return null

  const planDetails = {
    starter:      { name: 'Starter', price: 29, color: 'teal' },
    professional: { name: 'Professional', price: 49, color: 'blue' },
    enterprise:   { name: 'Enterprise', price: 99, color: 'purple' },
  }

  const plan = planDetails[requiredPlan]

  const handleUpgrade = () => {
    router.post('/subscription/checkout', { plan: requiredPlan })
  }

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">

      {/* Backdrop */}
      <div
        className="absolute inset-0 bg-black/40 backdrop-blur-sm"
        onClick={onClose}
      />

      {/* Modal */}
      <div className="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">

        <button
          onClick={onClose}
          className="absolute top-4 right-4 text-surface-400 hover:text-surface-600"
        >
          <X size={20} />
        </button>

        <div className="flex justify-center mb-4">
          <div className="w-12 h-12 bg-teal-50 rounded-full flex items-center justify-center">
            <Lock size={24} className="text-teal-500" />
          </div>
        </div>

        <h2 className="text-xl font-bold text-surface-900 text-center mb-2">
          Upgrade to unlock
        </h2>

        <p className="text-surface-500 text-center text-sm mb-6">
          {feature || 'This feature'} requires the{' '}
          <strong>{plan.name}</strong> plan or higher.
        </p>

        <div className="bg-surface-50 rounded-xl p-4 mb-6 text-center">
          <span className="text-3xl font-bold text-surface-900">
            ${plan.price}
          </span>
          <span className="text-surface-400 text-sm">/month</span>
          <p className="text-xs text-surface-400 mt-1">Cancel anytime</p>
        </div>

        <button
          onClick={handleUpgrade}
          className="w-full bg-teal-500 hover:bg-teal-600 text-white
                     py-3 rounded-lg font-semibold transition-colors
                     flex items-center justify-center gap-2"
        >
          <Zap size={16} />
          Upgrade to {plan.name}
        </button>

        <button
          onClick={onClose}
          className="w-full mt-3 text-sm text-surface-400 hover:text-surface-600"
        >
          Maybe later
        </button>

      </div>
    </div>
  )
}
```

---

## STEP 14 — FRONTEND: LOCKED OVERLAY COMPONENT

Create `resources/js/Components/UI/LockedOverlay.jsx`:

```jsx
import { useState } from 'react'
import { Lock } from 'lucide-react'
import UpgradeModal from '@/Components/Payment/UpgradeModal'

export default function LockedOverlay({
  children,
  isLocked,
  feature,
  requiredPlan = 'starter',
  blurContent = true,
}) {
  const [showModal, setShowModal] = useState(false)

  if (!isLocked) return <>{children}</>

  return (
    <>
      <div className="relative">
        {/* Blurred content underneath */}
        {blurContent && (
          <div className="filter blur-sm pointer-events-none select-none">
            {children}
          </div>
        )}

        {/* Lock overlay */}
        <div className={`
          ${blurContent ? 'absolute inset-0' : ''}
          flex flex-col items-center justify-center
          bg-gradient-to-b from-transparent to-white/95
          rounded-lg p-4
        `}>
          <Lock size={20} className="text-surface-300 mb-2" />
          <p className="text-xs text-surface-500 text-center mb-3">
            {feature || 'Upgrade to unlock this feature'}
          </p>
          <button
            onClick={() => setShowModal(true)}
            className="text-xs bg-teal-500 hover:bg-teal-600
                       text-white px-3 py-1.5 rounded-lg
                       font-medium transition-colors"
          >
            Upgrade Plan
          </button>
        </div>
      </div>

      <UpgradeModal
        isOpen={showModal}
        onClose={() => setShowModal(false)}
        feature={feature}
        requiredPlan={requiredPlan}
      />
    </>
  )
}
```

---

## STEP 15 — FRONTEND: SUBSCRIPTION SETTINGS (DASHBOARD)

Create `resources/js/Components/Payment/SubscriptionCard.jsx`:

```jsx
import { router } from '@inertiajs/react'
import { CreditCard, ExternalLink, AlertCircle } from 'lucide-react'

export default function SubscriptionCard({ subscription, plan }) {
  const planColors = {
    free:         'bg-slate-100 text-slate-600',
    starter:      'bg-teal-100 text-teal-700',
    professional: 'bg-blue-100 text-blue-700',
    enterprise:   'bg-purple-100 text-purple-700',
  }

  const handlePortal = () => router.visit('/subscription/portal')
  const handleUpgrade = () => router.visit('/pricing')
  const handleCancel = () => {
    if (confirm('Cancel subscription? You keep access until end of billing period.')) {
      router.post('/subscription/cancel')
    }
  }
  const handleResume = () => router.post('/subscription/resume')

  return (
    <div className="bg-white border border-surface-200 rounded-2xl p-6 shadow-card">

      <div className="flex items-center gap-3 mb-4">
        <CreditCard size={20} className="text-surface-400" />
        <h3 className="font-semibold text-surface-900">Subscription</h3>
      </div>

      {/* Current Plan */}
      <div className="flex items-center justify-between mb-4">
        <span className="text-sm text-surface-500">Current Plan</span>
        <span className={`text-xs font-semibold px-2.5 py-1 rounded-full ${planColors[plan]}`}>
          {plan.charAt(0).toUpperCase() + plan.slice(1)}
        </span>
      </div>

      {/* Renewal / Grace Period */}
      {subscription?.renews_at && !subscription?.on_grace_period && (
        <div className="flex items-center justify-between mb-4 text-sm">
          <span className="text-surface-500">Renews</span>
          <span className="text-surface-700">{subscription.renews_at}</span>
        </div>
      )}

      {subscription?.on_grace_period && (
        <div className="flex items-start gap-2 bg-amber-50 border border-amber-200
                        rounded-lg p-3 mb-4 text-sm">
          <AlertCircle size={16} className="text-amber-500 mt-0.5 shrink-0" />
          <div>
            <p className="text-amber-800 font-medium">Subscription cancelled</p>
            <p className="text-amber-600 text-xs">
              Access until {subscription.ends_at}
            </p>
          </div>
        </div>
      )}

      {/* Actions */}
      <div className="flex flex-col gap-2 mt-2">
        {plan === 'free' ? (
          <button
            onClick={handleUpgrade}
            className="w-full bg-teal-500 hover:bg-teal-600 text-white
                       py-2 rounded-lg text-sm font-semibold transition-colors"
          >
            Upgrade Plan
          </button>
        ) : subscription?.on_grace_period ? (
          <button
            onClick={handleResume}
            className="w-full bg-teal-500 hover:bg-teal-600 text-white
                       py-2 rounded-lg text-sm font-semibold transition-colors"
          >
            Resume Subscription
          </button>
        ) : (
          <>
            <button
              onClick={handlePortal}
              className="w-full flex items-center justify-center gap-2
                         bg-white border border-surface-200 hover:bg-surface-50
                         text-surface-700 py-2 rounded-lg text-sm
                         font-medium transition-colors"
            >
              <ExternalLink size={14} />
              Manage Billing
            </button>
            <button
              onClick={handleUpgrade}
              className="w-full text-sm text-surface-400
                         hover:text-surface-600 py-1"
            >
              Change Plan
            </button>
            <button
              onClick={handleCancel}
              className="w-full text-xs text-red-400
                         hover:text-red-600 py-1"
            >
              Cancel Subscription
            </button>
          </>
        )}
      </div>

    </div>
  )
}
```

---

## STEP 16 — WEBHOOK SETUP IN STRIPE

Set up Stripe webhook in Dashboard:

```
Stripe Dashboard → Developers → Webhooks → Add Endpoint

URL: https://tenderiq.com/stripe/webhook

Events to listen for:
✅ customer.subscription.created
✅ customer.subscription.updated
✅ customer.subscription.deleted
✅ invoice.payment_succeeded
✅ invoice.payment_failed
✅ customer.updated
```

Then copy the Webhook Signing Secret into `.env`:
```
STRIPE_WEBHOOK_SECRET=whsec_...
```

Exclude webhook route from CSRF in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
  $middleware->validateCsrfTokens(except: [
    'stripe/webhook',
  ]);
})
```

---

## STEP 17 — INERTIA SHARED DATA

Share subscription data globally in `HandleInertiaRequests.php`:

```php
public function share(Request $request): array
{
  $user = $request->user();
  $subscriptionService = app(\App\Modules\Payment\Services\SubscriptionService::class);

  return [
    ...parent::share($request),
    'auth' => [
      'user' => $user ? [
        'id'                => $user->id,
        'name'              => $user->name,
        'email'             => $user->email,
        'plan'              => $user ? $subscriptionService->getUserPlan($user) : 'free',
        'remaining_views'   => $user ? $subscriptionService->getRemainingDailyViews($user) : 0,
        'can_view_ai'       => $user ? $subscriptionService->canViewAiSummary($user) : false,
        'can_export_csv'    => $user ? $subscriptionService->canExportCsv($user) : false,
      ] : null,
    ],
    'flash' => [
      'success'          => $request->session()->get('success'),
      'error'            => $request->session()->get('error'),
      'upgrade_required' => $request->session()->get('upgrade_required'),
    ],
  ];
}
```

---

## STEP 18 — FRONTEND SUBSCRIPTION HOOK

Create `resources/js/hooks/useSubscription.js`:

```js
import { usePage } from '@inertiajs/react'

export function useSubscription() {
  const { auth } = usePage().props

  const plan           = auth?.user?.plan ?? 'free'
  const remainingViews = auth?.user?.remaining_views ?? 0
  const canViewAi      = auth?.user?.can_view_ai ?? false
  const canExportCsv   = auth?.user?.can_export_csv ?? false

  const isPaid       = ['starter', 'professional', 'enterprise'].includes(plan)
  const isPro        = ['professional', 'enterprise'].includes(plan)
  const isEnterprise = plan === 'enterprise'

  const canAccessCountry = (countryCode) => {
    if (countryCode === 'PK') return true
    if (plan === 'free') return false
    if (['GB'].includes(countryCode)) return isPaid
    if (['US', 'WB', 'UN'].includes(countryCode)) return isPro
    return isEnterprise
  }

  const hasUnlimitedViews = isPaid

  return {
    plan,
    isPaid,
    isPro,
    isEnterprise,
    remainingViews,
    hasUnlimitedViews,
    canViewAi,
    canExportCsv,
    canAccessCountry,
  }
}
```

---

## FULL PAYMENT FLOW DIAGRAM

```
User clicks "Start Starter" on /pricing
          ↓
POST /subscription/checkout { plan: 'starter' }
          ↓
SubscriptionController::checkout()
  → Creates Stripe Checkout Session
  → Returns Stripe Checkout URL
          ↓
Inertia::location($checkoutUrl)
  → User redirected to Stripe hosted page
          ↓
User enters card details on Stripe
          ↓
     [Success]                    [Cancel]
         ↓                            ↓
GET /subscription/success      Redirect to /pricing
         ↓
SubscriptionService::syncPlanToUser()
  → Updates user.subscription_plan
         ↓
Render Payment/Success.jsx
         ↓
ALSO: Stripe fires webhook async
         ↓
POST /stripe/webhook
  → handleCustomerSubscriptionCreated()
  → syncPlanToUser() again (safety net)
  → Send PaymentConfirmedMail
```

---

## FOLDER STRUCTURE

```
app/Modules/Payment/
├── Controllers/
│   ├── SubscriptionController.php
│   └── WebhookController.php
├── Mail/
│   ├── PaymentConfirmedMail.php
│   └── PaymentFailedMail.php
├── Middleware/
│   ├── RequireSubscription.php
│   └── TrackTenderView.php
├── Services/
│   └── SubscriptionService.php
└── Routes/
    └── payment.routes.php

config/
└── plans.php

resources/js/
├── Pages/
│   ├── Pricing.jsx
│   └── Payment/
│       └── Success.jsx
├── Components/
│   ├── Payment/
│   │   ├── UpgradeModal.jsx
│   │   └── SubscriptionCard.jsx
│   └── UI/
│       └── LockedOverlay.jsx
└── hooks/
    └── useSubscription.js
```

---

## ENV CHECKLIST

```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_STARTER_PRICE_ID=price_...
STRIPE_PROFESSIONAL_PRICE_ID=price_...
STRIPE_ENTERPRISE_PRICE_ID=price_...
CASHIER_CURRENCY=usd
```

---

## TESTING CHECKLIST

```
□ Free user sees 5 views/day limit
□ Free user sees LockedOverlay on AI summary
□ Free user sees LockedOverlay on UK tenders
□ Clicking upgrade → Stripe Checkout opens
□ Test card 4242 4242 4242 4242 works
□ Success page shows after payment
□ User plan updates immediately after success
□ Webhook fires and updates plan (check Stripe logs)
□ Cancelled plan shows grace period message
□ Resume subscription works
□ Billing portal opens via Stripe
□ Payment failed email triggers on failed card
□ Plan swap (upgrade/downgrade) works correctly
□ Enterprise user has API access
□ CSV export gated correctly per plan
```

---

## STRIPE TEST CARDS

```
Success:           4242 4242 4242 4242
Requires auth:     4000 0025 0000 3155
Payment declined:  4000 0000 0000 9995
Insufficient funds:4000 0000 0000 9995
Expiry: any future date | CVC: any 3 digits
```