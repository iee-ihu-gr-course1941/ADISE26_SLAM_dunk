<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

//test user for postman
class PostmanTestUser
{
    public function handle(Request $request, Closure $next)
    {
        if (!app()->environment('local')) {
            abort(403, 'poostman test identities are only available locally');
        }

        $userId = $request->header('X-Test-User-Id');
        if ($userId === null) {
            abort(401, 'missing X-Test-User-Id header');
        }
        if (!ctype_digit((string) $userId) || (int) $userId < 1) {
            abort(401, 'test user ID must be a positive integer');
        }

        $user = User::find((int) $userId);
        if ($user === null) {
            abort(401, 'Test user does not exist.');
        }

        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        return $next($request);
    }
}

