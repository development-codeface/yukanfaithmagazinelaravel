<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        $plainToken = $request->bearerToken();

        if (!$plainToken) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $tokens = ApiToken::with('user')->get();

        $apiToken = $tokens->first(function (ApiToken $token) use ($plainToken) {
            return Hash::check($plainToken, $token->token);
        });

        if (!$apiToken || !$apiToken->user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $apiToken->forceFill(['last_used_at' => now()])->save();
        $request->setUserResolver(fn () => $apiToken->user);
        $request->attributes->set('api_token', $apiToken);

        return $next($request);
    }
}
