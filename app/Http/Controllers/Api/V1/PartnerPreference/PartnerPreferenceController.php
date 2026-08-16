<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\PartnerPreference;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\PartnerPreference\UpdatePartnerPreferenceRequest;
use App\Http\Resources\Api\V1\PartnerPreference\PartnerPreferenceResource;
use App\Services\Api\V1\PartnerPreference\PartnerPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerPreferenceController extends ApiController
{
    public function __construct(private readonly PartnerPreferenceService $preferences)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return $this->success(new PartnerPreferenceResource($this->preferences->get($request->user())));
    }

    public function update(UpdatePartnerPreferenceRequest $request): JsonResponse
    {
        $preference = $this->preferences->update($request->user(), $request->validated());

        return $this->success(new PartnerPreferenceResource($preference), 'Partner preferences updated successfully.');
    }

    public function clear(Request $request): JsonResponse
    {
        $preference = $this->preferences->clear($request->user());

        return $this->success(new PartnerPreferenceResource($preference), 'Partner preferences cleared successfully.');
    }
}

