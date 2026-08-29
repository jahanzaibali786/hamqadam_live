@foreach (($calls ?? collect())->reverse() as $call)
    @php
        $isCaller = (int) $call->caller_id === (int) Auth::id();
        $status = $call->status instanceof \App\Enums\CallStatus ? $call->status->value : (string) $call->status;
        $type = $call->call_type instanceof \App\Enums\CallType ? $call->call_type->value : (string) $call->call_type;
        $icon = $type === 'video' ? 'la-video' : 'la-phone';
        $label = match ($status) {
            'missed' => translate('Missed call'),
            'rejected' => translate('Declined call'),
            'cancelled' => translate('Cancelled call'),
            'busy' => translate('Busy call'),
            default => $type === 'video' ? translate('Video call') : translate('Audio call'),
        };
        $duration = $call->duration_seconds ? gmdate('i:s', (int) $call->duration_seconds) : '00:00';
        $time = optional($call->ended_at ?? $call->answered_at ?? $call->started_at ?? $call->created_at)->diffForHumans();
    @endphp
    <div class="chat-coversation {{ $isCaller ? 'right' : '' }} call-log-item">
        <div class="media">
            @if (! $isCaller)
                <span class="avatar avatar-xs flex-shrink-0 call-log-avatar">
                    <span class="call-log-icon"><i class="las {{ $icon }}"></i></span>
                </span>
            @endif
            <div class="media-body call-log-body">
                <div class="text call-log-card">
                    <div class="call-log-heading">
                        <span class="call-log-title"><i class="las {{ $icon }}"></i><span>{{ $label }}</span></span>
                        <span class="call-log-status">{{ strtoupper($status) }}</span>
                    </div>
                    <div class="call-log-duration"><i class="las la-clock"></i><span>{{ $duration }}</span></div>
                </div>
                <span class="time call-log-time">{{ $time }}</span>
            </div>
            @if ($isCaller)
                <span class="avatar avatar-xs flex-shrink-0 call-log-avatar">
                    <span class="call-log-icon"><i class="las {{ $icon }}"></i></span>
                </span>
            @endif
        </div>
    </div>
@endforeach
