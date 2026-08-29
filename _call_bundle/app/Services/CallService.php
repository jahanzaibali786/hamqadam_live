<?php















declare(strict_types=1);















namespace App\Services;















use App\Enums\CallStatus;







use App\Enums\CallType;







use App\Events\CallAccepted;







use App\Events\CallBusy;







use App\Events\CallCancelled;







use App\Events\CallEnded;







use App\Events\CallIncoming;







use App\Events\CallMissed;







use App\Events\CallRejected;







use App\Exceptions\ApiException;







use App\Models\Call;







use App\Models\ChatThread;







use App\Models\Setting;







use App\Models\User;







use Illuminate\Support\Carbon;







use Illuminate\Support\Facades\DB;







use Illuminate\Support\Facades\Log;







use TaylanUnutmaz\AgoraTokenBuilder\RtcTokenBuilder;







use Throwable;















class CallService







{







    public function start(User $caller, int $threadId, string $callType): array







    {







        $thread = $this->threadForUser($caller, $threadId);







        $type = $this->normalizeType($callType);







        $receiver = $this->otherParticipant($thread, $caller);















        $activeCall = $this->activeCallForThread($thread);







        if ($activeCall) {







            $activeCall->loadMissing(['caller', 'receiver', 'conversation']);







            if ((int) $activeCall->caller_id === (int) $caller->id && in_array($activeCall->status, [CallStatus::Calling, CallStatus::Ringing], true)) {







                if ($activeCall->status !== CallStatus::Ringing) {







                    $activeCall->forceFill(['status' => CallStatus::Ringing])->save();







                    $activeCall->refresh();







                }















                $rebroadcastPayload = $this->payload($activeCall, $caller, translate('Ali is calling you...'));







                $rebroadcastPayload['rtc'] = null;







                $this->broadcastSafely(new CallIncoming($rebroadcastPayload));







            }















            $rtc = $this->rtcPayload($activeCall, $caller);







            $payload = $this->payload($activeCall, $caller, translate('Call already active.'), $rtc);















            Log::info('Existing active call reused.', [







                'call_id' => $activeCall->id,







                'caller_id' => $caller->id,







                'receiver_id' => $receiver->id,







                'conversation_id' => $thread->id,







                'status' => $activeCall->status,







            ]);















            return [







                'call' => $payload['call'],







                'rtc' => $rtc,







                'reused' => true,







            ];







        }















        $this->ensureCanCall($caller, $receiver, $thread);















        if ($this->hasActiveCall($caller) || $this->hasActiveCall($receiver)) {







            $busyCall = $this->createCall($caller, $receiver, $thread, $type, CallStatus::Busy);







            $payload = $this->payload($busyCall->fresh(['caller', 'receiver', 'conversation']), $caller, 'User Busy');







            $this->broadcastSafely(new CallBusy($payload));







            Log::warning('Call start blocked - user busy.', [







                'call_id' => $busyCall->id,







                'caller_id' => $caller->id,







                'receiver_id' => $receiver->id,







                'conversation_id' => $thread->id,







            ]);















            throw new ApiException(







                translate('User is busy.'),







                409,







                'user_busy'







            );







        }















        $call = $this->createCall($caller, $receiver, $thread, $type, CallStatus::Calling);







        $rtc = $this->rtcPayload($call, $caller);







        $payload = $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $caller, translate('Ali is calling you...'));







        $payload['rtc'] = null;







        $this->broadcastSafely(new CallIncoming($payload));















        Log::info('Call initiated.', [







            'call_id' => $call->id,







            'caller_id' => $caller->id,







            'receiver_id' => $receiver->id,







            'conversation_id' => $thread->id,







            'call_type' => $type->value,







        ]);















        return [







            'call' => $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $caller),







