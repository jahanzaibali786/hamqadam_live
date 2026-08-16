<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Profile;

use App\Models\User;

class ProfileCompletionService
{
    public function calculate(User $user): int
    {
        $user->loadMissing([
            'member',
            'addresses',
            'education',
            'career',
            'physical_attributes',
            'lifestyles',
            'families',
            'spiritual_backgrounds',
            'partner_expectations',
        ]);

        $checks = [
            filled($user->first_name),
            filled($user->email) || filled($user->phone),
            filled($user->photo),
            filled($user->member?->gender),
            filled($user->member?->birthday),
            filled($user->member?->introduction),
            filled($user->member?->marital_status_id),
            filled($user->member?->mothere_tongue),
            $user->addresses->isNotEmpty(),
            $user->education->isNotEmpty(),
            $user->career->isNotEmpty(),
            $user->physical_attributes !== null,
            $user->lifestyles !== null,
            $user->families !== null,
            $user->spiritual_backgrounds !== null,
            $user->partner_expectations !== null,
        ];

        $completed = count(array_filter($checks));

        return (int) round(($completed / count($checks)) * 100);
    }
}

