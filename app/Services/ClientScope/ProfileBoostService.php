<?php

declare(strict_types=1);

namespace App\Services\ClientScope;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

/** Premium-only profile boost. One 24-hour boost per seven days per member. */
final class ProfileBoostService
{
    public function current(User $user): array
    {
        if (! Schema::hasTable('member_profile_boosts')) return ['active' => false, 'available' => false];
        $last = DB::table('member_profile_boosts')->where('user_id', $user->id)
            ->orderByDesc('starts_at')->first();
        $available = app(PremiumEntitlementService::class)->has($user, 'profile_boost');
        return [
            'active' => $last && \Carbon\Carbon::parse($last->ends_at)->isFuture(),
            'starts_at' => $last?->starts_at,
            'ends_at' => $last?->ends_at,
            'next_available_at' => $last ? \Carbon\Carbon::parse($last->starts_at)->addDays(7)->toISOString() : null,
            'available' => $available,
        ];
    }

    public function activate(User $user): array
    {
        if (! app(PremiumEntitlementService::class)->has($user, 'profile_boost')) {
            throw ValidationException::withMessages(['package' => 'An active Boost Profile plan is required.']);
        }
        return DB::transaction(function () use ($user): array {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $last = DB::table('member_profile_boosts')->where('user_id', $locked->id)
                ->orderByDesc('starts_at')->first();
            if ($last && \Carbon\Carbon::parse($last->starts_at)->addDays(7)->isFuture()) {
                throw ValidationException::withMessages(['boost' => 'You can activate another boost seven days after the previous one.']);
            }
            $starts = now();
            $ends = $starts->copy()->addHours(24);
            $validThrough = \Carbon\Carbon::parse($user->member->package_validity)->endOfDay();
            if ($validThrough->lessThan($ends)) $ends = $validThrough;
            DB::table('member_profile_boosts')->insert([
                'user_id' => $locked->id, 'starts_at'=>$starts, 'ends_at'=>$ends,
                'created_at'=>$starts, 'updated_at'=>$starts,
            ]);
            if (Schema::hasTable('analytics_events')) {
                \App\Models\AnalyticsEvent::query()->create([
                    'user_id'=>$locked->id, 'event_name'=>'profile_boost_activated',
                    'feature_key'=>'profile_boost','source'=>'premium_boost',
                    'occurred_at'=>$starts, 'metadata'=>['ends_at'=>$ends->toISOString()],
                ]);
            }
            return $this->current($locked);
        }, 3);
    }
}
