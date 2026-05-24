<?php

namespace App\Modules\AI\DTOs;

readonly class AiSummaryResult
{
    public function __construct(
        public string  $summary,
        public string  $eligibility,
        public ?float  $budget,
        public string  $recommendation,
        public array   $keyRequirements,
        public ?string $deadline = null,
        public ?string $currency = null,
        public array   $sectorTags = [],
        public ?string $contractDuration = null,
        public ?string $location = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            summary:          $data['SUMMARY'] ?? $data['summary'] ?? '',
            eligibility:      $data['ELIGIBILITY'] ?? $data['eligibility'] ?? '',
            budget:           isset($data['BUDGET']) ? self::parseBudget($data['BUDGET']) : null,
            recommendation:   self::normalizeRecommendation($data['RECOMMENDATION'] ?? $data['recommendation'] ?? 'Review'),
            keyRequirements:  $data['KEY_REQUIREMENTS'] ?? $data['key_requirements'] ?? [],
            deadline:         $data['DEADLINE'] ?? $data['deadline'] ?? null,
            currency:         $data['CURRENCY'] ?? $data['currency'] ?? null,
            sectorTags:       $data['SECTOR_TAGS'] ?? $data['sector_tags'] ?? [],
            contractDuration: $data['CONTRACT_DURATION'] ?? $data['contract_duration'] ?? null,
            location:         $data['LOCATION'] ?? $data['location'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'SUMMARY'           => $this->summary,
            'ELIGIBILITY'       => $this->eligibility,
            'BUDGET'            => $this->budget,
            'RECOMMENDATION'    => $this->recommendation,
            'KEY_REQUIREMENTS'  => $this->keyRequirements,
            'DEADLINE'          => $this->deadline,
            'CURRENCY'          => $this->currency,
            'SECTOR_TAGS'       => $this->sectorTags,
            'CONTRACT_DURATION' => $this->contractDuration,
            'LOCATION'          => $this->location,
        ];
    }

    private static function parseBudget(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        $cleaned = preg_replace('/[^\d.]/', '', (string) $value);
        return $cleaned ? (float) $cleaned : null;
    }

    private static function normalizeRecommendation(string $value): string
    {
        $upper = strtoupper(trim($value));
        return match (true) {
            str_contains($upper, 'APPLY') => 'Apply',
            str_contains($upper, 'SKIP')  => 'Skip',
            default                       => 'Review',
        };
    }
}
