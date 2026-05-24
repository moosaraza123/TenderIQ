<?php

namespace App\Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tender\Models\Tender;
use App\Modules\Tender\Services\TenderAccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenderApiController extends Controller
{
    public function __construct(private readonly TenderAccessService $accessService) {}

    public function index(Request $request): JsonResponse
    {
        $user    = $request->user();
        $perPage = min((int) $request->query('per_page', 20), 100);

        $query = Tender::query()
            ->select([
                'id', 'tender_number', 'title', 'organization_name', 'ministry',
                'country', 'country_code', 'region', 'city', 'category', 'sector',
                'tier', 'source', 'status', 'tender_type',
                'closing_at', 'published_at', 'currency', 'estimated_value',
                'detail_url', 'is_summarized', 'ai_summary', 'ai_recommendation',
            ]);

        if ($request->filled('country')) {
            $query->where('country_code', strtoupper($request->country));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('organization_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('tier')) {
            $query->where('tier', $request->tier);
        }

        // Restrict results to what the user's plan allows
        if ($user) {
            if (! $user->hasActivePlan(['enterprise'])) {
                $query->whereNotIn('country_code', ['*']);
            }
            if (! $user->hasActivePlan(['pro', 'enterprise'])) {
                $query->where('country_code', '!=', 'SA');
            }
            if (! $user->hasActivePlan(['basic', 'pro', 'enterprise'])) {
                $query->where('country_code', '!=', 'AE');
            }
        } else {
            $query->where('tier', 'free');
        }

        $tenders = $query->orderByDesc('closing_at')->paginate($perPage);

        return response()->json([
            'data'  => $tenders->items(),
            'meta'  => [
                'total'        => $tenders->total(),
                'per_page'     => $tenders->perPage(),
                'current_page' => $tenders->currentPage(),
                'last_page'    => $tenders->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $tenderNumber): JsonResponse
    {
        $tender = Tender::where('tender_number', $tenderNumber)->firstOrFail();
        $user   = $request->user();

        if (! $this->accessService->canViewTender($user, $tender)) {
            return response()->json([
                'error'         => 'This tender requires a higher subscription plan.',
                'required_plan' => $tender->country_code === 'AE' ? 'basic' : ($tender->country_code === 'SA' ? 'pro' : 'enterprise'),
            ], 403);
        }

        return response()->json(['data' => $tender]);
    }
}
