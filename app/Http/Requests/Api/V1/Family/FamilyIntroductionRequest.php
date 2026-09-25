<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Family;

use App\Http\Requests\Api\V1\ApiFormRequest;

class FamilyIntroductionRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'proposal_id' => ['required', 'integer'],
            'message' => ['sometimes', 'string', 'max:500'],
            'accept' => ['sometimes', 'boolean'],
        ];
    }
}
