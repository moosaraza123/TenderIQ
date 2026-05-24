<?php

use App\Modules\User\Controllers\UserController;
use App\Modules\Tender\Models\Tender;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Homepage
Route::get('/', function () {
    $tenderService = app(\App\Modules\Tender\Services\TenderService::class);
    $user          = request()->user();
    return Inertia::render('Home', [
        'recentTenders'   => $tenderService->getRecentTenders(6),
        'stats'           => $tenderService->getStats(),
        'userPlan'        => $user?->subscription_plan ?? 'free',
        'isAuthenticated' => (bool) $user,
    ]);
})->name('home');

// Pricing
Route::get('/pricing', function () {
    $user = request()->user();
    return Inertia::render('Pricing', [
        'userPlan'        => $user?->subscription_plan ?? 'free',
        'isAuthenticated' => (bool) $user,
    ]);
})->name('pricing');

// SEO landing pages
Route::get('/tenders/pakistan', function () {
    $tenders = Tender::where('country_code', 'PK')
        ->where('status', 'Published')
        ->orderBy('closing_at')
        ->limit(20)
        ->get();
    return Inertia::render('Countries/Pakistan', compact('tenders'));
})->name('tenders.pakistan');

Route::get('/tenders/uk', function () {
    $user    = request()->user();
    $tenders = [];
    if ($user && $user->hasActivePlan(['starter', 'professional', 'enterprise'])) {
        $tenders = Tender::where('country_code', 'GB')
            ->where('status', 'Published')
            ->orderBy('closing_at')
            ->limit(20)
            ->get();
    }
    return Inertia::render('Countries/UK', compact('tenders', 'user'));
})->name('tenders.uk');

Route::get('/tenders/usa', function () {
    $user    = request()->user();
    $tenders = [];
    if ($user && $user->hasActivePlan(['professional', 'enterprise'])) {
        $tenders = Tender::where('country_code', 'US')
            ->where('status', 'Published')
            ->orderBy('closing_at')
            ->limit(20)
            ->get();
    }
    return Inertia::render('Countries/USA', compact('tenders', 'user'));
})->name('tenders.usa');

Route::get('/tenders/world-bank', function () {
    $user    = request()->user();
    $tenders = [];
    if ($user && $user->hasActivePlan(['professional', 'enterprise'])) {
        $tenders = Tender::where('source', 'world_bank')
            ->where('status', 'Published')
            ->orderBy('closing_at')
            ->limit(20)
            ->get();
    }
    return Inertia::render('Countries/WorldBank', compact('tenders', 'user'));
})->name('tenders.world-bank');

Route::get('/tenders/un', function () {
    $user    = request()->user();
    $tenders = [];
    if ($user && $user->hasActivePlan(['professional', 'enterprise'])) {
        $tenders = Tender::where('source', 'ungm')
            ->where('status', 'Published')
            ->orderBy('closing_at')
            ->limit(20)
            ->get();
    }
    return Inertia::render('Countries/UN', compact('tenders', 'user'));
})->name('tenders.un');

// Auth routes
Route::get('/login',    [UserController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login',   [UserController::class, 'login'])->middleware('guest');
Route::get('/register', [UserController::class, 'showRegister'])->name('register')->middleware('guest');
Route::post('/register', [UserController::class, 'register'])->middleware('guest');
Route::post('/logout',   [UserController::class, 'logout'])->name('logout')->middleware('auth');

// Email verification
Route::get('/email/verify', fn () => Inertia::render('Auth/VerifyEmail'))
    ->middleware('auth')->name('verification.notice');
Route::get('/email/verify/{id}/{hash}', [UserController::class, 'verifyEmail'])
    ->middleware(['auth', 'signed'])->name('verification.verify');
Route::post('/email/verification-notification', [UserController::class, 'resendVerification'])
    ->middleware(['auth', 'throttle:6,1'])->name('verification.send');

// Dashboard (auth required)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');
});

// Robots.txt
Route::get('/robots.txt', function () {
    return response("User-agent: *\nAllow: /\nSitemap: " . url('/sitemap.xml'), 200, [
        'Content-Type' => 'text/plain',
    ]);
});
