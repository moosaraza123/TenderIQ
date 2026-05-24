<?php

namespace App\Modules\Payment\Middleware;

use App\Modules\Payment\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;

class RequireSubscription
{
    public function __construct(private readonly SubscriptionService $subscriptionService) {}

    // Usage: ->middleware('subscription:starter')
    // Usage: ->middleware('subscription:professional,enterprise')
    public function handle(Request $request, Closure $next, string ...$plans): mixed
    {
        $user        = $request->user();
        $currentPlan = $user ? $this->subscriptionService->getUserPlan($user) : 'free';

        if (! in_array($currentPlan, $plans)) {
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
