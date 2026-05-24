<?php

namespace App\Modules\Tender\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payment\Services\SubscriptionService;
use App\Modules\Tender\Models\Tender;
use App\Modules\Tender\Requests\TenderFilterRequest;
use App\Modules\Tender\Services\TenderAccessService;
use App\Modules\Tender\Services\TenderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenderController extends Controller
{
    public function __construct(
        private readonly TenderService       $tenderService,
        private readonly TenderAccessService $accessService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function index(TenderFilterRequest $request): Response
    {
        $filters = $request->validated();

        return Inertia::render('Tenders/Index', [
            'tenders' => $this->tenderService->getFilteredTenders($filters),
            'filters' => $filters,
            'stats'   => Cache::remember('tenders.stats', 300, fn () => $this->tenderService->getStats()),
        ]);
    }

    public function show(Request $request, string $tenderNumber): Response
    {
        $tender = $this->tenderService->getByNumber($tenderNumber);
        $user   = $request->user();

        // Tier-based country lock: UAE/Saudi/International require paid plans
        $tierLocked = ! $this->accessService->canViewTender($user, $tender);

        if (! $tierLocked && ! $this->accessService->canViewDetail($user, $request)) {
            return Inertia::render('Tenders/Show', [
                'tender'       => $tender,
                'accessDenied' => true,
                'tierLocked'   => false,
                'viewsUsed'    => $this->accessService->dailyViewCount($request, $user),
                'viewLimit'    => 5,
            ]);
        }

        if ($tierLocked) {
            return Inertia::render('Tenders/Show', [
                'tender'       => $tender,
                'accessDenied' => true,
                'tierLocked'   => true,
                'requiredPlan' => $tender->country_code === 'AE' ? 'starter' : ($tender->country_code === 'SA' ? 'professional' : 'enterprise'),
            ]);
        }

        $this->tenderService->recordView($tender, $request);

        if (! $user) {
            $this->accessService->incrementGuestViewCount($request);
        }

        $seoTitle       = "{$tender->title} - {$tender->organization_name} Tender | TenderIQ";
        $seoDescription = $tender->ai_summary
            ? explode('.', $tender->ai_summary)[0] . '.'
            : substr($tender->title, 0, 160);

        return Inertia::render('Tenders/Show', [
            'tender'          => $tender,
            'accessDenied'    => false,
            'tierLocked'      => false,
            'canViewSummary'  => $this->accessService->canViewSummary($user),
            'canDownloadPdf'  => $this->accessService->canDownloadPdf($user),
            'canViewRec'      => $this->accessService->canViewRecommendation($user),
            'seoTitle'        => $seoTitle,
            'seoDescription'  => $seoDescription,
        ]);
    }

    public function export(TenderFilterRequest $request): StreamedResponse
    {
        $user = $request->user();

        abort_unless($user && $this->subscriptionService->canExportCsv($user), 403, 'CSV export requires a Starter plan or higher.');

        $filters = $request->validated();
        $query   = Tender::query();

        if (! empty($filters['keyword'])) {
            $kw = $filters['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->where('title', 'like', "%{$kw}%")
                  ->orWhere('organization_name', 'like', "%{$kw}%")
                  ->orWhere('tender_number', 'like', "%{$kw}%");
            });
        }
        if (! empty($filters['status']))      $query->where('status', $filters['status']);
        if (! empty($filters['category']))    $query->where('category', $filters['category']);
        if (! empty($filters['city']))        $query->where('city', $filters['city']);
        if (! empty($filters['closing_from'])) $query->where('closing_at', '>=', $filters['closing_from']);
        if (! empty($filters['closing_to']))   $query->where('closing_at', '<=', $filters['closing_to'] . ' 23:59:59');

        $tenders = $query->orderBy('closing_at')->limit(5000)->get([
            'tender_number', 'title', 'organization_name', 'category',
            'city', 'status', 'closing_at', 'advertised_at', 'detail_url',
        ]);

        $filename = 'tenders-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($tenders) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Tender Number', 'Title', 'Organization', 'Category', 'City', 'Status', 'Closing Date', 'Advertised Date', 'URL']);
            foreach ($tenders as $t) {
                fputcsv($handle, [
                    $t->tender_number,
                    $t->title,
                    $t->organization_name,
                    $t->category,
                    $t->city,
                    $t->status,
                    $t->closing_at?->toDateString(),
                    $t->advertised_at?->toDateString(),
                    $t->detail_url,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function sitemap()
    {
        $tenders = Tender::select('tender_number', 'updated_at')
            ->orderByDesc('updated_at')
            ->limit(5000)
            ->get();

        $xml = view('sitemap', compact('tenders'));

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
