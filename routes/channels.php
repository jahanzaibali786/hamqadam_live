<?php

declare(strict_types=1);

use App\Models\ChatThread;
use App\Models\HelpChatThread;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat-thread.{threadId}', function ($user, int $threadId) {
    return ChatThread::query()
        ->whereKey($threadId)
        ->where(function ($query) use ($user) {
            $query->where('sender_user_id', $user->id)
                ->orWhere('receiver_user_id', $user->id);
        })
        ->exists();
});

/*
| Help Center. The member is admitted to their own conversation; admins and
| staff to every one. Subscribing to a help channel for a thread that does
| not exist answers false, so a stale subscription cannot leak anything.
*/
Broadcast::channel('help-chat.{threadId}', function ($user, int $threadId) {
    if (in_array($user->user_type, ['admin', 'staff', 'subadmin'], true)) {
        return true;
    }

    return HelpChatThread::query()
        ->whereKey($threadId)
        ->where('user_id', $user->id)
        ->exists();
});
