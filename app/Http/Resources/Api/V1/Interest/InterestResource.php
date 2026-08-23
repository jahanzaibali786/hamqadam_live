<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Interest;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InterestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewerId = $request->user()?->id;
        // `interested_by` is the sender, `user_id` the recipient.
        $isSender = (int) $this->interested_by === (int) $viewerId;
        $counterpart = $isSender ? $this->user : $this->sender;

        return [
            'id' => $this->id,
            'direction' => $isSender ? 'sent' : 'received',
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'initial_note' => $this->initial_note,
            'can_respond' => ! $isSender && $this->status?->value === 0,
            'can_withdraw' => $isSender && $this->status?->value === 0,
            'member' => $counterpart ? [
                'id' => $counterpart->id,
                'code' => $counterpart->code,
                'name' => trim(($counterpart->first_name ?? '').' '.($counterpart->last_name ?? '')),
                'photo' => $counterpart->photo ? uploaded_asset($counterpart->photo) : null,
                'gender' => $counterpart->member?->gender,
                'city_id' => $counterpart->member?->city_id,
                'verification_status' => $counterpart->member?->verification_status,
                'ai_verification_status' => $counterpart->member?->ai_verification_status,
            ] : null,
            'responded_at' => optional($this->responded_at)->toISOString(),
            'withdrawn_at' => optional($this->withdrawn_at)->toISOString(),
            'expires_at' => optional($this->expires_at)->toISOString(),
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
