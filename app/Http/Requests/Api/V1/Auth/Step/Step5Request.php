<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step5Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'country_code' => ['required', 'string', 'max:10'],
            'phone' => ['required', 'string', 'max:15'],
            'education_level' => ['nullable', 'string', 'max:100'],
            'degree' => ['nullable', 'string', 'max:255'],
            'institution' => ['nullable', 'string', 'max:255'],
            'education_start' => ['nullable', 'numeric'],
            'employment_status' => ['nullable', 'string', 'max:20'],
            'profession' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'career_start' => ['nullable', 'numeric'],
            'annual_income' => ['nullable', 'numeric', 'min:0'],
            'work_location_city' => ['nullable', 'string', 'max:255'],
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
