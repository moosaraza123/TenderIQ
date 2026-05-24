<?php

namespace App\Modules\AI\Services;

use Illuminate\Support\Facades\Cache;

class TranslationService
{
    public function __construct(private readonly OpenAiService $openAi) {}

    public function translateToEnglish(string $text): string
    {
        if (empty($text) || $this->isAscii($text)) {
            return $text;
        }

        $cacheKey = 'translation:' . md5($text);

        return Cache::remember($cacheKey, now()->addDays(30), function () use ($text) {
            $systemPrompt = 'Translate the following Arabic government tender text to English. '
                . 'Keep organization names, amounts, and reference numbers as-is. '
                . 'Return only the translated text, no explanations.';

            $translated = $this->openAi->chat($systemPrompt, $text);

            return $translated ?: $text;
        });
    }

    private function isAscii(string $text): bool
    {
        return mb_detect_encoding($text, 'ASCII', true) !== false
            && ! preg_match('/[\x{0600}-\x{06FF}]/u', $text);
    }
}
