<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\AdminStatsService;
use App\Modules\Scraper\Jobs\RunScraper;
use App\Modules\Tender\Models\Tender;
use App\Modules\User\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function __construct(private readonly AdminStatsService $stats) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Index', [
            'scraperStats' => $this->stats->getScraperStats(),
            'userStats'    => $this->stats->getUserStats(),
        ]);
    }

    public function tenders(): Response
    {
        return Inertia::render('Admin/Tenders', [
            'tenders' => Tender::orderByDesc('scraped_at')->paginate(50),
        ]);
    }

    public function users(): Response
    {
        return Inertia::render('Admin/Users', [
            'users' => User::orderByDesc('created_at')->paginate(50),
        ]);
    }

    public function triggerScraper()
    {
        RunScraper::dispatch();
        return back()->with('success', 'Scraper job dispatched to queue.');
    }

    public function toggleFeatured(Tender $tender)
    {
        $tender->update(['is_featured' => ! $tender->is_featured]);
        return back();
    }
}
