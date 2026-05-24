<?php

namespace App\Modules\AI\Services;

use OpenAI\Laravel\Facades\OpenAI;

class OpenAiService
{
    public function chat(string $systemPrompt, string $userContent, string $model = 'gpt-4o-mini'): ?string
    {
        $response = OpenAI::chat()->create([
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user',   'content' => $userContent],
            ],
            'temperature' => 0.3,
        ]);

        return $response->choices[0]->message->content ?? null;
    }
}
