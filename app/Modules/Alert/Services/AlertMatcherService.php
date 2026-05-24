<?php

namespace App\Modules\Alert\Services;

use App\Modules\Alert\Models\AlertSubscription;
use App\Modules\Tender\Models\Tender;
use Illuminate\Support\Collection;

class AlertMatcherService
{
    public function matchTendersToSubscription(AlertSubscription $sub, Collection $tenders): Collection
    {
        return $tenders->filter(function (Tender $tender) use ($sub) {
            return $this->matches($tender, $sub);
        });
    }

    private function matches(Tender $tender, AlertSubscription $sub): bool
    {
        if (! empty($sub->keywords)) {
            $text  = strtolower("{$tender->title} {$tender->description}");
            $found = false;
            foreach ($sub->keywords as $kw) {
                if (str_contains($text, strtolower($kw))) {
                    $found = true;
                    break;
                }
            }
            if (! $found) return false;
        }

        if (! empty($sub->categories) && ! in_array($tender->category, $sub->categories)) {
            return false;
        }

        if (! empty($sub->cities) && ! in_array($tender->city, $sub->cities)) {
            return false;
        }

        if ($sub->min_budget !== null && $tender->budget !== null && $tender->budget < $sub->min_budget) {
            return false;
        }

        if ($sub->max_budget !== null && $tender->budget !== null && $tender->budget > $sub->max_budget) {
            return false;
        }

        // Country filter (Phase 2)
        if (! empty($sub->countries) && ! in_array($tender->country_code, $sub->countries)) {
            return false;
        }

        // Source filter (Phase 2)
        if (! empty($sub->sources) && ! in_array($tender->source, $sub->sources)) {
            return false;
        }

        return true;
    }
}
