<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
class AuthTokenMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();


        if (!$token) {
            return response()->json([
                'message' => 'Unauthenticated. Missing Bearer token.',
            ], 401); // Error 401 σημαινει Unauthorized
        }

        $user = User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Invalid or expired token.',
            ], 401);
        }

        //Ενημέρωση του "last_active" καθε λεπτο (online status purposes)
        if (!$user->last_active || $user->last_active->lt(now()->subMinute())) {
            $user->timestamps = false; // Μην κάνεις αχρηστα saves
            $user->update(['last_active' => now()]);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
