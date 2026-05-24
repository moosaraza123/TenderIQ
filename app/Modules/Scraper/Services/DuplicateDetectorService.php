<?php

namespace App\Modules\Scraper\Services;

use App\Modules\Tender\Models\Tender;
use Illuminate\Support\Facades\DB;

class DuplicateDetectorService
{
    public function findDuplicate(Tender $tender): ?int
    {
        if (empty($tender->title) || empty($tender->organization_name)) {
            return null;
        }

        // Look for same org + same closing date from a different source
        $candidate = Tender::where('id', '!=', $tender->id)
            ->where('organization_name', $tender->organization_name)
            ->where('closing_at', $tender->closing_at)
            ->where('source', '!=', $tender->source)
            ->whereNull('duplicate_of')
            ->first();

        if (! $candidate) {
            return null;
        }

        // Compare title similarity (>70% similar = duplicate)
        similar_text(
            strtolower($tender->title),
            strtolower($candidate->title),
            $percent
        );

        return $percent >= 70 ? $candidate->id : null;
    }

    public function markDuplicate(Tender $tender, int $canonicalId): void
    {
        $tender->update(['duplicate_of' => $canonicalId]);
    }
}
