<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthEmailLookup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    public function showResetForm()
    {
        return view('auth.passwords.reset');
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = AuthEmailLookup::find((string) $request->email);
        if ($user && ! hash_equals((string) $user->verification_code, (string) $request->code)) {
            $user = null;
        }
        if ($user && empty($user->verification_code)) {
            $user = null;
        }

        if (! $user) {
            flash(translate('Invalid email or verification code'))->error();

            return back()->withInput($request->only('email'));
        }

        $user->password = Hash::make($request->password);
        $user->verification_code = null;
        $user->save();

        flash(translate('Password has been updated, you can login now'))->success();

        return redirect()->route('user.login');
    }
}
