<?php

namespace App\Modules\AI\Services;

use App\Modules\AI\DTOs\AiSummaryResult;

class TenderAiService
{
    private const SYSTEM_PROMPT = <<<'PROMPT'
You are an expert in government procurement across GCC countries and Pakistan. Analyze the provided tender document (which may be in English or Arabic) and extract key information.

Respond in JSON format only with these keys:
- SUMMARY: 3-4 sentence plain English summary of what is being procured and by which entity
- ELIGIBILITY: Key eligibility requirements (registration, experience, certifications, nationality restrictions)
- BUDGET: Estimated contract value if mentioned (numeric only, in the tender's currency)
- CURRENCY: The currency code (AED, SAR, PKR, USD, etc.) or null if not mentioned
- DEADLINE: Submission/closing deadline as stated
- RECOMMENDATION: One of "Apply" (clear requirements, common goods/services), "Review" (complex or specialized), or "Skip" (very specialized or critical info missing)
- KEY_REQUIREMENTS: Array of 3-5 most important requirements as short strings
- SECTOR_TAGS: Array of 1-3 sector tags from: ["Construction", "IT", "Consulting", "Healthcare", "Education", "Energy", "Transport", "Goods", "Services", "Security", "Environment", "Finance"]
- CONTRACT_DURATION: Duration of the contract if mentioned (e.g. "12 months", "3 years")
- LOCATION: City or region where work is to be performed, if mentioned

If the tender is in Arabic, analyze the Arabic text directly — do not indicate that it is Arabic in your response, just analyze and respond in English.

Example response:
{"SUMMARY":"...","ELIGIBILITY":"...","BUDGET":500000,"CURRENCY":"AED","DEADLINE":"2026-03-15","RECOMMENDATION":"Apply","KEY_REQUIREMENTS":["...","..."],"SECTOR_TAGS":["Construction"],"CONTRACT_DURATION":"18 months","LOCATION":"Dubai"}
PROMPT;

    public function __construct(
        private readonly OpenAiService    $openAi,
        private readonly TranslationService $translator,
    ) {}

    public function summarize(string $text, string $language = 'en'): AiSummaryResult
    {
        // Pre-translate Arabic text for better token efficiency on non-multilingual models
        if ($language === 'ar') {
            $text = $this->translator->translateToEnglish($text);
        }

        $words    = str_word_count($text);
        $truncated = $words > 6000 ? implode(' ', array_slice(explode(' ', $text), 0, 6000)) : $text;

        $raw = $this->openAi->chat(self::SYSTEM_PROMPT, $truncated);

        return $this->parseResponse($raw);
    }

    private function parseResponse(?string $raw): AiSummaryResult
    {
        if (! $raw) {
            return $this->fallback();
        }

        $json = preg_replace('/^```json\s*|\s*```$/', '', trim($raw));

        $data = json_decode($json, true);
        if (! is_array($data)) {
            return $this->fallback();
        }

        return AiSummaryResult::fromArray($data);
    }

    private function fallback(): AiSummaryResult
    {
        return new AiSummaryResult(
            summary:        '',
            eligibility:    '',
            budget:         null,
            recommendation: 'Review',
            keyRequirements: [],
        );
    }
}
