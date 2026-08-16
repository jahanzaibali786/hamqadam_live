<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdatePrivacyRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'show_photo' => ['sometimes', 'boolean'],
            'show_gallery' => ['sometimes', 'boolean'],
            'show_contact' => ['sometimes', 'boolean'],
            'show_email' => ['sometimes', 'boolean'],
            'show_phone' => ['sometimes', 'boolean'],
            'show_location' => ['sometimes', 'boolean'],
            'allow_profile_view_notifications' => ['sometimes', 'boolean'],
            'do_not_disturb' => ['sometimes', 'boolean'],
            'invisible_mode' => ['sometimes', 'boolean'],
        ];
    }
}
