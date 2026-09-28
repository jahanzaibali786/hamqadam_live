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
            /*
             * The member's self-written bio (About me). Registration collects
             * it as `introduction`; the profile detail page renders it as the
             * "About me" card. Trimmed; empty stays null so the client can
             * hide the card entirely.
             */
            'about_me' => filled(trim((string) $this->member?->introduction))
                ? trim((string) $this->member->introduction)
                : null,
            'marital_status_id' => $this->member?->marital_status_id,
            'height' => $this->physical_attributes?->height,
            'religion_id' => $this->spiritual_backgrounds?->religion_id,
            'caste_id' => $this->spiritual_backgrounds?->caste_id,
            /*
             * Sect / school of thought for the card's "Muslim · Sunni" chips.
             * The card design shows both the faith and the denomination, and
             * they live on the same spiritual-background row as religion.
             */
            'sect_main_id' => $this->spiritual_backgrounds?->sect_main_id,
            'school_of_thought_id' => $this->spiritual_backgrounds?->school_of_thought_id,
            'city_id' => $this->addresses->first()?->city_id,
            'state_id' => $this->addresses->first()?->state_id,
            'country_id' => $this->addresses->first()?->country_id,
            /*
             * Education + career for the card's "Master's / Designer" facts.
             * Both are hasMany collections — the FIRST row is the member's
             * primary record (the app writes exactly one per member).
             */
            'education_level_id' => $this->education->first()?->education_level_id,
            'degree_id' => $this->education->first()?->degree_id,
            'profession_id' => $this->career->first()?->profession_id,
            /*
             * The card's "Family Oriented" line: the member's own family-values
             * pick. Falls back to the registration's work-intent answers so the
             * row never renders empty for a member who answered those.
             */
            'family_values' => $this->member?->family_values
                ?? ($this->member?->expects_spouse_to_work === 'yes' ? 'Family Oriented' : null),
            /*
             * Photo-count badge on the card image: how many EXTRA photos the
             * member's gallery holds (front photo excluded), so a member with
             * only the profile photo shows no badge.
             */
            'additional_photo_count' => is_array($this->member?->private_gallery)
                ? count($this->member->private_gallery)
                : (filled((string) $this->member?->private_gallery)
                    ? count((array) json_decode((string) $this->member->private_gallery, true) ?: [])
                    : 0),
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
            'badges' => app(\App\Services\BadgeService::class)->payload($this->resource),
            'verification' => [
                'identity_verified' => $this->member?->verification_status === 'verified'
                    || $this->member?->ai_verification_status === 'approved',
                'verified_at' => $this->member?->ai_verified_at
                    ? Carbon::parse($this->member->ai_verified_at)->toISOString()
                    : null,
            ],
            // A stored score wins only when it can explain itself — legacy
            // scoring runs left bare 0%/low numbers with no reasoning, and
            // letting them through shadowed the live AI model's real score.
            // `ai_match_percentage` is what the controller filled in from the
            // model for rows without a usable stored verdict.
            'compatibility_percentage' => $this->storedInformativePercentage()
                ?? $this->resource->getAttribute('ai_match_percentage'),
            'last_active_at' => $this->last_login_at ? Carbon::parse($this->last_login_at)->toISOString() : null,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toISOString() : null,
        ];
    }

    /**
     * The stored percentage only when the row is informative (has reasoning),
     * mirroring ProfileController::compatibility()'s "stored is a cache, not a
     * verdict" rule. Legacy bare-number rows return null so the live AI score
     * shows through.
     */
    private function storedInformativePercentage(): ?int
    {
        $stored = $this->profile_match_for_viewer;

        if ($stored === null || $stored->match_percentage === null) {
            return null;
        }

        if (filled($stored->compatibility_explanation) || filled($stored->score_breakdown)) {
            return (int) $stored->match_percentage;
        }

        return null;
    }
}
