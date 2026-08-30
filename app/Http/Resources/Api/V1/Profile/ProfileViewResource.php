<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Profile;

use App\Http\Resources\Api\V1\Search\SearchProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileViewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->relationLoaded('user') ? $this->user : $this->profileViewer;

        return [
            'id' => $this->id,
            'viewed_at' => optional($this->created_at)->toISOString(),
            'view_type' => $this->relationLoaded('user') ? 'sent' : 'received',
            'profile' => $profile ? new SearchProfileResource($profile) : null,
        ];
    }
}
