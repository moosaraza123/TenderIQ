<?php

namespace App\Modules\Tender\Services;

use App\Modules\Tender\Models\Tender;
use App\Modules\Tender\Models\TenderView;
use App\Modules\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class TenderAccessService
{
    private const GUEST_DAILY_LIMIT = 5;

    public function canViewTender(?User $user, Tender $tender): bool
    {
        if ($tender->tier === 'free') return true;
        if (! $user) return false;

        // starter $29: UK (FTS + Contracts Finder)
        if ($tender->tier === 'starter') return $user->hasActivePlan(['starter', 'professional', 'enterprise']);

        // professional $49: World Bank, SAM.gov, UN, ADB, AfDB
        if ($tender->tier === 'professional') return $user->hasActivePlan(['professional', 'enterprise']);

        return $user->hasActivePlan(['enterprise']);
    }

    public function canViewDetail(?User $user, Request $request): bool
    {
        if ($user && $user->isSubscribed()) {
            return true;
        }

        return $this->dailyViewCount($request, $user) < self::GUEST_DAILY_LIMIT;
    }

    public function canViewSummary(?User $user): bool
    {
        return $user && $user->canViewSummary();
    }

    public function canDownloadPdf(?User $user): bool
    {
        return $user && $user->canDownloadPdf();
    }

    public function canViewRecommendation(?User $user): bool
    {
        return $user && $user->canViewRecommendation();
    }

    public function canCreateAlert(User $user): bool
    {
        $existingCount = $user->alerts()->count();
        return $existingCount < $user->alertLimit();
    }

    public function dailyViewCount(Request $request, ?User $user): int
    {
        if ($user) {
            return TenderView::where('user_id', $user->id)
                ->whereDate('created_at', today())
                ->count();
        }

        $key = 'views:' . $request->ip() . ':' . today()->format('Y-m-d');

        try {
            return (int) Redis::get($key);
        } catch (\Throwable) {
            return 0;
        }
    }

    public function incrementGuestViewCount(Request $request): void
    {
        $key = 'views:' . $request->ip() . ':' . today()->format('Y-m-d');
        try {
            Redis::pipeline(function ($pipe) use ($key) {
                $pipe->incr($key);
                $pipe->expire($key, 86400);
            });
        } catch (\Throwable) {
            // Redis unavailable — fail gracefully
        }
    }
}
