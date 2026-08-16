<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Utility\SmsUtility;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class OTPVerificationController extends Controller
{
    public function verification(Request $request)
    {
        $user = $request->user()?->fresh();

        if ($user && ($user->email_verified_at || (int) $user->approved === 1)) {
            return redirect()->route('dashboard');
        }

        return view()->exists('auth.verify') ? view('auth.verify') : redirect()->route('home');
    }

    public function send_code(User $user): void
    {
        if (! $user->verification_code) {
            $user->verification_code = rand(100000, 999999);
            $user->save();
        }

        if ($user->phone) {
            SmsUtility::mobile_number_verification($user);
        }
    }

    public function verify_phone(Request $request)
    {
        $request->validate([
            'verification_code' => ['required'],
        ]);

        $user = $request->user();

        if ($user && hash_equals((string) $user->verification_code, (string) $request->verification_code)) {
            $user->email_verified_at = Carbon::now();
            $user->verification_code = null;
            $user->save();

            flash(translate('Your phone number has been verified successfully'))->success();

            return redirect()->route('dashboard');
        }

        flash(translate('Verification code does not match'))->error();

        return back();
    }

    public function resend_verificcation_code(Request $request)
    {
        $user = $request->user();

        if ($user) {
            $user->verification_code = rand(100000, 999999);
            $user->save();
            $this->send_code($user);
        }

        flash(translate('Verification code has been resent'))->success();

        return back();
    }

    public function reset_password_with_code(Request $request)
    {
        $request->validate([
            'phone' => ['required'],
            'code' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::where('phone', $request->phone)
            ->where('verification_code', $request->code)
            ->first();

        if (! $user) {
            flash(translate('Invalid phone number or verification code'))->error();

            return back();
        }

        $user->password = Hash::make($request->password);
        $user->verification_code = null;
        $user->save();

        flash(translate('Password has been updated, you can login now'))->success();

        return redirect()->route('user.login');
    }
}

