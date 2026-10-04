<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Notification;

use App\Enums\ApiErrorCode;
use App\Exceptions\ApiException;
use App\Models\Notification;
use App\Models\NotificationDeliveryLog;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\UserPushToken;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function list(User $user, array $filters): LengthAwarePaginator
    {
        $query = Notification::query()
            ->with('deliveries')
            ->where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->latest();

        if (! empty($filters['unread_only'])) {
            $query->whereNull('read_at');
        }

        if (array_key_exists('read', $filters)) {
            $filters['read'] ? $query->whereNotNull('read_at') : $query->whereNull('read_at');
        }

        if (! empty($filters['event_key'])) {
            $query->where('type', $filters['event_key']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function unreadCount(User $user): int
    {
        return Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->count();
    }

    public function markRead(User $user, string $id): Notification
    {
        $notification = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->whereKey($id)
            ->first();

        if (! $notification) {
            throw new ApiException('Notification not found.', 404, ApiErrorCode::NotFound->value);
        }

        if (! $notification->read_at) {
            $notification->forceFill(['read_at' => now()])->save();
            NotificationDeliveryLog::where('notification_id', (string) $notification->id)
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now(), 'status' => 'read']);
        }

        return $notification;
    }

    public function markClicked(User $user, string $id): Notification
    {
        $notification = $this->markRead($user, $id);
        NotificationDeliveryLog::where('notification_id', (string) $notification->id)
            ->where('user_id', $user->id)->update(['clicked_at' => now()]);
        return $notification;
    }

    public function markAllRead(User $user): int
    {
        $ids = Notification::where('notifiable_id', $user->id)
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')->pluck('id');
        $updated = Notification::whereIn('id', $ids)->update(['read_at' => now()]);
        NotificationDeliveryLog::where('user_id', $user->id)->whereIn('notification_id', $ids->map(fn ($id) => (string) $id))
            ->whereNull('read_at')->update(['read_at' => now(), 'status' => 'read']);
        return $updated;
    }

    public function preferences(User $user): NotificationPreference
    {
        return NotificationPreference::firstOrCreate(['user_id' => $user->id]);
    }

    public function updatePreferences(User $user, array $data): NotificationPreference
    {
        $preferences = $this->preferences($user);
        $preferences->fill($data)->save();

        return $preferences;
    }

    public function storePushToken(User $user, array $data): UserPushToken
    {
        $token = UserPushToken::updateOrCreate([
            'user_id' => $user->id,
            'token' => $data['token'],
        ], [
            'platform' => $data['platform'] ?? null,
            'device_id' => $data['device_id'] ?? null,
            'last_used_at' => now(),
        ]);

        $user->forceFill(['fcm_token' => $data['token']])->save();

        return $token;
    }

    public function deletePushToken(User $user, int $id): void
    {
        $token = UserPushToken::where('user_id', $user->id)->whereKey($id)->first();
        if (! $token) {
            return;
        }

        // Deleting the row is not enough on its own: every push sender also
        // reads users.fcm_token, so a member who logged out kept receiving
        // their own messages and calls - previews included - on a phone that
        // was no longer theirs.
        if ($user->fcm_token === $token->token) {
            $user->forceFill(['fcm_token' => null])->save();
        }

        $token->delete();
    }
}
