<?php

namespace App\Modules\Payment\Controllers;

use App\Modules\Payment\Mail\PaymentConfirmedMail;
use App\Modules\Payment\Mail\PaymentFailedMail;
use App\Modules\Payment\Services\SubscriptionService;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class WebhookController extends CashierWebhookController
{
    public function __construct(private readonly SubscriptionService $subscriptionService) {}

    protected function handleCustomerSubscriptionCreated(array $payload): void
    {
        parent::handleCustomerSubscriptionCreated($payload);
        $this->syncUserPlan($payload);
    }

    protected function handleCustomerSubscriptionUpdated(array $payload): void
    {
        parent::handleCustomerSubscriptionUpdated($payload);
        $this->syncUserPlan($payload);
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): void
    {
        parent::handleCustomerSubscriptionDeleted($payload);

        $user = $this->findUser($payload);
        if ($user) {
            $user->update(['subscription_plan' => 'free']);
        }
    }

    protected function handleInvoicePaymentSucceeded(array $payload): void
    {
        $user = $this->findUser($payload);
        if ($user) {
            $this->subscriptionService->syncPlanToUser($user);
            Mail::to($user->email)->queue(new PaymentConfirmedMail($user, $payload));
        }
    }

    protected function handleInvoicePaymentFailed(array $payload): void
    {
        $user = $this->findUser($payload);
        if ($user) {
            Mail::to($user->email)->queue(new PaymentFailedMail($user));
        }
    }

    private function syncUserPlan(array $payload): void
    {
        $user = $this->findUser($payload);
        if ($user) {
            $this->subscriptionService->syncPlanToUser($user);
        }
    }

    private function findUser(array $payload): ?User
    {
        $stripeId = $payload['data']['object']['customer'] ?? null;
        if (! $stripeId) return null;
        return User::where('stripe_id', $stripeId)->first();
    }
}
