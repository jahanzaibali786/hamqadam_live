<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Proposal;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreMeetingFeedbackRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'interested_next_step' => ['required', 'boolean'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
