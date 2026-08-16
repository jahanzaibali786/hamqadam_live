<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Search;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')),
            'photo' => $this->photo ? uploaded_asset($this->photo) : null,
            'membership' => $this->membership,
            'approved' => (bool) $this->approved,
            'age' => $this->member?->birthday ? Carbon::parse($this->member->birthday)->age : null,
            'gender' => $this->member?->gender,
            'marital_status_id' => $this->member?->marital_status_id,
            'height' => $this->physical_attributes?->height,
            'religion_id' => $this->spiritual_backgrounds?->religion_id,
            'caste_id' => $this->spiritual_backgrounds?->caste_id,
            'city_id' => $this->addresses->first()?->city_id,
            'state_id' => $this->addresses->first()?->state_id,
            'country_id' => $this->addresses->first()?->country_id,
            'compatibility_percentage' => $this->profile_match_for_viewer?->match_percentage,
            'last_active_at' => $this->last_login_at ? Carbon::parse($this->last_login_at)->toISOString() : null,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toISOString() : null,
        ];
    }
}
