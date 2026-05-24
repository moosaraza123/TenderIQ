<?php

namespace App\Modules\AI\Commands;

use App\Modules\Tender\Jobs\SummarizeTender;
use App\Modules\Tender\Models\Tender;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessAiBatch extends Command
{
    protected $signature   = 'ai:process-batch {--limit=20}';
    protected $description = 'Process a batch of unsummarized tenders through AI, respecting daily spend limit';

    private const SPEND_CACHE_PREFIX = 'ai:spend:';
    private const APPROX_COST_PER_TENDER = 0.004; // ~$0.004 per tender with gpt-4o-mini

    public function handle(): int
    {
        $spendLimit = (float) config('services.openai.daily_spend_limit', 8.0);
        $todayKey   = self::SPEND_CACHE_PREFIX . now()->format('Y-m-d');
        $spentToday = (float) Cache::get($todayKey, 0.0);

        if ($spentToday >= $spendLimit) {
            $this->info("Daily AI spend limit reached (\${$spentToday}/{$spendLimit}). Skipping.");
            return self::SUCCESS;
        }

        $remainingBudget = $spendLimit - $spentToday;
        $maxTenders      = (int) min($this->option('limit'), floor($remainingBudget / self::APPROX_COST_PER_TENDER));

        if ($maxTenders <= 0) {
            $this->info('Not enough budget for even one tender. Skipping.');
            return self::SUCCESS;
        }

        $tenders = Tender::where('is_summarized', false)
            ->whereNotNull('title')
            ->orderByRaw("FIELD(tier, 'paid', 'premium', 'enterprise', 'free')")
            ->orderBy('closing_at')
            ->limit($maxTenders)
            ->get(['id', 'title', 'tier', 'country_code', 'closing_at']);

        if ($tenders->isEmpty()) {
            $this->info('No unsummarized tenders found.');
            return self::SUCCESS;
        }

        $dispatched = 0;
        foreach ($tenders as $tender) {
            SummarizeTender::dispatch($tender->id);
            $dispatched++;
        }

        $estimatedCost = round($dispatched * self::APPROX_COST_PER_TENDER, 4);
        Cache::put($todayKey, $spentToday + $estimatedCost, now()->endOfDay());

        $this->info("Dispatched {$dispatched} AI summarization jobs. Estimated cost: \${$estimatedCost}. Total today: \$" . round($spentToday + $estimatedCost, 4));

        Log::info('ProcessAiBatch dispatched', [
            'count'          => $dispatched,
            'estimated_cost' => $estimatedCost,
            'total_today'    => round($spentToday + $estimatedCost, 4),
        ]);

        return self::SUCCESS;
    }
}
