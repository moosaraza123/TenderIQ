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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendTenderAlerts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private readonly array $tenderIds) {}

    public function handle(AlertMatcherService $matcher): void
    {
        $tenders = Tender::whereIn('id', $this->tenderIds)->get();

        if ($tenders->isEmpty()) return;

        // Only instant-frequency subscriptions fire in real-time; daily/weekly use digest jobs
        $subscriptions = AlertSubscription::active()->where('frequency', 'instant')->with('user')->get();

        $matchesByUser = [];

        foreach ($subscriptions as $sub) {
            $matched = $matcher->matchTendersToSubscription($sub, $tenders);
            if ($matched->isEmpty()) continue;

            $userId = $sub->user_id;
            if (! isset($matchesByUser[$userId])) {
                $matchesByUser[$userId] = [
                    'user'     => $sub->user,
                    'tenders'  => collect(),
                    'webhooks' => [],
                ];
            }

            $matchesByUser[$userId]['tenders'] = $matchesByUser[$userId]['tenders']
                ->merge($matched)
                ->unique('id');

            if ($sub->webhook_url && ! in_array($sub->webhook_url, $matchesByUser[$userId]['webhooks'])) {
                $matchesByUser[$userId]['webhooks'][] = $sub->webhook_url;
            }

            $sub->update(['last_triggered_at' => now()]);
        }

        foreach ($matchesByUser as $userId => $data) {
            Mail::to($data['user']->email)
                ->queue(new TenderAlertMail($data['user'], $data['tenders']));

            foreach ($data['webhooks'] as $webhookUrl) {
                $this->dispatchWebhook($webhookUrl, $data['tenders']);
            }
        }
    }

    private function dispatchWebhook(string $url, $tenders): void
    {
        try {
            Http::timeout(10)->post($url, [
                'event'   => 'tender.match',
                'tenders' => $tenders->map(fn ($t) => [
                    'id'                => $t->id,
                    'tender_number'     => $t->tender_number,
                    'title'             => $t->title,
                    'organization_name' => $t->organization_name,
                    'closing_at'        => $t->closing_at?->toIso8601String(),
                    'url'               => url("/tenders/{$t->tender_number}"),
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            Log::warning("Webhook delivery failed to {$url}: {$e->getMessage()}");
        }
    }
}
