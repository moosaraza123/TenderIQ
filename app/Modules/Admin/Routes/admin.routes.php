<?php

use App\Modules\Admin\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'isAdmin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('/tenders', [AdminController::class, 'tenders'])->name('tenders');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/trigger-scraper', [AdminController::class, 'triggerScraper'])->name('trigger-scraper');
    Route::patch('/tenders/{tender}/featured', [AdminController::class, 'toggleFeatured'])->name('tenders.featured');
});
