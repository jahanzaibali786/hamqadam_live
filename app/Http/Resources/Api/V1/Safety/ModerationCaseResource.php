<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Safety;

use App\Http\Resources\Api\V1\Proposal\ProposalUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ModerationCaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reported_user' => $this->whenLoaded('reportedUser', fn () => new ProposalUserResource($this->reportedUser)),
            'reporter' => $this->whenLoaded('reporter', fn () => $this->reporter ? new ProposalUserResource($this->reporter) : null),
            'case_type' => $this->case_type,
            'status' => is_object($this->status) ? $this->status->value : $this->status,
            'severity' => $this->severity,
            'reason' => $this->reason,
            'evidence' => $this->evidence,
            'resolution_note' => $this->resolution_note,
            'resolved_at' => optional($this->resolved_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
