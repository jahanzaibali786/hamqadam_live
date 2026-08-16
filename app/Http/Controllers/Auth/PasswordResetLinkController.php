<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use App\Models\User;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.passwords.email');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
         if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
                $user = User::where('email', $request->email)->first();
                if ($user != null) {
                    $user->verification_code = rand(100000,999999);
                    $user->save();

                    EmailUtility::password_reset_email($user, $user->verification_code);
                    return view('auth.passwords.reset');
                }
                else {
                    flash(translate('No account exists with this email'))->error();
                    return back();
                }
            }
            else{
                $user = User::where('phone', $request->email)->first();
                if ($user != null) {
                    $user->verification_code = rand(100000,999999);
                    $user->save();

                    SmsUtility::password_reset($user , $user->verification_code);
                    return view('addons.otp_systems.frontend.auth.passwords.reset_with_phone');
                }
                else {
                    flash(translate('No account exists with this phone number'))->error();
                    return back();
                }
            }
    }
}
