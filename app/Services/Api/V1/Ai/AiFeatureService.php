<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Ai;

use App\Models\AiFeatureRequest;
use App\Models\User;

class AiFeatureService
{
    public function bio(User $user, array $input): AiFeatureRequest
    {
        $member = $user->member;
        $bio = trim(sprintf(
            '%s is a thoughtful, family-oriented person seeking a compatible partner built on respect, shared values, and clear communication.',
            trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'This member'
        ));

        if (! empty($input['text'])) {
            $bio .= ' ' . mb_substr($input['text'], 0, 220);
        }

        $member?->forceFill(['ai_generated_bio' => $bio])?->save();

        return $this->log($user, 'bio_generator', $input, ['bio' => $bio]);
    }

    public function conversationStarters(User $user, array $input): AiFeatureRequest
    {
        return $this->log($user, 'conversation_starters', $input, [
            'starters' => [
                'What values are most important to you in family life?',
                'How do you like to spend meaningful time with loved ones?',
                'What are your hopes for the next few years?',
            ],
        ]);
    }

    public function profileQuality(User $user, array $input): AiFeatureRequest
    {
        $member = $user->member;
        $score = 40;
        $tips = [];

        if ($user->photo) {
            $score += 15;
        } else {
            $tips[] = 'Add a clear profile photo.';
        }

        if ($member?->introduction) {
            $score += 15;
        } else {
            $tips[] = 'Write a warm introduction.';
        }

        if ($member?->verification_status === 'verified') {
            $score += 20;
        } else {
            $tips[] = 'Complete identity verification.';
        }

        if ($member?->profile_completion_percentage) {
            $score += min(10, (int) $member->profile_completion_percentage / 10);
        }

        return $this->log($user, 'profile_quality_checker', $input, [
            'score' => min(100, (int) $score),
            'tips' => $tips,
        ]);
    }

    public function safetyScan(User $user, string $feature, array $input): AiFeatureRequest
    {
        $text = mb_strtolower((string) ($input['text'] ?? ''));
        $signals = [];

        foreach (['money', 'bank', 'crypto', 'whatsapp', 'urgent', 'secret', 'password'] as $term) {
            if (str_contains($text, $term)) {
                $signals[] = $term;
            }
        }

        $score = min(100, count($signals) * 18);

        return $this->log($user, $feature, $input, [
            'risk_score' => $score,
            'risk_level' => $score >= 60 ? 'high' : ($score >= 30 ? 'medium' : 'low'),
            'signals' => $signals,
            'recommendation' => $score >= 60 ? 'Send to moderation queue.' : 'No immediate action required.',
        ]);
    }

    private function log(User $user, string $feature, array $input, array $output): AiFeatureRequest
    {
        return AiFeatureRequest::create([
            'user_id' => $user->id,
            'feature' => $feature,
            'prompt' => $input['text'] ?? null,
            'input' => $input,
            'output' => $output,
            'provider' => env('OPENAI_API_KEY') ? 'openai_ready' : 'local',
            'status' => 'completed',
        ]);
    }
}
