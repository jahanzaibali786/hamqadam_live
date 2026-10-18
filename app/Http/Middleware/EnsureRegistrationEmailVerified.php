<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class EnsureRegistrationEmailVerified
{
    public function handle(Request $request, Closure $next)
    {
        if (!get_setting('registration_verification')) {
            return $next($request);
        }

        $email = trim(mb_strtolower((string) $request->input('email', '')));
        $confirmationEmail = trim(mb_strtolower((string) $request->input('email_verify', $email)));
        $proof = $request->session()->get('registration_otp_verified');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL) || $confirmationEmail !== $email || $proof !== 'email:' . $email) {
            $message = 'Please verify the currently selected email address before creating your account.';
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $message, 'errors' => ['email' => [$message]]], 422);
            }
            return back()->withErrors(['email' => $message])->withInput($request->except(['password', 'password_confirmation', 'code']));
        }

        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return back()->withErrors(['email' => 'An account with this email already exists.'])->withInput($request->except(['password', 'password_confirmation', 'code']));
        }

        // The subsequent request reaches the original RegisterController.
        // The proof is scoped to the session and to the exact verified email.
        $response = $next($request);
        if (auth()->check() && auth()->user()->email === $email) {
            $request->session()->forget(['registration_otp_verified', 'registration_otp_pending']);
        }
        return $response;
    }
}
