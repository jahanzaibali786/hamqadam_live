<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Proposal;

use App\Enums\ProposalStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProposalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof ProposalStatus ? $this->status : ProposalStatus::tryFrom((int) $this->status);

        return [
            'id' => $this->id,
            'status' => $status?->label(),
            'status_value' => $status?->value,
            'initial_note' => $this->initial_note,
            'compatibility_percentage' => $this->compatibility_snapshot,
            'sender' => $this->whenLoaded('sender', fn () => new ProposalUserResource($this->sender)),
            'recipient' => $this->whenLoaded('recipient', fn () => new ProposalUserResource($this->recipient)),
            'notes' => $this->whenLoaded('notes', fn () => ProposalNoteResource::collection($this->notes)),
            'timeline' => $this->whenLoaded('events', fn () => ProposalEventResource::collection($this->events)),
            'responded_at' => optional($this->responded_at)->toISOString(),
            'withdrawn_at' => optional($this->withdrawn_at)->toISOString(),
            'cancelled_at' => optional($this->cancelled_at)->toISOString(),
            'expires_at' => optional($this->expires_at)->toISOString(),
            'expired_at' => optional($this->expired_at)->toISOString(),
            'expires_in_seconds' => $this->expires_at && ! $this->expired_at ? max(0, now()->diffInSeconds($this->expires_at, false)) : null,
            'created_at' => optional($this->created_at)->toISOString(),
            'updated_at' => optional($this->updated_at)->toISOString(),
        ];
    }
}
