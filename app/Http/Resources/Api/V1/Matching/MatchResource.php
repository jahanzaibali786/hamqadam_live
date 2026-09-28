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
                // Sect chips + education/career/family facts — the same card
                // recipe as GET /search/profiles so both listings agree.
                'sect_main_id' => $user->spiritual_backgrounds?->sect_main_id,
                'school_of_thought_id' => $user->spiritual_backgrounds?->school_of_thought_id,
                'education_level_id' => $user->education->first()?->education_level_id,
                'degree_id' => $user->education->first()?->degree_id,
                'profession_id' => $user->career->first()?->profession_id,
                'family_values' => $user->member?->family_values,
                'additional_photo_count' => is_array($user->member?->private_gallery)
                    ? count($user->member->private_gallery)
                    : (filled((string) $user->member?->private_gallery)
                        ? count((array) json_decode((string) $user->member->private_gallery, true) ?: [])
                        : 0),
                'verified' => (bool) $user->approved,
                'badges' => app(\App\Services\BadgeService::class)->payload($user),
            ] : null,
        ];
    }
}

