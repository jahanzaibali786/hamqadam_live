<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiFormRequest;
use App\Support\RegistrationOnboarding;
use Illuminate\Validation\Rule;

class RegisterRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'email' => ['required_without:phone', 'nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required_without:email', 'nullable', 'string', 'max:15', 'unique:users,phone'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'gender' => ['required', 'string', 'max:20'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'on_behalf' => ['nullable', 'integer'],
            'referral_code' => ['nullable', 'string', 'max:100'],
            'device_name' => ['nullable', 'string', 'max:255'],
            'device_type' => ['nullable', Rule::in(['android', 'ios', 'web', 'unknown'])],
            'device_id' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $countryCode = preg_replace('/\D+/', '', (string) $this->input('country_code', ''));
            $phone = preg_replace('/\D+/', '', (string) $this->input('phone', ''));

            if ($countryCode === '92' && strlen($phone) > 11) {
                $validator->errors()->add('phone', translate('Pakistan mobile number must be 11 digits or fewer.'));
            } elseif ($countryCode !== '' && strlen($phone) > 15) {
                $validator->errors()->add('phone', translate('Phone number is too long for the selected country.'));
            }
        });
    }
}
