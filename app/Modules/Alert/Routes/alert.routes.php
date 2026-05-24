<?php

use App\Modules\Alert\Controllers\AlertController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts', [AlertController::class, 'store'])->name('alerts.store');
    Route::patch('/alerts/{alert}/toggle', [AlertController::class, 'toggle'])->name('alerts.toggle');
    Route::delete('/alerts/{alert}', [AlertController::class, 'destroy'])->name('alerts.destroy');
});

Route::get('/alerts/unsubscribe/{user}', [AlertController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('alerts.unsubscribe');
