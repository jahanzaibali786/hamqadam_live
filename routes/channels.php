<?php

declare(strict_types=1);

use App\Models\ChatThread;
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
