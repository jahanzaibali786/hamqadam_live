<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Family;

use App\Http\Resources\Api\V1\Proposal\ProposalUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyGuardianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'profile' => $this->whenLoaded('profile', fn () => new ProposalUserResource($this->profile)),
            'guardian' => $this->whenLoaded('guardian', fn () => new ProposalUserResource($this->guardian)),
            'relationship' => $this->relationship,
            'status' => $this->status,
            'permissions' => $this->permissions ?? [],
            'approved_at' => optional($this->approved_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
