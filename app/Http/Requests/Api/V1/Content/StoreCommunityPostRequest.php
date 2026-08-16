<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Content;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreCommunityPostRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
