<?php

namespace App\Modules\Alert\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Alert\Models\AlertSubscription;
use App\Modules\Alert\Requests\CreateAlertRequest;
use App\Modules\Payment\Services\SubscriptionService;
use App\Modules\Tender\Services\TenderAccessService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function __construct(
        private readonly TenderAccessService $accessService,
        private readonly SubscriptionService $subscriptionService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Alerts/Index', [
            'alerts'    => $user->alerts()->orderByDesc('created_at')->get(),
            'canCreate' => $this->accessService->canCreateAlert($user),
            'limit'     => $user->alertLimit(),
        ]);
    }

    public function store(CreateAlertRequest $request)
    {
        $user = $request->user();

        if (! $this->accessService->canCreateAlert($user)) {
            return back()->with('error', 'You have reached your alert limit. Upgrade your plan to create more alerts.');
        }

        $data = $request->validated();
        $plan = $this->subscriptionService->getUserPlan($user);

        // Free users can only use daily digest — no instant alerts
        if ($plan === 'free') {
            $data['frequency'] = 'daily';
        }

        // Only enterprise can set webhook_url
        if ($plan !== 'enterprise') {
            unset($data['webhook_url']);
        }

        $user->alerts()->create($data);

        return back()->with('success', 'Alert created successfully.');
    }

    public function toggle(Request $request, AlertSubscription $alert)
    {
        abort_unless($alert->user_id === $request->user()->id, 403);
        $alert->update(['is_active' => ! $alert->is_active]);
        return back();
    }

    public function destroy(Request $request, AlertSubscription $alert)
    {
        abort_unless($alert->user_id === $request->user()->id, 403);
        $alert->delete();
        return back()->with('success', 'Alert deleted.');
    }

    public function unsubscribe(int $userId)
    {
        AlertSubscription::where('user_id', $userId)->update(['is_active' => false]);
        return redirect('/')->with('success', 'You have been unsubscribed from all alerts.');
    }
}
