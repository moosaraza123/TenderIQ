<?php

namespace App\Modules\Api\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Api\Models\ApiToken;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiTokenController extends Controller
{
    public function index(Request $request): Response
    {
        $tokens = $request->user()
            ->apiTokens()
            ->select(['id', 'name', 'last_used_at', 'calls_today', 'created_at'])
            ->latest()
            ->get();

        return Inertia::render('Api/Index', [
            'tokens'    => $tokens,
            'callLimit' => $request->user()->apiCallLimit(),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $request->validate(['name' => 'required|string|max:100']);

        $user = $request->user();

        if ($user->apiTokens()->count() >= 5) {
            return back()->withErrors(['name' => 'Maximum of 5 API tokens allowed.']);
        }

        [$token, $record] = ApiToken::generate($user->id, $request->name);

        return back()->with([
            'new_token' => $token,
            'token_id'  => $record->id,
        ]);
    }

    public function destroy(Request $request, int $id): \Illuminate\Http\RedirectResponse
    {
        $request->user()->apiTokens()->where('id', $id)->delete();

        return back()->with('status', 'Token deleted.');
    }
}
