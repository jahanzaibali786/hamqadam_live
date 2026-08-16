<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Proposal;

use App\Http\Requests\Api\V1\ApiFormRequest;

class StoreProposalNoteRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:2000'],
        ];
    }
}
