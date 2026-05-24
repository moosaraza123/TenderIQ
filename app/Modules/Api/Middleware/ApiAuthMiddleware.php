<?php

namespace App\Modules\Api\Middleware;

use App\Modules\Api\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $raw = $request->bearerToken() ?? $request->header('X-API-Key');

        if (! $raw) {
            return response()->json(['error' => 'API token required'], 401);
        }

        $hashedToken = hash('sha256', $raw);

        $tokenRecord = ApiToken::where('token', $hashedToken)->with('user')->first();

        if (! $tokenRecord) {
            return response()->json(['error' => 'Invalid API token'], 401);
        }

        $user  = $tokenRecord->user;
        $limit = $user->apiCallLimit();

        if ($limit !== null) {
            $callsKey  = 'api_calls:' . $tokenRecord->id . ':' . now()->format('Y-m-d');
            $callCount = (int) Cache::get($callsKey, 0);

            if ($callCount >= $limit) {
                return response()->json([
                    'error'       => 'Daily API call limit reached',
                    'limit'       => $limit,
                    'used'        => $callCount,
                    'resets_at'   => now()->endOfDay()->toISOString(),
                ], 429);
            }

            Cache::put($callsKey, $callCount + 1, now()->endOfDay());
        }

        $tokenRecord->update(['last_used_at' => now()]);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
