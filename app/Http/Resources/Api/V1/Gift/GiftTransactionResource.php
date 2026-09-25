<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Gift;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GiftTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isSender = $user && (int) $user->id === (int) $this->sender_id;

        return [
            'id' => $this->id,
            'gift' => $this->gift ? (new GiftResource($this->gift))->resolve($request) : null,
            'sender' => $this->sender ? [
                'id' => $this->sender->id,
                'name' => trim(($this->sender->first_name ?? '') . ' ' . ($this->sender->last_name ?? '')),
            ] : null,
            'receiver' => $this->receiver ? [
                'id' => $this->receiver->id,
                'name' => trim(($this->receiver->first_name ?? '') . ' ' . ($this->receiver->last_name ?? '')),
            ] : null,
            'coins' => $this->coins,
            'message' => $this->message,
            'status' => $this->status,
            'direction' => $isSender ? 'sent' : 'received',
            'created_at' => optional($this->created_at)->toISOString(),
        ];
    }
}
