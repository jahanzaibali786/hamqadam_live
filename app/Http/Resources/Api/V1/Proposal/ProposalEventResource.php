<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Proposal;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'event' => $this->event,
            'note' => $this->note,
            'metadata' => $this->metadata,
            'actor' => $this->whenLoaded('actor', fn () => $this->actor ? new ProposalUserResource($this->actor) : null),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
