<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCmsEmailIsVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user()?->fresh();

        if (! $user) {
            return redirect()->route('user.login');
        }

        if ($user->email_verified_at || (int) $user->approved === 1) {
            return $next($request);
        }

        return $request->expectsJson()
            ? response()->json(['message' => translate('Email is not verified!')], 403)
            : redirect()->route('verification.notice');
    }
}
