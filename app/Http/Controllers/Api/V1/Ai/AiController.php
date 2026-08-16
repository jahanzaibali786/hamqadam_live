<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Ai;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Ai\AiTextRequest;
use App\Http\Resources\Api\V1\Ai\AiResultResource;
use App\Services\Api\V1\Ai\AiFeatureService;
use Illuminate\Http\JsonResponse;

class AiController extends ApiController
{
    public function __construct(private readonly AiFeatureService $ai)
    {
    }

    public function bio(AiTextRequest $request): JsonResponse
    {
        return $this->success(new AiResultResource($this->ai->bio($request->user(), $request->validated())), 'AI bio generated.');
    }

    public function conversationStarters(AiTextRequest $request): JsonResponse
    {
        return $this->success(new AiResultResource($this->ai->conversationStarters($request->user(), $request->validated())));
    }

    public function profileQuality(AiTextRequest $request): JsonResponse
    {
        return $this->success(new AiResultResource($this->ai->profileQuality($request->user(), $request->validated())));
    }

    public function scamCheck(AiTextRequest $request): JsonResponse
    {
        return $this->success(new AiResultResource($this->ai->safetyScan($request->user(), 'scam_detection', $request->validated())));
    }

    public function redFlagCheck(AiTextRequest $request): JsonResponse
    {
        return $this->success(new AiResultResource($this->ai->safetyScan($request->user(), 'red_flag_detection', $request->validated())));
    }
}
