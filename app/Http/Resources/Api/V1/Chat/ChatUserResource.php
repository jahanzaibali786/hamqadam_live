<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Chat;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ChatUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')),
            'photo' => $this->photo ? uploaded_asset($this->photo) : null,
        ];
    }
}
