<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Chat;

use App\Http\Middleware\EnsureApiMemberActivity;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => trim(($this->first_name ?? '').' '.($this->last_name ?? '')),
            'photo' => $this->photo ? uploaded_asset($this->photo) : null,
            // Chat presence: the other side renders "Online" vs "Last seen …".
            // Null for members who have never been active since the column was
            // added — the app shows no presence line rather than guessing.
            'last_active_at' => optional($this->last_active_at)->toISOString(),
            'is_online' => $this->last_active_at !== null
                && $this->last_active_at->gt(now()->subSeconds(EnsureApiMemberActivity::ONLINE_WINDOW_SECONDS)),
        ];
    }
}
