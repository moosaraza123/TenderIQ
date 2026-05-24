<?php

namespace App\Modules\Payment\Services;

use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;

class SubscriptionService
{
    public function getUserPlan(User $user): string
    {
        if ($user->subscribed('default')) {
            $priceId  = (string) $user->subscription('default')->stripe_price;
            $priceMap = array_filter([
                (string) config('plans.starter.stripe_price_id')      => 'starter',
                (string) config('plans.professional.stripe_price_id') => 'professional',
                (string) config('plans.enterprise.stripe_price_id')   => 'enterprise',
            ], fn ($k) => $k !== '', ARRAY_FILTER_USE_KEY);
            $plan = $priceMap[$priceId] ?? null;
            if ($plan) return $plan;
        }
        // Fall back to DB column (set by webhook or manual override)
        $dbPlan = (string) $user->subscription_plan;
        return \in_array($dbPlan, ['starter', 'professional', 'enterprise'], true)
            ? $dbPlan
            : 'free';
    }

    public function canAccessSource(User $user, string $source): bool
    {
        $allowed = (array) config('plans.' . $this->getUserPlan($user) . '.sources', []);
        return $allowed === ['*'] || \in_array($source, $allowed);
    }

    public function canAccessCountry(User $user, string $countryCode): bool
    {
        if ($countryCode === 'PK') return true;
        $allowed = (array) config('plans.' . $this->getUserPlan($user) . '.countries', ['PK']);
        return $allowed === ['*'] || \in_array($countryCode, $allowed);
    }

    public function canViewAiSummary(User $user): bool
    {
        return (bool) config('plans.' . $this->getUserPlan($user) . '.features.ai_summaries', false);
    }

    public function canExportCsv(User $user): bool
    {
        return (bool) config('plans.' . $this->getUserPlan($user) . '.features.csv_export', false);
    }

    public function canUseWebhooks(User $user): bool
    {
        return (bool) config('plans.' . $this->getUserPlan($user) . '.features.webhooks', false);
    }

    public function getRemainingDailyViews(User $user): int
    {
        $limit = config('plans.' . $this->getUserPlan($user) . '.features.daily_views', 5);
        if ($limit === PHP_INT_MAX) return PHP_INT_MAX;

        $used = Cache::get("user_views:{$user->id}:" . now()->toDateString(), 0);
        return max(0, $limit - $used);
    }

    public function recordTenderView(User $user): void
    {
        $limit = config('plans.' . $this->getUserPlan($user) . '.features.daily_views', 5);
        if ($limit === PHP_INT_MAX) return;

        $key = "user_views:{$user->id}:" . now()->toDateString();
        Cache::put($key, Cache::get($key, 0) + 1, now()->endOfDay());
    }

    public function getMaxAlerts(User $user): int
    {
        $max = config('plans.' . $this->getUserPlan($user) . '.features.alerts', 1);
        return $max === PHP_INT_MAX ? 9999 : $max;
    }

    public function syncPlanToUser(User $user): void
    {
        $user->update(['subscription_plan' => $this->getUserPlan($user)]);
    }

    public function isActive(User $user): bool
    {
        return $user->subscribed('default') || $user->subscription_plan === 'free';
    }

    public function getAllPlans(): array
    {
        return config('plans');
    }
}
