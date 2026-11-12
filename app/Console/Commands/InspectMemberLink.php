<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\User;
use Illuminate\Console\Command;

class InspectMemberLink extends Command
{
    protected $signature = 'members:inspect-link {userId : The users.id to inspect}';

    protected $description = 'Read-only diagnosis of the member relation for an existing user ID';

    public function handle(): int
    {
        $userId = filter_var($this->argument('userId'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (! $userId) {
            $this->error('Enter a positive numeric users.id.');
            return self::FAILURE;
        }

        $user = User::withTrashed()->find($userId);
        if (! $user) {
            $this->error("No users row exists with id {$userId} in this database.");
            return self::FAILURE;
        }

        $this->line('User ID: '.$user->id);
        $this->line('User type: '.$user->user_type);
        $this->line('User soft-deleted: '.($user->trashed() ? 'yes' : 'no'));

        $rows = Member::withTrashed()->where('user_id', $user->id)->orderBy('id')->get();
        if ($rows->isEmpty()) {
            $this->warn("No member has user_id={$user->id}. Do not assume members.id={$user->id} is related.");
        } else {
            $this->table(['members.id', 'members.user_id', 'deleted_at', 'current_package_id'],
                $rows->map(fn ($member) => [
                    $member->id, $member->user_id,
                    $member->deleted_at?->toDateTimeString() ?? 'active',
                    $member->current_package_id ?? 'NULL',
                ])->toArray());
        }

        $sameId = Member::withTrashed()->find($user->id);
        if ($sameId && (int) $sameId->user_id !== (int) $user->id) {
            $this->warn("members.id={$user->id} belongs to a DIFFERENT user_id ({$sameId->user_id}).");
        }

        $active = $rows->filter(fn ($member) => ! $member->trashed())->count();
        if ($active === 1) {
            $this->info('User::member has exactly one active candidate.');
        } elseif ($active === 0) {
            $this->warn('User::member will be NULL (no active members.user_id match).');
        } else {
            $this->warn("{$active} active members rows exist for the same user_id. Reconcile duplicates manually.");
        }

        return self::SUCCESS;
    }
}
