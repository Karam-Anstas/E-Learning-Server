<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckEmailVerification
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user() || !$request->user()->email_verified_at) {
            return response()->json([
                'message' => 'Your email address is not verified. Please verify your email before enrolling in courses.',
                'status' => 'unverified'
            ], 403);
        }

        return $next($request);
    }
} 