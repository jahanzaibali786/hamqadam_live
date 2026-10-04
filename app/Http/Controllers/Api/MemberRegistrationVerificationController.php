<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RegistrationVerificationCode;
use App\Models\User;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use Illuminate\Http\Request;

class MemberRegistrationVerificationController extends Controller
{
   
    public function sendRegVerificationCode(Request $request)
    {
        $email = !empty($request->email) ? trim(strtolower($request->email)) : null;
        $cleanPhone = !empty($request->phone) ? preg_replace('/\D+/', '', $request->phone) : null;
        $countryCode = !empty($request->country_code) ? preg_replace('/\D+/', '', $request->country_code) : '';
        $phone = $cleanPhone ? '+' . $countryCode . $cleanPhone : null;

        if ($email) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return response()->json(['status' => 0, 'message' => translate('Please enter a valid email address.')]);
            }
            if (User::where('email', $email)->exists()) {
                return response()->json(['status' => 0, 'message' => translate('Email already exists. Please use a different email.')]);
            }
        }

        if ($phone) {
            if (User::where('phone', $phone)->orWhere('phone', $cleanPhone)->exists()) {
                return response()->json(['status' => 0, 'message' => translate('Phone already exists. Please use a different phone number.')]);
            }
        }

        if (!$email && !$phone) {
            return response()->json(['status' => 0, 'message' => translate('Please enter your email or phone number.')]);
        }

        $verificationCode = rand(100000, 999999);

        // Invalidate and delete ALL previous OTP requests for this email or phone so old codes cannot be reused
        RegistrationVerificationCode::where(function ($q) use ($email, $phone, $cleanPhone) {
            if ($email) {
                $q->where('email', $email);
            }
            if ($phone) {
                $q->orWhere('phone', $phone);
                if ($cleanPhone) {
                    $q->orWhere('phone', $cleanPhone);
                }
            }
        })->delete();

        RegistrationVerificationCode::create([
            'email'       => $email,
            'phone'       => $phone,
            'code'        => $verificationCode,
            'is_verified' => 0,
        ]);

        $success = 1;

        if ($email) {
            try {
                $success = EmailUtility::email_verification_for_registration_user('email_registration_verification', $email, $verificationCode);
            } catch (\Exception $e) {
                $success = 0;
            }
        } elseif ($phone != null && addon_activation('otp_system') && (get_sms_template('mobile_registration_verification', 'status') == 1)) {
            try {
                SmsUtility::mobile_registration_verification($phone, $verificationCode);
            } catch (\Exception $e) {
                $success = 0;
            }
        }

        if ($success) {
            return response()->json(['status' => 1, 'message' => translate('Verification code sent successfully.')]);
        } else {
            return response()->json(['status' => 0, 'message' => translate('Verification code sending failed.')]);
        }
    }

    public function regVerifyCodeConfirmation(Request $request)
    {
        $email = !empty($request->email) ? trim(strtolower($request->email)) : null;
        $cleanPhone = !empty($request->phone) ? preg_replace('/\D+/', '', $request->phone) : null;
        $countryCode = !empty($request->country_code) ? preg_replace('/\D+/', '', $request->country_code) : '';
        $phone = $cleanPhone ? '+' . $countryCode . $cleanPhone : null;
        $code = isset($request->code) ? trim((string)$request->code) : '';

        if (empty($code)) {
            return response()->json(['status' => 0, 'message' => translate('Please enter the verification code.')]);
        }

        if ($email && User::where('email', $email)->exists()) {
            return response()->json(['status' => 0, 'message' => translate('This email is already registered. Please use a different email.')]);
        }
        if ($phone && (User::where('phone', $phone)->orWhere('phone', $cleanPhone)->exists())) {
            return response()->json(['status' => 0, 'message' => translate('This phone number is already registered. Please use a different phone number.')]);
        }

        // Query the latest unverified code generated within 15 minutes
        $customerVerification = RegistrationVerificationCode::where('code', $code)
            ->where('is_verified', 0)
            ->where(function ($q) use ($email, $phone, $cleanPhone) {
                if ($email) {
                    $q->where('email', $email);
                }
                if ($phone) {
                    $q->orWhere('phone', $phone);
                    if ($cleanPhone) {
                        $q->orWhere('phone', $cleanPhone);
                    }
                }
            })
            ->where('created_at', '>=', now()->subMinutes(15))
            ->latest()
            ->first();

        if ($customerVerification == null) {
            return response()->json(['status' => 0, 'message' => translate('Verification Code did not match or has expired.')]);
        } else {
            $customerVerification->is_verified = 1;
            $customerVerification->save();

            // Clean up any other old verification records for this target
            RegistrationVerificationCode::where('id', '!=', $customerVerification->id)
                ->where(function ($q) use ($email, $phone, $cleanPhone) {
                    if ($email) {
                        $q->where('email', $email);
                    }
                    if ($phone) {
                        $q->orWhere('phone', $phone);
                        if ($cleanPhone) {
                            $q->orWhere('phone', $cleanPhone);
                        }
                    }
                })->delete();

            return response()->json(['status' => 1, 'message' => translate('Verification Successful')]);
        }
    }
}
