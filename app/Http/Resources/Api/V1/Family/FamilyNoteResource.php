<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Family;

use App\Http\Resources\Api\V1\Proposal\ProposalUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FamilyNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'profile' => $this->whenLoaded('profile', fn () => new ProposalUserResource($this->profile)),
            'author' => $this->whenLoaded('author', fn () => new ProposalUserResource($this->author)),
            'note' => $this->note,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
