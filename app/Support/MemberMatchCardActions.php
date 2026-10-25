<?php

namespace App\Support;

use App\Models\ChatThread;
use App\Models\ExpressInterest;
use App\Models\User;

final class MemberMatchCardActions
{
    /** Resolve the existing proposal state once per visible member card. */
    public static function between(User $viewer, User $person): array
    {
        $viewerId = (int) $viewer->id;
        $personId = (int) $person->id;

        $interest = ExpressInterest::query()
            ->where(function ($query) use ($viewerId, $personId) {
                $query->where(function ($pair) use ($viewerId, $personId) {
                    $pair->where('interested_by', $viewerId)->where('user_id', $personId);
                })->orWhere(function ($pair) use ($viewerId, $personId) {
                    $pair->where('interested_by', $personId)->where('user_id', $viewerId);
                });
            })->latest('id')->first();

        $status = $interest?->status instanceof \BackedEnum
            ? (int) $interest->status->value
            : (int) ($interest?->status ?? -1);

        $thread = null;
        if ($interest && $status === 1) {
            $thread = ChatThread::query()
                ->where(function ($query) use ($viewerId, $personId) {
                    $query->where(function ($pair) use ($viewerId, $personId) {
                        $pair->where('sender_user_id', $viewerId)->where('receiver_user_id', $personId);
                    })->orWhere(function ($pair) use ($viewerId, $personId) {
                        $pair->where('sender_user_id', $personId)->where('receiver_user_id', $viewerId);
                    });
                })->first();
        }

        return [
            'interest' => $interest,
            'status' => $status,
            'thread' => $thread,
            'display_name' => trim((string) ($person->first_name ?? '') . ' ' . (string) ($person->last_name ?? '')) ?: 'Member',
        ];
    }
}
