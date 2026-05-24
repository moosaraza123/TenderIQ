<?php

use App\Modules\Payment\Controllers\SubscriptionController;
use App\Modules\Payment\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Stripe webhook — no auth, Cashier verifies signature
Route::post('/stripe/webhook', [WebhookController::class, 'handleWebhook'])->name('stripe.webhook');

// Authenticated subscription routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::get('/subscription/success',   [SubscriptionController::class, 'success'])->name('subscription.success');
    Route::get('/subscription/portal',    [SubscriptionController::class, 'portal'])->name('subscription.portal');
    Route::post('/subscription/cancel',   [SubscriptionController::class, 'cancel'])->name('subscription.cancel');
    Route::post('/subscription/resume',   [SubscriptionController::class, 'resume'])->name('subscription.resume');
    Route::post('/subscription/swap',     [SubscriptionController::class, 'swap'])->name('subscription.swap');
    Route::get('/subscription/status',    [SubscriptionController::class, 'status'])->name('subscription.status');
});
