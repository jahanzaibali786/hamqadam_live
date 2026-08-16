<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step12Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'profile_visibility' => ['nullable', 'string', 'in:public,members_only,hidden'],
            'show_photo' => ['nullable', 'boolean'],
            'show_gallery' => ['nullable', 'boolean'],
            'show_contact' => ['nullable', 'boolean'],
            'show_email' => ['nullable', 'boolean'],
            'show_phone' => ['nullable', 'boolean'],
            'show_location' => ['nullable', 'boolean'],
            'allow_profile_view_notifications' => ['nullable', 'boolean'],
            'do_not_disturb' => ['nullable', 'boolean'],
            'invisible_mode' => ['nullable', 'boolean'],
        ];
    }
}
