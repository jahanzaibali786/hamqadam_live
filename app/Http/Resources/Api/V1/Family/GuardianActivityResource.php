<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Family;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianActivityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'guardian' => $this->guardian ? [
                'id' => $this->guardian->id,
                'name' => trim(($this->guardian->first_name ?? '') . ' ' . ($this->guardian->last_name ?? '')),
            ] : null,
            'resource_type' => $this->resource_type,
            'resource_id' => $this->resource_id,
            'metadata' => $this->metadata,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