            'rtc' => $rtc,







        ];







    }















    public function accept(User $user, int $callId): array







    {







        $call = $this->callForUser($user, $callId);







        $this->ensureIsReceiver($call, $user);







        if ($this->markMissedIfExpired($call)) {







            $call->refresh();







        }















        if ($call->status === CallStatus::Missed || $call->status === CallStatus::Cancelled || $call->status === CallStatus::Rejected || $call->status === CallStatus::Ended || $call->status === CallStatus::Busy) {







            throw new ApiException(translate('This call is no longer active.'), 422, 'call_inactive');







        }















        $call->forceFill([







            'status' => CallStatus::Accepted,







            'answered_at' => $call->answered_at ?: now(),







            'started_at' => $call->started_at ?: now(),







        ])->save();















        $rtc = $this->rtcPayload($call, $user);







        $payload = $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $user, translate('Call accepted.'));







        $payload['rtc'] = $rtc;







        $this->broadcastSafely(new CallAccepted($payload));















        Log::info('Call accepted.', [







            'call_id' => $call->id,







            'caller_id' => $call->caller_id,







            'receiver_id' => $call->receiver_id,







            'conversation_id' => $call->conversation_id,







        ]);















        return [







            'call' => $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $user, null, $rtc),







            'rtc' => $rtc,







        ];







    }















    public function reject(User $user, int $callId): array







    {







        $call = $this->callForUser($user, $callId);







        $this->ensureIsReceiver($call, $user);















        $call->forceFill([







            'status' => CallStatus::Rejected,







            'ended_at' => now(),







            'ended_by_user_id' => $user->id,







            'duration_seconds' => 0,







        ])->save();















        $payload = $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $user, translate('Call declined.'));







        $this->broadcastSafely(new CallRejected($payload));















        Log::info('Call rejected.', [







            'call_id' => $call->id,







            'caller_id' => $call->caller_id,







            'receiver_id' => $call->receiver_id,







            'conversation_id' => $call->conversation_id,







            'rejected_by' => $user->id,







        ]);















        return [







            'call' => $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $user, null),







        ];







    }















    public function cancel(User $user, int $callId): array







    {







        $call = $this->callForUser($user, $callId);







        $this->ensureIsCaller($call, $user);















        $call->forceFill([







            'status' => CallStatus::Cancelled,







            'ended_at' => now(),







            'ended_by_user_id' => $user->id,







            'duration_seconds' => 0,







        ])->save();















        $payload = $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $user, translate('Call cancelled.'));







        $this->broadcastSafely(new CallCancelled($payload));















        Log::info('Call cancelled.', [







            'call_id' => $call->id,







            'caller_id' => $call->caller_id,







            'receiver_id' => $call->receiver_id,







            'conversation_id' => $call->conversation_id,







            'cancelled_by' => $user->id,







        ]);















        return [







            'call' => $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $user, null),







        ];







    }















    public function connect(User $user, int $callId): array







    {







        $call = $this->callForUser($user, $callId);















        if (in_array($call->status, [CallStatus::Cancelled, CallStatus::Rejected, CallStatus::Busy, CallStatus::Missed, CallStatus::Ended], true)) {







            throw new ApiException(translate('This call is no longer active.'), 422, 'call_inactive');







        }















        $call->forceFill([







            'status' => CallStatus::Connected,







            'answered_at' => $call->answered_at ?: now(),







            'started_at' => $call->started_at ?: now(),







        ])->save();















        Log::info('Call connected.', [







            'call_id' => $call->id,







            'caller_id' => $call->caller_id,







            'receiver_id' => $call->receiver_id,







            'conversation_id' => $call->conversation_id,







            'connected_by' => $user->id,







        ]);















        return [







            'call' => $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $user),







        ];







    }















    public function end(User $user, int $callId, ?string $status = null): array







    {







        $call = $this->callForUser($user, $callId);







        $status = $status ?: CallStatus::Ended->value;















        if (! in_array($status, [CallStatus::Ended->value, CallStatus::Missed->value], true)) {







            $status = CallStatus::Ended->value;







        }















        $call->forceFill([







            'status' => CallStatus::from($status),







            'ended_at' => now(),







            'ended_by_user_id' => $user->id,







            'duration_seconds' => $this->durationSeconds($call),







        ])->save();















        $payload = $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $user, $status === CallStatus::Missed->value ? translate('Missed call.') : translate('Call ended.'));







        $event = $status === CallStatus::Missed->value ? new CallMissed($payload) : new CallEnded($payload);







        $this->broadcastSafely($event);















        Log::info($status === CallStatus::Missed->value ? 'Call missed.' : 'Call ended.', [







            'call_id' => $call->id,







            'caller_id' => $call->caller_id,







            'receiver_id' => $call->receiver_id,







            'conversation_id' => $call->conversation_id,







            'ended_by' => $user->id,







            'status' => $status,







        ]);















        return [







            'call' => $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $user),







        ];







    }















    public function history(User $user, int $threadId, int $perPage = 20)







    {







        $thread = $this->threadForUser($user, $threadId);















        return Call::with(['caller', 'receiver', 'conversation'])







            ->where('conversation_id', $thread->id)







            ->orderByDesc('created_at')







            ->paginate($perPage);







    }















    public function get(User $user, int $callId): array







    {







        $call = $this->callForUser($user, $callId);















        return [







            'call' => $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $user),







        ];







    }















    public function markMissedIfExpired(Call $call): bool







    {







        if ($call->status !== CallStatus::Calling && $call->status !== CallStatus::Ringing && $call->status !== CallStatus::Accepted) {







            return false;







        }















        if (! $call->ring_expires_at || $call->ring_expires_at->isFuture()) {







            return false;







        }















        $call->forceFill([







            'status' => CallStatus::Missed,







            'ended_at' => now(),







            'ended_by_user_id' => $call->caller_id,







            'duration_seconds' => 0,







        ])->save();















        $payload = $this->payload($call->fresh(['caller', 'receiver', 'conversation']), $call->caller, translate('Missed call.'));







        $this->broadcastSafely(new CallMissed($payload));















        Log::info('Call missed.', [







            'call_id' => $call->id,







            'caller_id' => $call->caller_id,







            'receiver_id' => $call->receiver_id,







            'conversation_id' => $call->conversation_id,







        ]);















        return true;







    }















    private function createCall(User $caller, User $receiver, ChatThread $thread, CallType $type, CallStatus $status): Call







    {







        return DB::transaction(function () use ($caller, $receiver, $thread, $type, $status): Call {







            $call = Call::create([







                'caller_id' => $caller->id,







                'receiver_id' => $receiver->id,







                'conversation_id' => $thread->id,







                'agora_channel' => '',







                'call_type' => $type,







                'status' => $status,







                'ring_expires_at' => now()->addSeconds($this->ringTimeout()),







                'started_at' => now(),







                'duration_seconds' => 0,







                'metadata' => [],







            ]);















            $call->forceFill([







                'agora_channel' => $this->channelName($call),







            ])->save();















            return $call->fresh(['caller', 'receiver', 'conversation']);







        });







    }















        private function activeCallForThread(ChatThread $thread): ?Call



    {



        $call = Call::query()



            ->with(['caller', 'receiver', 'conversation'])



            ->where('conversation_id', $thread->id)



            ->whereIn('status', [



                CallStatus::Calling->value,



                CallStatus::Ringing->value,



                CallStatus::Accepted->value,



                CallStatus::Connected->value,



            ])



            ->whereNull('ended_at')



            ->latest('id')



            ->first();







        if (! $call) {



            return null;



        }







        if ($this->markMissedIfExpired($call)) {



            return null;



        }







        if (in_array($call->status, [CallStatus::Missed, CallStatus::Cancelled, CallStatus::Rejected, CallStatus::Ended, CallStatus::Busy], true)) {



            return null;



        }







        return $call;



    }







