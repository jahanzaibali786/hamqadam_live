<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Ai;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AiResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'feature' => $this->feature,
            'provider' => $this->provider,
            'status' => $this->status,
            'output' => $this->output,
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
