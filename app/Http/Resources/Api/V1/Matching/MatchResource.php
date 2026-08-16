<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Matching;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $this->matchedUser;

        return [
            'id' => $this->id,
            'matched_user_id' => $this->match_id,
            'compatibility_percentage' => (int) $this->match_percentage,
            'compatibility_explanation' => $this->compatibility_explanation,
            'compatibility_reasons' => $this->compatibility_reasons ?: [],
            'score_breakdown' => $this->score_breakdown ?: [],
            'calculated_at' => optional($this->calculated_at)->toISOString(),
            'profile' => $user ? [
                'code' => $user->code,
                'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'photo' => $user->photo ? uploaded_asset($user->photo) : null,
                'age' => $user->member?->birthday ? Carbon::parse($user->member->birthday)->age : null,
                'gender' => $user->member?->gender,
                'height' => $user->physical_attributes?->height,
                'religion_id' => $user->spiritual_backgrounds?->religion_id,
                'verified' => (bool) $user->approved,
            ] : null,
        ];
    }
}

