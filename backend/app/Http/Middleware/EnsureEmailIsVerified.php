<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()?->user_verified) {
            return response()->json(['message' => 'Email verification required'], 403);
        }

        return $next($request);
    }
}