private function threadForUser(User $user, int $threadId): ChatThread







    {







        $thread = ChatThread::with(['sender', 'receiver'])->find($threadId);















        if (! $thread) {







            throw new ApiException(translate('Chat thread not found.'), 404, 'not_found');







        }















        if (! in_array((int) $user->id, [(int) $thread->sender_user_id, (int) $thread->receiver_user_id], true)) {







            throw new ApiException(translate('Chat thread not found.'), 404, 'not_found');







        }















        if ((int) $thread->active !== 1 || ! empty($thread->blocked_by_user)) {







            throw new ApiException(translate('This chat is blocked.'), 403, 'chat_blocked');







        }















        return $thread;







    }















    private function callForUser(User $user, int $callId): Call







    {







        $call = Call::with(['caller', 'receiver', 'conversation'])->find($callId);















        if (! $call || ! in_array((int) $user->id, [(int) $call->caller_id, (int) $call->receiver_id], true)) {







            throw new ApiException(translate('Call not found.'), 404, 'not_found');







        }















        return $call;







    }















    private function ensureCanCall(User $caller, User $receiver, ChatThread $thread): void







    {







        if ((int) $caller->id === (int) $receiver->id) {







            throw new ApiException(translate('You cannot call yourself.'), 422, 'invalid_call');







        }















        if (! $this->enabled()) {







            throw new ApiException(translate('Calling service unavailable.'), 503, 'service_unavailable');







        }















        if ($this->hasActiveCall($caller) && ! $this->hasActiveCallInThread($caller, $thread)) {







            throw new ApiException(translate('You already have an active call.'), 409, 'user_busy');







        }















        if ($this->hasActiveCall($receiver)) {







            throw new ApiException(translate('User is busy.'), 409, 'user_busy');







        }







    }















    private function ensureIsReceiver(Call $call, User $user): void







    {







        if ((int) $call->receiver_id !== (int) $user->id) {







            throw new ApiException(translate('Only the receiver can perform this action.'), 403, 'forbidden');







        }







    }















    private function ensureIsCaller(Call $call, User $user): void







    {







        if ((int) $call->caller_id !== (int) $user->id) {







            throw new ApiException(translate('Only the caller can perform this action.'), 403, 'forbidden');







        }







    }















    private function otherParticipant(ChatThread $thread, User $user): User







    {







        return (int) $thread->sender_user_id === (int) $user->id ? $thread->receiver : $thread->sender;







    }















    private function hasActiveCall(User $user): bool







    {







        return Call::query()







            ->where(function ($query) use ($user): void {







                $query->where('caller_id', $user->id)







                    ->orWhere('receiver_id', $user->id);







            })







            ->whereIn('status', [







                CallStatus::Calling->value,







                CallStatus::Ringing->value,







                CallStatus::Accepted->value,







                CallStatus::Connected->value,







            ])







            ->whereNull('ended_at')







            ->exists();







    }















    private function hasActiveCallInThread(User $user, ChatThread $thread): bool







    {







        return Call::query()







            ->where('conversation_id', $thread->id)







            ->where(function ($query) use ($user): void {







                $query->where('caller_id', $user->id)







                    ->orWhere('receiver_id', $user->id);







            })







            ->whereIn('status', [







                CallStatus::Calling->value,







                CallStatus::Ringing->value,







                CallStatus::Accepted->value,







                CallStatus::Connected->value,







            ])







            ->whereNull('ended_at')







            ->exists();







    }















    private function normalizeType(string $callType): CallType







    {







        return CallType::from(strtolower(trim($callType)));







    }















    private function enabled(): bool







    {







        return (int) (get_setting('agora_calling_enabled') ?? 0) === 1;







    }















    private function ringTimeout(): int







    {







        return 30;







    }















    private function tokenExpirySeconds(): int







    {







        return max(60, (int) (get_setting('agora_token_expiry') ?? 3600));







    }















    private function agoraAppId(): string







    {







        return (string) (get_setting('agora_app_id') ?? '');







    }















    private function agoraAppCertificate(): string







    {







        return (string) (get_setting('agora_app_certificate') ?? '');







    }















    private function channelName(Call $call): string







    {







        return sprintf('call.%d.%d', $call->conversation_id, $call->id);







    }















    private function rtcPayload(Call $call, User $user): array







    {







        $appId = $this->agoraAppId();







        $certificate = $this->agoraAppCertificate();















        if ($appId === '' || $certificate === '') {







            Log::error('Agora configuration missing.', [







                'call_id' => $call->id,







                'caller_id' => $call->caller_id,







                'receiver_id' => $call->receiver_id,







                'conversation_id' => $call->conversation_id,







            ]);















            throw new ApiException(translate('Calling service unavailable.'), 503, 'agora_config_missing');







        }















        $expiresIn = $this->tokenExpirySeconds();







        $tokenExpiresAt = now()->addSeconds($expiresIn);







        $token = RtcTokenBuilder::buildTokenWithUid(







            $appId,







            $certificate,







            $call->agora_channel,







            $user->id,







            RtcTokenBuilder::RolePublisher,







            $tokenExpiresAt->timestamp







        );















        Log::info('Agora token generated.', [







            'call_id' => $call->id,







            'caller_id' => $call->caller_id,







            'receiver_id' => $call->receiver_id,







            'conversation_id' => $call->conversation_id,







            'user_id' => $user->id,







        ]);















        return [







            'app_id' => $appId,







            'channel' => $call->agora_channel,







            'token' => $token,







            'uid' => $user->id,







            'expires_at' => $tokenExpiresAt->toISOString(),







            'expires_in' => $expiresIn,







        ];







    }















    private function payload(Call $call, User $actor, ?string $message = null, ?array $rtc = null): array







    {







        $call->loadMissing(['caller', 'receiver', 'conversation']);















        return [







            'call' => [







                'id' => $call->id,







                'conversation_id' => $call->conversation_id,







                'thread_id' => $call->conversation_id,







                'agora_channel' => $call->agora_channel,







                'call_type' => $call->call_type instanceof CallType ? $call->call_type->value : (string) $call->call_type,







                'status' => $call->status instanceof CallStatus ? $call->status->value : (string) $call->status,







                'caller' => $this->userPayload($call->caller),







                'receiver' => $this->userPayload($call->receiver),

                'self' => $this->userPayload(((int) $call->caller_id === (int) $actor->id ? $call->caller : ((int) $call->receiver_id === (int) $actor->id ? $call->receiver : null))),

                'peer' => $this->userPayload(((int) $call->caller_id === (int) $actor->id ? $call->receiver : ((int) $call->receiver_id === (int) $actor->id ? $call->caller : null))),







                'ring_expires_at' => optional($call->ring_expires_at)->toISOString(),







                'started_at' => optional($call->started_at)->toISOString(),







                'answered_at' => optional($call->answered_at)->toISOString(),







                'ended_at' => optional($call->ended_at)->toISOString(),







                'duration_seconds' => (int) $call->duration_seconds,







                'ended_by_user_id' => $call->ended_by_user_id ? (int) $call->ended_by_user_id : null,







                'metadata' => $call->metadata ?? [],







                'message' => $message,







                'actor_id' => $actor->id,







            ],







            'rtc' => $rtc,







        ];







    }















    private function userPayload(?User $user): array







    {







        if (! $user) {







            return [];







        }















        return [







            'id' => $user->id,







            'code' => $user->code,







            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),







            'photo' => $user->photo ? uploaded_asset($user->photo) : static_asset('assets/img/avatar-place.png'),







        ];







    }















        private function durationSeconds(Call $call): int

    {

        if (! $call->answered_at) {

            return 0;

        }



        $endedAt = now();

        $seconds = (int) Carbon::parse($call->answered_at)->diffInSeconds($endedAt);



        return max(0, $seconds);

    }



private function broadcastSafely(object $event): void







    {







        try {







            broadcast($event)->toOthers();







        } catch (Throwable $throwable) {







            Log::warning('Call broadcast failed.', [







                'event' => $event::class,







                'message' => $throwable->getMessage(),







            ]);







        }







    }







}







