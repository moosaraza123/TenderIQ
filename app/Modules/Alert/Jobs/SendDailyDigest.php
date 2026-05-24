<?php

namespace App\Modules\Alert\Jobs;

use App\Modules\Alert\Mail\TenderAlertMail;
use App\Modules\Alert\Models\AlertSubscription;
use App\Modules\Alert\Services\AlertMatcherService;
use App\Modules\Tender\Models\Tender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendDailyDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(AlertMatcherService $matcher): void
    {
        $since         = now()->subDay();
        $newTenders    = Tender::where('created_at', '>=', $since)->get();

        if ($newTenders->isEmpty()) return;

        $subscriptions = AlertSubscription::active()
            ->where('frequency', 'daily')
            ->with('user')
            ->get();

        foreach ($subscriptions as $sub) {
            $matched = $matcher->matchTendersToSubscription($sub, $newTenders);
            if ($matched->isEmpty()) continue;

            Mail::to($sub->user->email)
                ->queue(new TenderAlertMail($sub->user, $matched));

            $sub->increment('match_count', $matched->count());
            $sub->update(['last_triggered_at' => now()]);
        }
    }
}
