<?php

namespace App\Http\Middleware;

use App\Modules\Payment\Services\SubscriptionService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $svc  = $user ? app(SubscriptionService::class) : null;
        $plan = $svc ? $svc->getUserPlan($user) : 'free';

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id'               => $user->id,
                    'name'             => $user->name,
                    'email'            => $user->email,
                    'company_name'     => $user->company_name,
                    'plan'             => $plan,
                    'remaining_views'  => $svc->getRemainingDailyViews($user),
                    'can_view_ai'      => $svc->canViewAiSummary($user),
                    'can_export_csv'   => $svc->canExportCsv($user),
                    'is_admin'         => $user->is_admin,
                ] : null,
            ],
            'flash' => [
                'success'          => fn () => $request->session()->get('success'),
                'error'            => fn () => $request->session()->get('error'),
                'upgrade_required' => fn () => $request->session()->get('upgrade_required'),
            ],
        ];
    }
}
