<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Proposal;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreProposalMeetingRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'meeting_type' => ['required', 'string', 'in:virtual,family,in_person'],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_minutes' => ['sometimes', 'integer', 'min:10', 'max:180'],
            'meeting_url' => ['sometimes', 'nullable', 'url', 'max:500'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'chaperone_mode' => ['sometimes', 'boolean'],
            'chaperone_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'recording_consent_requested' => ['sometimes', 'boolean'],
            'pre_meeting_questionnaire' => ['sometimes', 'nullable', 'array'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
