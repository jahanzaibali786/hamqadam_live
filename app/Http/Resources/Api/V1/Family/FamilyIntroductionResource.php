<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Family;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyIntroductionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $proposal = $this->proposal;

        return [
            'id' => $this->id,
            'proposal_id' => $this->proposal_id,
            'status' => $this->status,
            'initiated_by' => $this->initiated_by,
            'message' => $this->message,
            'other_member' => $proposal ? [
                'id' => $proposal->user_id === $this->initiated_by ? $proposal->interested_by : $proposal->user_id,
            ] : null,
            'accepted_at' => optional($this->accepted_at)->toISOString(),
            'cancelled_at' => optional($this->cancelled_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
