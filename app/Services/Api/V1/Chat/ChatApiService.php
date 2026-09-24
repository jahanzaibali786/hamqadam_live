<?php
declare(strict_types=1);
namespace App\Services\Api\V1\Chat;
use App\Enums\ApiErrorCode;
use App\Enums\ChatMessageType;
use App\Events\ChatMessageDelivered;
use App\Events\ChatMessageRead;
use App\Events\ChatMessageSent;
use App\Events\ChatReactionUpdated;
use App\Events\ChatTypingIndicator as ChatTypingIndicatorEvent;
use App\Exceptions\ApiException;
use App\Models\Chat;
use App\Models\ChatReaction;
use App\Models\ChatThread;
use App\Models\ChatTypingIndicator;
use App\Models\ReportedUser;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\Api\V1\Chat\ChatMessageResource;
use App\Services\FcmV1Service;
class ChatApiService
{
    /**
     * The member's conversation list. Archive is per side, so the viewer's own
     * archive column decides where a thread shows up: `$archived = false` is
     * the normal inbox, `true` is the Archived tab.
     */
    public function threads(User $user, int $perPage = 20, bool $archived = false): LengthAwarePaginator
    {
        $threads = ChatThread::with(['sender', 'receiver'])
            ->where(function ($query) use ($user, $archived) {
                $query->where(function ($side) use ($user, $archived) {
                    $side->where('sender_user_id', $user->id)
                        ->where(function ($state) use ($archived) {
                            $archived
                                ? $state->whereNotNull('sender_archived_at')
                                : $state->whereNull('sender_archived_at');
                        });
                })->orWhere(function ($side) use ($user, $archived) {
                    $side->where('receiver_user_id', $user->id)
                        ->where(function ($state) use ($archived) {
                            $archived
                                ? $state->whereNotNull('receiver_archived_at')
                                : $state->whereNull('receiver_archived_at');
                        });
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('updated_at')
            ->paginate($perPage);

        $threads->getCollection()->transform(function (ChatThread $thread) use ($user) {
            $thread->setRelation('visibleLastMessage', $this->visibleLastMessage($thread, $user));
            return $thread;
        });

        return $threads;
    }
    public function messages(User $user, int $threadId, int $perPage = 20): LengthAwarePaginator
    {
        $thread = $this->threadForUser($user, $threadId);
        $this->markRead($user, $thread);
        $this->pruneExpired($thread);
        return Chat::with(['sender', 'replyTo.sender', 'reactions.user'])
            ->where('chat_thread_id', $thread->id)
            ->whereNull($this->deleteColumnFor($thread, $user))
            // Disappearing messages: anything past its deadline is hidden
            // (and hard-deleted by the prune above) for BOTH sides.
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Hard-deletes messages whose disappearing TTL has run out. Runs inline on
     * history reads so the feature works even without a scheduler; cheap
     * because the index on expires_at keeps the scan tiny.
     */
    public function pruneExpired(ChatThread $thread): int
    {
        return Chat::where('chat_thread_id', $thread->id)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->delete();
    }

    /**
     * Persists the thread's disappearing-message default (seconds; 0 = off).
     * Both clients read this back on the thread resource and apply it to new
     * messages, so the two sides stay in agreement without per-message picks.
     */
    public function setDisappearAfter(User $user, int $threadId, int $seconds): int
    {
        $thread = $this->threadForUser($user, $threadId);
        $value = max(0, min($seconds, 31536000));
        $thread->forceFill(['disappear_after' => $value])->save();
        return $value;
    }

    public function send(User $user, int $threadId, array $data, array $files = []): Chat
    {
        $thread = $this->threadForUser($user, $threadId);
        $this->ensureNotBlocked($thread);
        if (! empty($data['reply_to_chat_id'])) {
            $this->ensureReplyBelongsToThread((int) $data['reply_to_chat_id'], $thread);
        }
        $attachments = array_map(fn (UploadedFile $file) => upload_api_file($file), $files);
        return DB::transaction(function () use ($thread, $user, $data, $attachments) {
            // Disappearing TTL: seconds until the message vanishes. Resolution
            // order: explicit per-message value → the thread's remembered
            // setting → off. The chosen value is remembered on the thread so
            // the other side (and the next message) stays consistent.
            $ttl = (int) ($data['disappear_after'] ?? 0);
            if ($ttl <= 0 && array_key_exists('disappear_after', $data)) {
                $ttl = 0; // explicit "off" wins over the thread default
            } elseif ($ttl <= 0) {
                $ttl = (int) ($thread->disappear_after ?? 0);
            }
            if ($ttl > 0) {
                $thread->forceFill(['disappear_after' => $ttl])->save();
            }

            $message = Chat::create([
                'chat_thread_id' => $thread->id,
                'sender_user_id' => $user->id,
                'message' => $this->maskSensitiveText((string) ($data['message'] ?? '')),
                'message_type' => $data['message_type'] ?? $this->detectType($attachments),
                'reply_to_chat_id' => $data['reply_to_chat_id'] ?? null,
                'attachment' => $attachments !== [] ? implode(',', $attachments) : null,
                'seen' => 0,
                // Delivered stays NULL until the recipient's app ACKs via
                // POST /chat/threads/{thread}/delivered — that is what gives
                // the sender's tick its meaning: single = server has it,
                // double = on the recipient's device, blue = they read it.
                'delivered_at' => null,
                'moderation_status' => 'clean',
                'toxicity_score' => 0,
                // Multipart delivers metadata values as strings ("7", "3");
                // normalize to ints so client casts never crash.
                'metadata' => $this->normalizeMetadata($data['metadata'] ?? null),
                'expires_at' => $ttl > 0 ? now()->addSeconds(min($ttl, 31536000)) : null,
            ]);
            $thread->forceFill(['last_message_at' => now()])->save();
            $message = $message->load(['sender', 'replyTo.sender']);
            $recipientId = (int) $thread->sender_user_id === (int) $user->id
                ? (int) $thread->receiver_user_id
                : (int) $thread->sender_user_id;
            $this->broadcastSafely(new ChatMessageSent($message, $user, (int) $thread->id, $recipientId));

            // Muting a conversation only silences it — the message still lands
            // in the thread and the chat bubble still updates — so both the
            // push and the notification-tray row are skipped while the
            // recipient's own mute stamp is set.
            if (! $this->isMutedFor($thread, $recipientId)) {
                // Send FCM push so the recipient gets notified even if app is backgrounded/killed
                $this->sendChatFcmPush($thread, $recipientId, $user, $message);
                // Create notification record in DB + tray push
                $recipient = User::find($recipientId);
                if ($recipient) {
                    \App\Services\NotificationHelper::chatMessage(
                        $recipient,
                        $user,
                        (int) $thread->id,
                        (string) ($data['message'] ?? ''),
                    );
                }
            }
            return $message;
        });
    }
    public function typing(User $user, int $threadId): void
    {
        $thread = $this->threadForUser($user, $threadId);
        ChatTypingIndicator::updateOrCreate([
            'chat_thread_id' => $thread->id,
            'user_id' => $user->id,
        ], [
            'expires_at' => now()->addSeconds(10),
        ]);
        $recipientId = (int) $thread->sender_user_id === (int) $user->id
            ? (int) $thread->receiver_user_id
            : (int) $thread->sender_user_id;
        $this->broadcastSafely(new ChatTypingIndicatorEvent(
            (int) $thread->id,
            $user,
            true,
            now()->addSeconds(10)->toISOString()
        ));
    }
    /**
     * Records that the recipient's app has the thread's messages on device —
     * the sender's single tick becomes a double tick. Reading (blue tick) is a
     * separate, stricter step handled by [markRead].
     */
    public function markDelivered(User $user, int $threadId): void
    {
        $thread = $this->threadForUser($user, $threadId);

        $messageIds = Chat::where('chat_thread_id', $thread->id)
            ->where('sender_user_id', '!=', $user->id)
            ->whereNull('delivered_at')
            ->pluck('id')
            ->all();
        if ($messageIds === []) {
            return;
        }

        Chat::whereIn('id', $messageIds)->update(['delivered_at' => now()]);
        $this->broadcastSafely(new ChatMessageDelivered((int) $thread->id, $messageIds, (int) $user->id, now()->toISOString()));
    }

    public function markRead(User $user, ChatThread $thread): void
    {
        $messageIds = Chat::where('chat_thread_id', $thread->id)
            ->where('sender_user_id', '!=', $user->id)
            ->where('seen', 0)
            ->pluck('id')
            ->all();
        if ($messageIds === []) {
            return;
        }
        Chat::whereIn('id', $messageIds)->update([
            'seen' => 1,
            'read_at' => now(),
        ]);
        $this->broadcastSafely(new ChatMessageRead((int) $thread->id, $messageIds, (int) $user->id, now()->toISOString()));
    }
    public function deleteMessageForMe(User $user, int $messageId): void
    {
        $message = Chat::with('chatThread')->find($messageId);
        if (! $message || ! $message->chatThread || ! $this->isParticipant($message->chatThread, $user)) {
            throw new ApiException('Message not found.', 404, ApiErrorCode::NotFound->value);
        }
        $message->forceFill([
            $this->deleteColumnFor($message->chatThread, $user) => now(),
        ])->save();
    }

    public function clear(User $user, int $threadId): ChatThread
    {
        $thread = $this->threadForUser($user, $threadId);
        Chat::where('chat_thread_id', $thread->id)
            ->whereNull($this->deleteColumnFor($thread, $user))
            ->update([
                $this->deleteColumnFor($thread, $user) => now(),
            ]);

        return $thread->fresh(['sender', 'receiver']);
    }
    /**
     * Moves the thread in or out of THIS member's archived tab. The other side
     * is untouched — their column stays where it was.
     */
    public function archive(User $user, int $threadId, bool $archived): ChatThread
    {
        $thread = $this->threadForUser($user, $threadId);
        $thread->forceFill([
            $thread->archivedColumnForUserId((int) $user->id) => $archived ? now() : null,
        ])->save();

        return $thread->fresh(['sender', 'receiver']);
    }

    /**
     * Mutes (or unmutes) the thread for THIS member: the conversation keeps
     * receiving messages, but no push/notification tray entry is produced
     * while their mute stamp is set.
     */
    public function mute(User $user, int $threadId, bool $muted): ChatThread
    {
        $thread = $this->threadForUser($user, $threadId);
        $thread->forceFill([
            $thread->mutedColumnForUserId((int) $user->id) => $muted ? now() : null,
        ])->save();

        return $thread->fresh(['sender', 'receiver']);
    }

    /**
     * Adds, swaps or clears the caller's emoji reaction on one message.
     * Tapping the same emoji twice removes it (WhatsApp-style toggle), which is
     * why a `null`/same-emoji request deletes the row instead of writing it.
     */
    public function react(User $user, int $messageId, ?string $emoji): ?Chat
    {
        $message = Chat::with('chatThread')->find($messageId);
        if (! $message || ! $message->chatThread || ! $this->isParticipant($message->chatThread, $user)) {
            throw new ApiException('Message not found.', 404, ApiErrorCode::NotFound->value);
        }

        $emoji = $emoji !== null ? trim($emoji) : null;
        if ($emoji !== null && mb_strlen($emoji) > 8) {
            throw new ApiException('That reaction is not supported.', 422, ApiErrorCode::ValidationFailed->value);
        }

        $existing = ChatReaction::where('chat_id', $message->id)
            ->where('user_id', $user->id)
            ->first();

        // Same emoji again (or an explicit null) = clear the reaction.
        $cleared = $emoji === null || ($existing && $existing->emoji === $emoji);

        if ($cleared) {
            if ($existing) {
                $existing->delete();
            }
        } else {
            ChatReaction::updateOrCreate(
                ['chat_id' => $message->id, 'user_id' => $user->id],
                ['emoji' => $emoji]
            );
        }

        $this->broadcastSafely(new ChatReactionUpdated(
            (int) $message->chat_thread_id,
            (int) $message->id,
            (int) $user->id,
            $cleared ? null : $emoji
        ));

        return $message->fresh(['sender', 'replyTo.sender', 'reactions.user']);
    }

    /**
     * Full JSON backup of one conversation, for the app's "Export chat".
     * Returns the peer + every message this member can still see, oldest first,
     * with attachments and reactions already flattened by the resources.
     */
    public function export(User $user, int $threadId): array
    {
        $thread = $this->threadForUser($user, $threadId);
        $this->pruneExpired($thread);

        $messages = Chat::with(['sender', 'reactions.user'])
            ->where('chat_thread_id', $thread->id)
            ->whereNull($this->deleteColumnFor($thread, $user))
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->oldest()
            ->get();

        $otherUser = (int) $thread->sender_user_id === (int) $user->id ? $thread->receiver : $thread->sender;

        return [
            'thread_id' => (int) $thread->id,
            'thread_code' => $thread->thread_code,
            'exported_at' => now()->toISOString(),
            'with' => $otherUser ? [
                'id' => (int) $otherUser->id,
                'name' => trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? '')),
            ] : null,
            'message_count' => $messages->count(),
            'messages' => $messages
                ->map(fn (Chat $message) => (new ChatMessageResource($message))->resolve())
                ->values()
                ->all(),
        ];
    }

    public function block(User $user, int $threadId): ChatThread
    {
        $thread = $this->threadForUser($user, $threadId);
        $thread->forceFill(['blocked_by_user' => $user->id])->save();
        app(\App\Services\BadgeService::class)->refresh($user->fresh(['member']));
        $otherId = (int) ($thread->sender_user_id === $user->id ? $thread->receiver_user_id : $thread->sender_user_id);
        app(\App\Services\BadgeService::class)->refresh(User::with('member')->find($otherId));
        return $thread->fresh(['sender', 'receiver']);
    }
    public function unblock(User $user, int $threadId): ChatThread
    {
        $thread = $this->threadForUser($user, $threadId);
        if ((int) $thread->blocked_by_user !== (int) $user->id) {
            throw new ApiException('Only the user who blocked this thread can unblock it.', 403, ApiErrorCode::Forbidden->value);
        }
        $thread->forceFill(['blocked_by_user' => null])->save();
        return $thread->fresh(['sender', 'receiver']);
    }
    public function report(User $user, int $threadId, string $reason): ChatThread
    {
        $thread = $this->threadForUser($user, $threadId);
        $reportedUserId = (int) $thread->sender_user_id === (int) $user->id ? $thread->receiver_user_id : $thread->sender_user_id;
        ReportedUser::updateOrCreate([
            'user_id' => $reportedUserId,
            'reported_by' => $user->id,
            'source' => 'chat',
            'chat_thread_id' => $thread->id,
        ], [
            'reason' => $reason,
        ]);

        $thread->forceFill([
            'active' => 0,
            'blocked_by_user' => $user->id,
        ])->save();

        app(\App\Services\BadgeService::class)->refresh($user->fresh(['member']));
        app(\App\Services\BadgeService::class)->refresh(User::with('member')->find($reportedUserId));

        return $thread->fresh(['sender', 'receiver']);
    }
    private function threadForUser(User $user, int $threadId): ChatThread
    {
        $thread = ChatThread::with(['sender', 'receiver'])->find($threadId);
        if (! $thread || ! $this->isParticipant($thread, $user)) {
            throw new ApiException('Chat thread not found.', 404, ApiErrorCode::NotFound->value);
        }
        return $thread;
    }
    private function isParticipant(ChatThread $thread, User $user): bool
    {
        return in_array((int) $user->id, [(int) $thread->sender_user_id, (int) $thread->receiver_user_id], true);
    }
    /**
     * True when $userId has muted this thread — the conversation keeps working
     * for them, it just stops producing push/tray notifications.
     */
    private function isMutedFor(ChatThread $thread, int $userId): bool
    {
        return $thread->{$thread->mutedColumnForUserId($userId)} !== null;
    }

    private function ensureNotBlocked(ChatThread $thread): void
    {
        if ($thread->blocked_by_user) {
            throw new ApiException('This chat thread is blocked.', 403, ApiErrorCode::Forbidden->value);
        }
    }

    private function visibleLastMessage(ChatThread $thread, User $user): ?Chat
    {
        return Chat::with(['sender', 'replyTo.sender', 'reactions.user'])
            ->where('chat_thread_id', $thread->id)
            ->whereNull($this->deleteColumnFor($thread, $user))
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }
    private function ensureReplyBelongsToThread(int $messageId, ChatThread $thread): void
    {
        if (! Chat::whereKey($messageId)->where('chat_thread_id', $thread->id)->exists()) {
            throw new ApiException('Reply message does not belong to this thread.', 422, ApiErrorCode::ValidationFailed->value);
        }
    }
    private function deleteColumnFor(ChatThread $thread, User $user): string
    {
        return (int) $thread->sender_user_id === (int) $user->id ? 'deleted_by_sender_at' : 'deleted_by_receiver_at';
    }
    private function detectType(array $attachments): string
    {
        return $attachments === [] ? ChatMessageType::Text->value : ChatMessageType::Mixed->value;
    }

    /**
     * Voice-note extras ride in as form-data strings; cast the known numeric
     * fields so the resource (and both clients) always see real integers.
     */
    private function normalizeMetadata(mixed $metadata): ?array
    {
        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : null;
        }
        if (! is_array($metadata)) {
            return null;
        }
        if (array_key_exists('duration', $metadata) && $metadata['duration'] !== null) {
            $metadata['duration'] = (int) $metadata['duration'];
        }
        if (isset($metadata['waveform']) && is_array($metadata['waveform'])) {
            $metadata['waveform'] = array_map(static fn ($value) => (int) $value, $metadata['waveform']);
        }

        return $metadata;
    }
    private function broadcastSafely(object $event): void
    {
        try {
            broadcast($event)->toOthers();
        } catch (\Throwable $throwable) {
            Log::warning('API realtime chat broadcast failed.', [
                'event' => $event::class,
                'message' => $throwable->getMessage(),
            ]);
        }
    }

    /**
     * Send an FCM v1 push so the recipient is notified even when the app
     * is backgrounded or killed and Pusher cannot reach it.
     */
    private function sendChatFcmPush(ChatThread $thread, int $recipientId, User $sender, Chat $message): void
    {
        try {
            $recipient = User::find($recipientId);
            if (! $recipient) {
                return;
            }
            // No check on $recipient->fcm_token: that single column is shared
            // with the website, so an empty or stale value there says nothing
            // about whether the member has a reachable device.
            $senderName = trim(($sender->first_name ?? '') . ' ' . ($sender->last_name ?? ''));
            $body = $message->message_type === 'text'
                ? $message->message
                : ($message->message ?: '📷 Photo');
            FcmV1Service::sendToUser(
                (int) $recipient->id,
                [
                    'title' => $senderName,
                    'body' => mb_substr($body, 0, 200),
                ],
                [
                    'type' => 'chat_message',
                    'thread_id' => (string) $thread->id,
                    'sender_id' => (string) $sender->id,
                    // The same person under the name the rest of the payloads
                    // use, so one routing path covers every notification type.
                    'notify_by' => (string) $sender->id,
                    'info_id' => (string) $thread->id,
                    'message_id' => (string) $message->id,
                ]
            );
        } catch (\Throwable $e) {
            Log::warning('FCM chat push failed.', ['error' => $e->getMessage()]);
        }
    }
    private function maskSensitiveText(string $message): string
    {
        $message = preg_replace('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', '[email hidden]', $message) ?? $message;
        return preg_replace('/(?<!\d)(?:\+?\d[\d\s().-]{7,}\d)(?!\d)/', '[phone hidden]', $message) ?? $message;
    }
}