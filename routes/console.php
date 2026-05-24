<?php

use App\Modules\Alert\Jobs\SendDailyDigest;
use App\Modules\Alert\Jobs\SendWeeklyDigest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Run all active scrapers every 6 hours (PK, UK, World Bank)
Schedule::command('scrape:all')->everySixHours()->withoutOverlapping();

// World Bank updates more frequently (large volume, free API)
Schedule::command('scrape:source world_bank')->everyFourHours()->withoutOverlapping();

// UK Find a Tender — 1,000+ notices/week, check every 6h via scrape:all
// SAM.gov — run daily when API key is configured
Schedule::command('scrape:source sam_gov')->dailyAt('02:00')->withoutOverlapping();

// AI summarization — every 30 min, respects $8/day spend limit
Schedule::command('ai:process-batch')->everyThirtyMinutes()->withoutOverlapping();

// Email digests
Schedule::job(new SendDailyDigest)->dailyAt('08:00');
Schedule::job(new SendWeeklyDigest)->weeklyOn(1, '08:00'); // Monday 8am

// Reset daily counters at midnight
Schedule::call(function () {
    \Illuminate\Support\Facades\Cache::forget('scraper.today_count');
})->daily();
