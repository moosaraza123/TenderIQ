<?php

namespace App\Modules\Tender\Jobs;

use App\Modules\AI\Services\TenderAiService;
use App\Modules\Scraper\Services\PdfExtractorService;
use App\Modules\Tender\Models\Tender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SummarizeTender implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 120;

    public function __construct(private readonly int $tenderId) {}

    public function handle(TenderAiService $aiService, PdfExtractorService $pdfExtractor): void
    {
        $tender = Tender::find($this->tenderId);
        if (! $tender || $tender->is_summarized) {
            return;
        }

        $text = $this->extractText($tender, $pdfExtractor);

        if (empty(trim($text))) {
            Log::info("SummarizeTender: no text for tender #{$this->tenderId}");
            return;
        }

        try {
            $language = $this->detectLanguage($tender);
            $result   = $aiService->summarize($text, $language);

            $tender->update([
                'ai_summary'          => $result->summary,
                'ai_eligibility'      => $result->eligibility,
                'ai_budget_extracted' => $result->budget,
                'ai_recommendation'   => $result->recommendation,
                'ai_key_requirements' => $result->keyRequirements,
                'is_summarized'       => true,
            ]);
        } catch (\Throwable $e) {
            Log::error("SummarizeTender failed for #{$this->tenderId}", ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function detectLanguage(Tender $tender): string
    {
        $text = $tender->title . ' ' . ($tender->description ?? '');
        return preg_match('/[\x{0600}-\x{06FF}]/u', $text) ? 'ar' : 'en';
    }

    private function extractText(Tender $tender, PdfExtractorService $pdfExtractor): string
    {
        if (! empty($tender->pdf_urls)) {
            foreach ($tender->pdf_urls as $pdfUrl) {
                $text = $pdfExtractor->extractFromUrl($pdfUrl);
                if ($text && strlen(trim($text)) > 100) {
                    return $text;
                }
            }
        }

        return trim("{$tender->title}\n\n{$tender->description}");
    }
}
