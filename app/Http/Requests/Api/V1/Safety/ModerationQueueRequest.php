<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Safety;

use App\Http\Requests\Api\V1\ApiFormRequest;

class ModerationQueueRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', 'string', 'in:open,under_review,resolved,dismissed'],
            'severity' => ['sometimes', 'nullable', 'string', 'in:low,medium,high,critical'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
