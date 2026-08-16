<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Safety;

use App\Http\Requests\Api\V1\ApiFormRequest;

class ResolveModerationCaseRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:resolved,dismissed'],
            'resolution_note' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
