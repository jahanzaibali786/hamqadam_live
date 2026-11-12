<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\AuthEmailLookup;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.passwords.email');
    }

    /**
     * Keep an identical response for recognized and unknown accounts,
     * including soft-deleted accounts, to avoid account enumeration.
     */
    public function store(Request $request): View
    {
        $request->validate(['email' => ['required', 'string', 'max:255']]);
        $identifier = trim((string) $request->input('email'));
        $isEmail = filter_var(AuthEmailLookup::normalize($identifier), FILTER_VALIDATE_EMAIL) !== false;

        if ($isEmail) {
            $identifier = AuthEmailLookup::normalize($identifier);
            $user = AuthEmailLookup::find($identifier);
        } else {
            $user = User::where('phone', $identifier)->first();
        }

        if ($user) {
            $user->verification_code = random_int(100000, 999999);
            $user->save();

            if ($isEmail) {
                EmailUtility::password_reset_email($user, $user->verification_code);
            } else {
                SmsUtility::password_reset($user, $user->verification_code);
            }
        }

        // Always show the code-entry screen, even if the account is missing.
        // The reset POST separately validates both identity and one-time code.
        return view($isEmail ? 'auth.passwords.reset' : 'addons.otp_systems.frontend.auth.passwords.reset_with_phone', [
            'email' => $isEmail ? $identifier : null,
        ])->with('resetNotice', 'If an eligible account exists, a verification code has been sent. Check your email or phone.');
    }
}
