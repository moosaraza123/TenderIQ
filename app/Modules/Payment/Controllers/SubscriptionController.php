<?php

namespace App\Modules\Payment\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptionService) {}

    public function pricing()
    {
        return Inertia::render('Pricing', [
            'plans'       => $this->subscriptionService->getAllPlans(),
            'currentPlan' => auth()->check()
                ? $this->subscriptionService->getUserPlan(auth()->user())
                : 'free',
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate(['plan' => 'required|in:starter,professional,enterprise']);

        $user    = $request->user();
        $plan    = $request->plan;
        $priceId = config("plans.{$plan}.stripe_price_id");

        if (! $priceId) {
            return back()->with('error', 'Invalid plan — Stripe price not configured.');
        }

        if ($user->subscribed('default')) {
            return $this->portal($request);
        }

        $checkout = $user
            ->newSubscription('default', $priceId)
            ->allowPromotionCodes()
            ->checkout([
                'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => route('pricing'),
                'metadata'    => ['user_id' => $user->id, 'plan' => $plan],
            ]);

        return Inertia::location($checkout->url);
    }

    public function success(Request $request)
    {
        $user = $request->user();
        $this->subscriptionService->syncPlanToUser($user);

        return Inertia::render('Payment/Success', [
            'plan' => $this->subscriptionService->getUserPlan($user),
        ]);
    }

    public function portal(Request $request)
    {
        $user = $request->user();

        if (! $user->stripe_id) {
            return redirect()->route('pricing')->with('error', 'No active subscription found.');
        }

        return Inertia::location($user->billingPortalUrl(route('dashboard')));
    }

    public function cancel(Request $request)
    {
        $user = $request->user();

        if ($user->subscribed('default')) {
            $user->subscription('default')->cancel();
            $this->subscriptionService->syncPlanToUser($user);
        }

        return back()->with('success', 'Subscription cancelled. You keep access until the end of the billing period.');
    }

    public function resume(Request $request)
    {
        $user = $request->user();

        if ($user->subscription('default')?->onGracePeriod()) {
            $user->subscription('default')->resume();
            $this->subscriptionService->syncPlanToUser($user);
        }

        return back()->with('success', 'Subscription resumed.');
    }

    public function swap(Request $request)
    {
        $request->validate(['plan' => 'required|in:starter,professional,enterprise']);

        $user    = $request->user();
        $priceId = config("plans.{$request->plan}.stripe_price_id");

        if ($user->subscribed('default')) {
            $user->subscription('default')->swap($priceId);
            $this->subscriptionService->syncPlanToUser($user);
            return back()->with('success', "Switched to {$request->plan} plan.");
        }

        return $this->checkout($request);
    }

    public function status(Request $request)
    {
        $user         = $request->user();
        $plan         = $this->subscriptionService->getUserPlan($user);
        $subscription = $user->subscription('default');

        $renewsAt = null;
        if ($subscription && ! $subscription->onGracePeriod()) {
            try {
                $renewsAt = \Carbon\Carbon::createFromTimestamp(
                    $subscription->asStripeSubscription()->current_period_end
                )->toDateString();
            } catch (\Throwable) {}
        }

        return response()->json([
            'plan'            => $plan,
            'is_active'       => $this->subscriptionService->isActive($user),
            'on_grace_period' => $subscription?->onGracePeriod() ?? false,
            'ends_at'         => $subscription?->ends_at?->toDateString(),
            'renews_at'       => $renewsAt,
        ]);
    }
}
