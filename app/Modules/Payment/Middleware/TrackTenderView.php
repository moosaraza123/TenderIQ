<?php

namespace App\Modules\Payment\Middleware;

use App\Modules\Payment\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;

class TrackTenderView
{
    public function __construct(private readonly SubscriptionService $subscriptionService) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $remaining = $this->subscriptionService->getRemainingDailyViews($user);

        if ($remaining <= 0) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error'   => 'daily_limit_reached',
                    'message' => 'You have reached your 5 daily view limit. Upgrade for unlimited access.',
                ], 403);
            }

            return redirect()->route('pricing')->with('upgrade_required', [
                'message'  => "You've reached your 5 daily tender views. Upgrade for unlimited access.",
                'required' => 'starter',
            ]);
        }

        $response = $next($request);
        $this->subscriptionService->recordTenderView($user);

        return $response;
    }
}
