<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeviceSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_name' => $this->device_name,
            'device_type' => $this->device_type,
            'device_id' => $this->device_id,
            'ip_address' => $this->ip_address,
            'last_used_at' => optional($this->last_used_at)->toISOString(),
            'expires_at' => optional($this->expires_at)->toISOString(),
            'revoked_at' => optional($this->revoked_at)->toISOString(),
            'is_current' => $request->user()?->currentAccessToken()?->id === $this->personal_access_token_id,
        ];
    }
}

