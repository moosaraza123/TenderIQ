<?php

namespace App\Modules\Admin\Services;

use App\Modules\Tender\Models\Tender;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;

class AdminStatsService
{
    public function getScraperStats(): array
    {
        return [
            'last_run'      => Cache::get('scraper.last_run', 'Never'),
            'total_tenders' => Cache::get('scraper.total_tenders', Tender::count()),
            'today_new'     => Cache::get('scraper.today_count', $this->getTodayCount()),
        ];
    }

    public function getTodayCount(): int
    {
        return Tender::whereDate('scraped_at', today())->count();
    }

    public function getUserStats(): array
    {
        return [
            'total'   => User::count(),
            'free'    => User::where('subscription_plan', 'free')->count(),
            'basic'   => User::where('subscription_plan', 'basic')->count(),
            'pro'     => User::where('subscription_plan', 'pro')->count(),
        ];
    }
}
