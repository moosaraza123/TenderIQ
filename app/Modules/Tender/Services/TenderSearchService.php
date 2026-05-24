<?php

namespace App\Modules\Tender\Services;

use App\Modules\Tender\Models\Tender;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TenderSearchService
{
    public function search(string $query, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Tender::search($query)
            ->query(function ($q) use ($filters) {
                if (! empty($filters['category'])) {
                    $q->where('category', $filters['category']);
                }
                if (! empty($filters['city'])) {
                    $q->where('city', $filters['city']);
                }
                if (! empty($filters['status'])) {
                    $q->where('status', $filters['status']);
                }
            })
            ->paginate($perPage);
    }
}
