<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next, string $mode = 'required')
    {
        $plainToken = $request->bearerToken();

        if (!$plainToken) {
            if ($mode === 'optional') return $next($request);
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $digest = hash('sha256', $plainToken);
        $apiToken = ApiToken::where('token', $digest)->first();

        // Existing bcrypt tokens remain valid and migrate on first use. New
        // random bearer tokens use the existing unique index for direct lookup.
        if (!$apiToken) {
            foreach (ApiToken::where(fn ($q) => $q->where('token', 'like', '$2%')->orWhere('token', 'like', '$argon%'))->cursor() as $legacyToken) {
                if (Hash::check($plainToken, $legacyToken->token)) {
                    $apiToken = $legacyToken;
                    $apiToken->forceFill(['token' => $digest])->save();
                    break;
                }
            }
        }

        if (!$apiToken || !$apiToken->user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$apiToken->last_used_at || $apiToken->last_used_at->lt(now()->subMinutes(5))) {
            $apiToken->forceFill(['last_used_at' => now()])->save();
        }
        $request->setUserResolver(fn () => $apiToken->user);
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}
