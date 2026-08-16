<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Proposal;

use App\Http\Requests\Api\V1\ApiFormRequest;

class UpdateProposalMeetingRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:scheduled,completed,cancelled,rescheduled'],
            'scheduled_at' => ['sometimes', 'date'],
            'duration_minutes' => ['sometimes', 'integer', 'min:10', 'max:180'],
            'meeting_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
