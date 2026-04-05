<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFrozenAccount
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isFrozen()) {
            return response()->json([
                'message' => 'Your account is currently frozen. Please contact support.',
            ], 403);
        }

        return $next($request);
    }
}
