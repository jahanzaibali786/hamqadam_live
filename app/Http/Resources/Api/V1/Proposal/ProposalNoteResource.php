<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Proposal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalNoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => new ProposalUserResource($this->user)),
            'note' => $this->note,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
