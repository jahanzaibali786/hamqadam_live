<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\Step\Step10Request;
use App\Http\Requests\Api\V1\Auth\Step\Step12Request;
use App\Http\Requests\Api\V1\Auth\Step\Step2Request;
use App\Http\Requests\Api\V1\Auth\Step\Step3Request;
use App\Http\Requests\Api\V1\Auth\Step\Step4Request;
use App\Http\Requests\Api\V1\Auth\Step\Step5Request;
use App\Http\Requests\Api\V1\Auth\Step\Step6Request;
use App\Http\Requests\Api\V1\Auth\Step\Step7Request;
use App\Http\Requests\Api\V1\Auth\Step\Step8Request;
use App\Http\Requests\Api\V1\Auth\Step\Step9Request;
use App\Http\Resources\Api\V1\Auth\UserResource;
use App\Services\Api\V1\Auth\RegistrationStepService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StepRegistrationController extends ApiController
{
    public function __construct(
        private readonly RegistrationStepService $stepService,
    ) {
    }

    public function step2(Step2Request $request): JsonResponse
    {
        $this->stepService->step2($request->user(), $request->validated());
        return $this->success(
            new UserResource($request->user()->load('member')),
            'Basic profile saved.',
        );
    }

    public function step3(Step3Request $request): JsonResponse
    {
        $this->stepService->step3($request->user(), $request->validated());
        return $this->success(
            new UserResource($request->user()->load('member')),
            'About me & personality saved.',
        );
    }

    public function step4(Step4Request $request): JsonResponse
    {
        $this->stepService->step4($request->user(), $request->validated());
        return $this->success(
            new UserResource($request->user()->load('member')),
            'Religion & culture saved.',
        );
    }

    public function step5(Step5Request $request): JsonResponse
    {
        $this->stepService->step5($request->user(), $request->validated());
        return $this->success(
            new UserResource($request->user()->load('member')),
            'Education & career saved.',
        );
    }

    public function step6(Step6Request $request): JsonResponse
    {
        $this->stepService->step6($request->user(), $request->validated());
        return $this->success(
            new UserResource($request->user()->load('member')),
            'Family details saved.',
        );
    }

    public function step7(Step7Request $request): JsonResponse
    {
        $this->stepService->step7($request->user(), $request->validated());
        return $this->success(
            new UserResource($request->user()->load('member')),
            'Marriage & future plans saved.',
        );
    }

    public function step8(Step8Request $request): JsonResponse
    {
        $this->stepService->step8($request->user(), $request->validated());
        return $this->success(
            new UserResource($request->user()->load('member')),
            'Lifestyle & interests saved.',
        );
    }

    public function step9(Step9Request $request): JsonResponse
    {
        $this->stepService->step9($request->user(), $request->validated());
        return $this->success(
            new UserResource($request->user()->load('member')),
            'Profile media saved.',
        );
    }

    public function step10(Step10Request $request): JsonResponse
    {
        $this->stepService->step10($request->user(), $request->validated());
        return $this->success(
            new UserResource($request->user()->load('member')),
            'Partner preferences saved.',
        );
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user()->load('member');
        $member = $user->member;

        $completed = [];
        if ($member) {
            if ($member->gender && $member->birthday) {
                $completed[] = 'step1';
            }
            if ($member->marital_status_id) {
                $completed[] = 'step2';
            }
            if ($member->personality_type || $member->looking_for) {
                $completed[] = 'step3';
            }
            if ($member->religious_practice_level || $user->spiritual_backgrounds()->exists()) {
                $completed[] = 'step4';
            }
            if ($member->education_level || $user->education()->exists()) {
                $completed[] = 'step5';
            }
            if ($member->family_type) {
                $completed[] = 'step6';
            }
            if ($member->marriage_timeline) {
                $completed[] = 'step7';
            }
            if ($member->hobbies) {
                $completed[] = 'step8';
            }
            if ($user->photo || $member->cover_photo) {
                $completed[] = 'step9';
            }
            if ($user->partner_expectations) {
                $completed[] = 'step10';
            }
            if ($user->profile_verification_requests()->exists()) {
                $completed[] = 'step11';
            }
            if ($user->profile_privacy_setting) {
                $completed[] = 'step12';
            }
        }

        return $this->success([
            'total_steps' => 12,
            'completed_steps' => $completed,
            'next_step' => $this->findNextStep($completed),
            'profile_completion_percentage' => $member?->profile_completion_percentage ?? 0,
        ]);
    }

    private function findNextStep(array $completed): string
    {
        $steps = ['step1', 'step2', 'step3', 'step4', 'step5', 'step6',
                  'step7', 'step8', 'step9', 'step10', 'step11', 'step12'];
        foreach ($steps as $step) {
            if (! in_array($step, $completed, true)) {
                return $step;
            }
        }
        return 'completed';
    }
}
