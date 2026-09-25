<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Family;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianInvitationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'profile_user_id' => $this->profile_user_id,
            'guardian_user_id' => $this->guardian_user_id,
            'contact' => $this->contact,
            'relationship' => $this->relationship,
            'guardian_role' => $this->guardian_role,
            'is_wali' => (bool) $this->is_wali,
            'permissions' => $this->permissions,
            'status' => $this->status,
            'expires_at' => optional($this->expires_at)->toISOString(),
            'accepted_at' => optional($this->accepted_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
