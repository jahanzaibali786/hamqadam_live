<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Content;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreCommunityThreadRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }
}
