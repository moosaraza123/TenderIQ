<?php

use App\Modules\Tender\Controllers\TenderController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:tenders')->group(function () {
    Route::get('/tenders', [TenderController::class, 'index'])->name('tenders.index');
    Route::get('/tenders/export', [TenderController::class, 'export'])->name('tenders.export')->middleware('auth');
    Route::get('/tenders/{tenderNumber}', [TenderController::class, 'show'])->name('tenders.show');
});

Route::get('/sitemap.xml', [TenderController::class, 'sitemap'])->name('sitemap');
