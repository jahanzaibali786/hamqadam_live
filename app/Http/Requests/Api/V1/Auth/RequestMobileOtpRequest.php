<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiFormRequest;

class RequestMobileOtpRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:15'],
            'country_code' => ['nullable', 'string', 'max:10'],
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
