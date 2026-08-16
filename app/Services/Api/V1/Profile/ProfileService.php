<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Profile;

use App\Models\Member;
use App\Models\ProfilePrivacySetting;
use App\Models\User;
use App\Services\Api\V1\Concerns\ExecutesInTransaction;
use Carbon\Carbon;

class ProfileService
{
    use ExecutesInTransaction;

    public function __construct(private readonly ProfileCompletionService $completionService)
    {
    }

    public function getProfile(User $user): User
    {
        $this->ensureProfileDefaults($user);
        $this->refreshCompletion($user);

        return $this->loadProfile($user->fresh());
    }

    public function updateProfile(User $user, array $data): User
    {
        return $this->transaction(function () use ($user, $data) {
            $member = $user->member ?: Member::create(['user_id' => $user->id]);

            $userData = [];
            foreach (['first_name', 'last_name', 'phone'] as $field) {
                if (array_key_exists($field, $data)) {
                    $userData[$field] = $data[$field];
                }
            }

            if ($userData !== []) {
                $user->fill($userData);
                $user->name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                $user->save();
            }

            $memberMap = [
                'gender' => 'gender',
                'date_of_birth' => 'birthday',
                'marital_status_id' => 'marital_status_id',
                'children' => 'children',
                'on_behalf' => 'on_behalves_id',
                'annual_salary_range_id' => 'annual_salary_range_id',
                'mother_tongue' => 'mothere_tongue',
                'about_me' => 'introduction',
                'ai_generated_bio' => 'ai_generated_bio',
                'video_introduction' => 'video_introduction',
                'voice_introduction' => 'voice_introduction',
                'travel_preferences' => 'travel_preferences',
                'future_goals' => 'future_goals',
                'hide_profile' => 'hide_profile',
            ];

            foreach ($memberMap as $input => $column) {
                if (array_key_exists($input, $data)) {
                    $member->{$column} = $input === 'date_of_birth' && $data[$input]
                        ? Carbon::parse($data[$input])->toDateString()
                        : $data[$input];
                }
            }

            if (array_key_exists('known_languages', $data)) {
                $member->known_languages = $data['known_languages'] ? json_encode($data['known_languages']) : null;
            }

            $member->profile_completion_percentage = $this->completionService->calculate($user->fresh());
            $member->save();

            return $this->getProfile($user);
        });
    }

    public function updatePrivacy(User $user, array $data): ProfilePrivacySetting
    {
        $privacy = $this->ensurePrivacy($user);
        $privacy->fill($data);
        $privacy->save();

        return $privacy;
    }

    public function updateVisibility(User $user, bool $hidden): User
    {
        $member = $user->member ?: Member::create(['user_id' => $user->id]);
        $member->hide_profile = $hidden;
        $member->save();

        return $this->getProfile($user);
    }

    public function deactivate(User $user): User
    {
        $user->deactivated = 1;
        $user->save();

        return $user;
    }

    private function refreshCompletion(User $user): void
    {
        if (! $user->member) {
            return;
        }

        $user->member->profile_completion_percentage = $this->completionService->calculate($user);
        $user->member->save();
    }

    private function ensureProfileDefaults(User $user): void
    {
        if (! $user->member) {
            Member::create(['user_id' => $user->id]);
        }

        $this->ensurePrivacy($user);
    }

    private function ensurePrivacy(User $user): ProfilePrivacySetting
    {
        return ProfilePrivacySetting::firstOrCreate(['user_id' => $user->id]);
    }

    private function loadProfile(User $user): User
    {
        return $user->load([
            'member',
            'profile_privacy_setting',
        ]);
    }
}

