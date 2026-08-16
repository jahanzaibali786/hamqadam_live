<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Family;

use App\Http\Resources\Api\V1\Proposal\ProposalUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyApprovalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'profile' => $this->whenLoaded('profile', fn () => new ProposalUserResource($this->profile)),
            'guardian' => $this->whenLoaded('guardian', fn () => new ProposalUserResource($this->guardian)),
            'request_type' => $this->request_type,
            'status' => $this->status,
            'payload' => $this->payload,
            'decision_note' => $this->decision_note,
            'decided_at' => optional($this->decided_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
