<?php

namespace App\Modules\Tender\Services;

use App\Modules\Tender\Models\Tender;
use App\Modules\Tender\Models\TenderView;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class TenderService
{
    public function getFilteredTenders(array $filters): LengthAwarePaginator
    {
        $query = Tender::query();

        if (! empty($filters['country'])) {
            $query->where('country_code', $filters['country']);
        }

        if (! empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->where('title', 'like', "%{$kw}%")
                  ->orWhere('description', 'like', "%{$kw}%")
                  ->orWhere('organization_name', 'like', "%{$kw}%")
                  ->orWhere('tender_number', 'like', "%{$kw}%");
            });
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['sector'])) {
            $query->where('sector', $filters['sector']);
        }

        if (! empty($filters['city'])) {
            $query->where('city', $filters['city']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['tender_type'])) {
            $query->where('tender_type', $filters['tender_type']);
        }

        if (! empty($filters['closing_from'])) {
            $query->where('closing_at', '>=', $filters['closing_from']);
        }

        if (! empty($filters['closing_to'])) {
            $query->where('closing_at', '<=', $filters['closing_to'] . ' 23:59:59');
        }

        $sort = $filters['sort'] ?? 'closing_soon';
        match ($sort) {
            'newest'   => $query->orderByDesc('advertised_at'),
            default    => $query->orderBy('closing_at'),
        };

        return $query->paginate(25)->withQueryString();
    }

    public function getByNumber(string $tenderNumber): ?Tender
    {
        return Tender::where('tender_number', $tenderNumber)->firstOrFail();
    }

    public function recordView(Tender $tender, Request $request): void
    {
        TenderView::create([
            'tender_id'  => $tender->id,
            'user_id'    => $request->user()?->id,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ]);
    }

    public function getRecentTenders(int $limit = 6): array
    {
        return Tender::active()
            ->orderByDesc('advertised_at')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getStats(): array
    {
        $total   = Tender::published()->count();
        $openNow = Tender::active()->count();

        return [
            'total_tenders'  => $total,
            'open_tenders'   => $openNow,
            'active_count'   => $openNow,
            'last_updated'   => Tender::max('scraped_at'),
            'org_count'      => Tender::distinct('organization_name')->count(),
        ];
    }
}
