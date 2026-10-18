<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistrationVerificationCode;
use App\Models\User;
use App\Utility\EmailUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;

/** Email-only registration OTP, as required by the approved 30-feature scope. */
class MemberRegistrationVerificationController extends Controller
{
    private function email(Request $request): string
    {
        return trim(mb_strtolower((string) $request->input('email', '')));
    }

    private function respond(string $message, int $status = 0)
    {
        return response()->json(['status' => $status, 'message' => translate($message)]);
    }

    public function sendRegVerificationCode(Request $request)
    {
        $email = $this->email($request);
        if (!$email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respond('Please enter a valid email address.');
        }
        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return $this->respond('Email already exists. Please use a different email.');
        }

        $identifier = 'email:'.$email;
        $limitKey = 'registration-otp-send:'.hash('sha256', $request->ip().':'.$identifier);
        if (RateLimiter::tooManyAttempts($limitKey, 5)) {
            return $this->respond('Too many verification requests. Please try again later.');
        }
        RateLimiter::hit($limitKey, 600);

        // An email change or resend invalidates any earlier verification proof.
        $request->session()->forget('registration_otp_verified');
        $request->session()->put('registration_otp_pending', $identifier);
        $code = (string) random_int(100000, 999999);
        RegistrationVerificationCode::where('email', $email)->delete();
        $verification = RegistrationVerificationCode::create([
            'email' => $email, 'phone' => null, 'code' => $code, 'is_verified' => 0,
        ]);

        try {
            $success = EmailUtility::email_verification_for_registration_user('email_registration_verification', $email, $code);
            if ($success === false || $success === 0) {
                throw new \RuntimeException('The mail service reported an OTP delivery failure.');
            }
        } catch (\Throwable $exception) {
            $verification->delete();
            report($exception);
            return $this->respond('Verification code sending failed.');
        }
        return $this->respond('Verification code sent successfully.', 1);
    }

    public function regVerifyCodeConfirmation(Request $request)
    {
        $email = $this->email($request);
        $code = trim((string) $request->input('code', ''));
        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respond('Please enter a valid email address.');
        }
        if (! preg_match('/^\d{6}$/', $code)) {
            return $this->respond('Please enter a valid verification code.');
        }
        $identifier = 'email:'.$email;
        if ($request->session()->get('registration_otp_pending') !== $identifier) {
            return $this->respond('Please request a new verification code for the current email address.');
        }
        if (User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            return $this->respond('This email is already registered. Please use a different email.');
        }

        $limitKey = 'registration-otp-check:'.hash('sha256', $request->ip().':'.$identifier);
        if (RateLimiter::tooManyAttempts($limitKey, 5)) {
            return $this->respond('Too many verification attempts. Please request a new code.');
        }
        RateLimiter::hit($limitKey, 900);

        $verified = DB::transaction(function () use ($email, $code): bool {
            $record = RegistrationVerificationCode::where('email', $email)
                ->whereNull('phone')->where('is_verified', 0)
                ->where('created_at', '>=', now()->subMinutes(15))
                ->latest('id')->lockForUpdate()->first();
            if (! $record || ! hash_equals((string) $record->code, $code)) {
                return false;
            }
            $record->is_verified = 1;
            $record->save();
            return true;
        });
        if (! $verified) {
            return $this->respond('Verification Code did not match or has expired.');
        }
        RateLimiter::clear($limitKey);
        $request->session()->put('registration_otp_verified', $identifier);
        $request->session()->forget('registration_otp_pending');
        return $this->respond('Verification Successful', 1);
    }
}
