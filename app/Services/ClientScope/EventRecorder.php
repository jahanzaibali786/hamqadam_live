<?php

namespace App\Services\ClientScope;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Http\Request;

/** Records only server-confirmed actions. Never accepts a client-supplied user id. */
class EventRecorder
{
    public function record(Request $request, string $event, string $feature, array $metadata = []): void
    {
        $user = $request->user();
        if (! $user instanceof User) {
            return;
        }
        AnalyticsEvent::query()->create([
            'user_id' => $user->id,
            'event_name' => $event,
            'feature_key' => $feature,
            'session_key' => $request->hasSession() ? $request->session()->getId() : null,
            'platform' => 'web',
            'source' => 'client_scope',
            // Do not store IP addresses, account identifiers or message text.
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
