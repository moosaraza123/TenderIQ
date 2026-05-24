<?php

use App\Modules\Api\Controllers\ApiTokenController;
use App\Modules\Api\Controllers\TenderApiController;
use App\Modules\Api\Middleware\ApiAuthMiddleware;
use Illuminate\Support\Facades\Route;

// Token management (web auth)
Route::middleware('auth')->prefix('api-access')->group(function () {
    Route::get('/', [ApiTokenController::class, 'index'])->name('api.tokens.index');
    Route::post('/tokens', [ApiTokenController::class, 'store'])->name('api.tokens.store');
    Route::delete('/tokens/{id}', [ApiTokenController::class, 'destroy'])->name('api.tokens.destroy');
});

// REST API endpoints (token auth)
Route::prefix('api/v1')->middleware(ApiAuthMiddleware::class)->group(function () {
    Route::get('/tenders', [TenderApiController::class, 'index']);
    Route::get('/tenders/{tenderNumber}', [TenderApiController::class, 'show']);
});
