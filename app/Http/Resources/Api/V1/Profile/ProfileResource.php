<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $member = $this->member;

        return [
            'user' => [
                'id' => $this->id,
                'code' => $this->code,
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'name' => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')),
                'email' => $this->email,
                'phone' => $this->phone,
                'photo' => $this->photo ? uploaded_asset($this->photo) : null,
                'approved' => (bool) $this->approved,
                'blocked' => (bool) $this->blocked,
                'deactivated' => (bool) $this->deactivated,
            ],
            'member' => [
                'gender' => $member?->gender,
                'date_of_birth' => $member?->birthday ? (string) $member->birthday : null,
                'about_me' => $member?->introduction,
                'ai_generated_bio' => $member?->ai_generated_bio,
                'video_introduction' => $member?->video_introduction,
                'voice_introduction' => $member?->voice_introduction,
                'marital_status_id' => $member?->marital_status_id,
                'children' => $member?->children,
                'on_behalf_id' => $member?->on_behalves_id,
                'annual_salary_range_id' => $member?->annual_salary_range_id,
                'mother_tongue' => $member?->mothere_tongue,
                'known_languages' => json_decode((string) $member?->known_languages, true) ?: [],
                'travel_preferences' => $member?->travel_preferences,
                'future_goals' => $member?->future_goals,
                'hide_profile' => (bool) $member?->hide_profile,
                'verification_status' => $member?->verification_status ?? 'unverified',
                'profile_completion_percentage' => (int) ($member?->profile_completion_percentage ?? 0),
            ],
            'privacy' => new ProfilePrivacyResource($this->profile_privacy_setting),
        ];
    }
}

