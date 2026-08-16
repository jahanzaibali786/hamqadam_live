<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->user_type,
            'code' => $this->code,
            'name' => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')),
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'membership' => $this->membership,
            'email_verified_at' => optional($this->email_verified_at)->toISOString(),
            'approved' => (bool) $this->approved,
            'blocked' => (bool) $this->blocked,
            'deactivated' => (bool) $this->deactivated,
            'photo' => $this->photo ? uploaded_asset($this->photo) : null,
            'registration_completed' => (bool) ($this->member?->registration_completed_at ?? ($this->approved && $this->membership)),
            'member' => $this->whenLoaded('member', fn () => [
                'id' => $this->member?->id,
                'gender' => $this->member?->gender,
                'birthday' => $this->member?->birthday ? (string) $this->member->birthday : null,
                'current_package_id' => $this->member?->current_package_id,
                'current_package_name' => $this->member?->package?->name,
                'coin_balance' => (int) ($this->member?->remaining_interest ?? 0),
                'package_validity' => $this->member?->package_validity,
                'registration_reward_applied' => (bool) $this->has_purchased_free_package,
            ]),
        ];
    }
}
