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
            /*
             * Trust badge for somebody ELSE's profile.
             *
             * Deliberately a boolean and a timestamp only. The full AI block
             * (recommendation, attempt count, fraud score, last error) lives on
             * GET /profile for the owner and must not leak here - a viewer has
             * no business knowing that another member failed verification three
             * times or why.
             *
             * Verified means either path succeeded: a moderator approved the
             * documents (members.verification_status = 'verified', set by
             * VerificationService::approve) or the model returned APPROVE
             * (members.ai_verification_status = 'approved').
             *
             * `verified_at` is only populated for the AI path - members.ai_verified_at.
             * The moderator path stores its timestamp on the verification
             * request (reviewed_at), and reaching for it here would add a
             * relation load to every row of every search page. So a
             * moderator-verified member reads identity_verified=true with a
             * null date, which is the honest answer for what this row can
             * cheaply know.
             */
            'verification' => [
                'identity_verified' => $this->member?->verification_status === 'verified'
                    || $this->member?->ai_verification_status === 'approved',
                'verified_at' => $this->member?->ai_verified_at
                    ? Carbon::parse($this->member->ai_verified_at)->toISOString()
                    : null,
            ],
            'compatibility_percentage' => $this->profile_match_for_viewer?->match_percentage,
            'last_active_at' => $this->last_login_at ? Carbon::parse($this->last_login_at)->toISOString() : null,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toISOString() : null,
        ];
    }
}
