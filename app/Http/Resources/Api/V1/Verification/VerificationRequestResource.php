<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Verification;

use App\Enums\VerificationRequestStatus;
use App\Http\Resources\Api\V1\Proposal\ProposalUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VerificationRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $status = $this->status instanceof VerificationRequestStatus ? $this->status->value : $this->status;

        return [
            'id' => $this->id,
            'user' => $this->whenLoaded('user', fn () => new ProposalUserResource($this->user)),
            'status' => $status,
            'cnic_number' => $this->cnic_number,
            'face_match_status' => $this->face_match_status,
            'face_match_score' => $this->face_match_score,
            'rejection_reason' => $this->rejection_reason,
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer ? new ProposalUserResource($this->reviewer) : null),
            'documents' => $this->whenLoaded('documents', fn () => VerificationDocumentResource::collection($this->documents)),
            'submitted_at' => optional($this->submitted_at)->toISOString(),
            'reviewed_at' => optional($this->reviewed_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
