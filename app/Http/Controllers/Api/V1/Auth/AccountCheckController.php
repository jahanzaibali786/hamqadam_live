<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Models\ProfileVerificationRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Registration-time account checks + in-app password change.
 *
 * check(): the final registration screens (email / phone / CNIC) call this
 * field-by-field so the member sees "Available" in green the moment an input
 * is free, or a red "already registered" line before submit. Guest-callable:
 * no user exists yet, hence public + throttled.
 *
 * changePassword(): the drawer's Change Password — verifies the CURRENT
 * password, then stores the new one. A wrong current password is a 422 the
 * app shows inline, never a lockout.
 */
class AccountCheckController extends ApiController
{
    /** POST /auth/check — { type: email|phone|cnic, value, exclude_user_id? } */
    public function check(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'string', Rule::in(['email', 'phone', 'cnic'])],
            'value' => ['required', 'string', 'max:255'],
            'exclude_user_id' => ['nullable', 'integer'],
        ]);

        $value = trim($data['value']);
        $excludeUserId = (int) ($request->user()?->id ?? $data['exclude_user_id'] ?? 0);

        switch ($data['type']) {
            case 'email':
                $available = ! User::where('email', $value)
                    ->when($excludeUserId > 0, fn ($q) => $q->where('id', '!=', $excludeUserId))
                    ->exists();
                $normalized = $value;
                break;

            case 'phone':
                // Store the digits-only form the way step5 does, but phone is
                // typed with spaces/dashes/+: normalise BOTH sides to digits
                // before comparing, and fall back to the last-10 comparison
                // for country-code ambiguity (0300... vs 92300...).
                $digits = preg_replace('/\D+/', '', $value) ?? '';
                $tail = strlen($digits) > 10 ? substr($digits, -10) : $digits;
                $available = ! User::where(function ($q) use ($value, $digits, $tail) {
                    $q->where('phone', $value)
                        ->orWhere('phone', $digits)
                        ->orWhereRaw("REGEXP_REPLACE(phone, '[^0-9]', '') = ?", [$digits])
                        ->orWhereRaw("RIGHT(REGEXP_REPLACE(phone, '[^0-9]', ''), 10) = ?", [$tail]);
                })
                    ->when($excludeUserId > 0, fn ($q) => $q->where('id', '!=', $excludeUserId))
                    ->exists();
                $normalized = $digits;
                break;

            case 'cnic':
            default:
                // CNICs live on profile_verification_requests (step 13), not
                // users. Compare digits-only again, ignoring separators.
                $digits = preg_replace('/\D+/', '', $value) ?? '';
                $available = ! ProfileVerificationRequest::where(function ($q) use ($value, $digits) {
                    $q->where('cnic_number', $value)
                        ->orWhereRaw("REPLACE(REPLACE(REPLACE(cnic_number, '-', ''), ' ', ''), '_', '') = ?", [$digits]);
                })
                    ->when($excludeUserId > 0, fn ($q) => $q->where('user_id', '!=', $excludeUserId))
                    ->exists();
                $normalized = $digits;
                break;
        }

        return $this->success([
            'type' => $data['type'],
            'value' => $normalized,
            'available' => $available,
            'message' => $available
                ? ucfirst($data['type']).' is available.'
                : 'This '.$data['type'].' is already registered.',
        ]);
    }

    /** POST /auth/change-password — { current_password, password, password_confirmation } */
    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();

        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => [translate('Your current password is incorrect.')],
            ]);
        }

        if (Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => [translate('The new password must be different from the current one.')],
            ]);
        }

        $user->forceFill(['password' => Hash::make($data['password'])])->save();

        return $this->success([
            'changed' => true,
        ], 'Password changed successfully.');
    }
}
