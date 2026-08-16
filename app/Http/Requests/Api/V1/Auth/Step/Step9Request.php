<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth\Step;

use App\Http\Requests\Api\V1\ApiFormRequest;

class Step9Request extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'profile_photo' => ['nullable', 'string', 'max:255'],
            'cover_photo' => ['nullable', 'string', 'max:255'],
            'video_introduction' => ['nullable', 'string', 'max:255'],
            'voice_introduction' => ['nullable', 'string', 'max:255'],
            'private_gallery' => ['nullable', 'array'],
            'private_gallery.*' => ['string', 'max:255'],
        ];
    }
}
