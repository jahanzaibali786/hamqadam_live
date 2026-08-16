<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Auth;

use App\Dto\Auth\IssuedTokenData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthTokenResource extends JsonResource
{
    public function __construct(private readonly IssuedTokenData $tokenData)
    {
        parent::__construct($tokenData);
    }

    public function toArray(Request $request): array
    {
        return [
            'access_token' => $this->tokenData->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $this->tokenData->expiresAt->toISOString(),
            'device_session_id' => $this->tokenData->deviceSession->id,
            'user' => new UserResource($this->tokenData->user->loadMissing('member.package')),
        ];
    }
}
