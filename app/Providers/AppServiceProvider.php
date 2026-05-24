<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->commands([
            \App\Modules\Scraper\Commands\ScrapePpra::class,
            \App\Modules\Scraper\Commands\ScrapeAll::class,
            \App\Modules\Scraper\Commands\ScrapeSource::class,
            \App\Modules\AI\Commands\ProcessAiBatch::class,
        ]);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('tenders', function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
