<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Proposal;

use App\Http\Requests\Api\V1\ApiFormRequest;

class ProposalListRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'direction' => ['sometimes', 'nullable', 'string', 'in:sent,received,all'],
            'status' => ['sometimes', 'nullable', 'string', 'in:pending,accepted,rejected,withdrawn,cancelled,expired'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }
}
