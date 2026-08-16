<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdateVisibilityRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'hide_profile' => ['required', 'boolean'],
        ];
    }
}

