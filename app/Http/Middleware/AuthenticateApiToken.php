<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    /**
     * Обработать входящий запрос.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! $plainToken) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $tokenHash = hash('sha256', $plainToken);

        $apiToken = ApiToken::with('user')
            ->where('token_hash', $tokenHash)
            ->first();

        if (! $apiToken || ! $apiToken->isValid()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $user = $apiToken->user;

        if (! $user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->isBlocked()) {
            return response()->json([
                'message' => 'Your account has been blocked.',
            ], Response::HTTP_FORBIDDEN);
        }

        // Обновить время последнего использования токена
        $apiToken->forceFill(['last_used_at' => now()])->save();

        $user->withAccessToken($apiToken);
        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
