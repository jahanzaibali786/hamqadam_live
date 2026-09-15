<?php

declare(strict_types=1);

namespace App\Services\Api\V1\Verification;

use App\Jobs\RunAiVerification;
use App\Models\AiVerificationAttempt;
use App\Enums\ApiErrorCode;
use App\Enums\VerificationDocumentType;
use App\Enums\VerificationRequestStatus;
use App\Exceptions\ApiException;
use App\Models\ProfileVerificationDocument;
use App\Models\ProfileVerificationRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class VerificationService
{
    public function current(User $user): ?ProfileVerificationRequest
    {
        return ProfileVerificationRequest::with(['documents', 'reviewer'])
            ->where('user_id', $user->id)
            ->latest()
            ->first();
    }

    public function history(User $user): LengthAwarePaginator
    {
        return ProfileVerificationRequest::with(['documents', 'reviewer'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(20);
    }

    public function submit(User $user, array $data): ProfileVerificationRequest
    {
        $existing = $this->current($user);
        if ($existing && ! $existing->status->isFinal()) {
            throw new ApiException('You already have a verification request under review.', 409, ApiErrorCode::Conflict->value);
        }

        return DB::transaction(function () use ($user, $data) {
            $request = ProfileVerificationRequest::create([
                'user_id' => $user->id,
                'status' => VerificationRequestStatus::Submitted,
                'cnic_number' => $data['cnic_number'],
                'face_match_status' => 'pending',
                'submitted_at' => now(),
            ]);

            $this->storeDocument($request, VerificationDocumentType::CnicFront, $data['cnic_front']);
            $this->storeDocument($request, VerificationDocumentType::CnicBack, $data['cnic_back']);
            $this->storeDocument($request, VerificationDocumentType::Selfie, $data['selfie']);

            if (! empty($data['face'])) {
                $this->storeDocument($request, VerificationDocumentType::Face, $data['face']);
            }

            $user->member?->forceFill(['verification_status' => 'submitted'])?->save();

            /*
             * This is where AI verification earns its keep. Registration only
             * ever has one profile photo, so the model can do face detection
             * and quality/fraud checks but no identity comparison. Here we have
             * a live selfie AND the CNIC front, so it can actually compare the
             * face against the CNIC portrait and the account photo.
             *
             * Queued after the DB commit (afterResponse also runs after the
             * transaction closes) so the submit endpoint returns immediately
             * and a model outage cannot roll back a document submission.
             */
            RunAiVerification::dispatchAfterResponse(
                $user->id,
                AiVerificationAttempt::SOURCE_DOCUMENT_SUBMIT,
                $request->id
            );

            return $request->load(['documents', 'reviewer', 'user']);
        });
    }

    public function queue(User $moderator, array $filters): LengthAwarePaginator
    {
        $this->ensureModerator($moderator);

        $query = ProfileVerificationRequest::with(['user', 'documents', 'reviewer'])
            ->latest('submitted_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        } else {
            $query->whereIn('status', [
                VerificationRequestStatus::Submitted->value,
                VerificationRequestStatus::UnderReview->value,
            ]);
        }

        return $query->paginate((int) ($filters['per_page'] ?? 20));
    }

    public function showForModerator(User $moderator, int $id): ProfileVerificationRequest
    {
        $this->ensureModerator($moderator);

        return ProfileVerificationRequest::with(['user', 'documents', 'reviewer'])->findOrFail($id);
    }

    public function approve(User $moderator, int $id): ProfileVerificationRequest
    {
        $this->ensureModerator($moderator);

        return DB::transaction(function () use ($moderator, $id) {
            $request = ProfileVerificationRequest::with('user.member')->lockForUpdate()->findOrFail($id);
            $this->ensureReviewable($request);

            $request->forceFill([
                'status' => VerificationRequestStatus::Approved,
                'reviewed_by' => $moderator->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ])->save();

            $request->user->forceFill(['approved' => 1])->save();
            $request->user->member?->forceFill(['verification_status' => 'verified', 'ai_verification_status' => 'approved', 'verification_badge' => true, 'verification_badge_earned_at' => now(), 'manual_review_started_at' => null, 'manual_review_expires_at' => null])?->save();

            $this->notifyDecisionByEmail($request->user, true, null);

            return $request->fresh(['user', 'documents', 'reviewer']);
        });
    }

    public function reject(User $moderator, int $id, string $reason): ProfileVerificationRequest
    {
        $this->ensureModerator($moderator);

        return DB::transaction(function () use ($moderator, $id, $reason) {
            $request = ProfileVerificationRequest::with('user.member')->lockForUpdate()->findOrFail($id);
            $this->ensureReviewable($request);

            $request->forceFill([
                'status' => VerificationRequestStatus::Rejected,
                'reviewed_by' => $moderator->id,
                'reviewed_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $request->user->member?->forceFill(['verification_status' => 'rejected', 'verification_badge' => false, 'verification_badge_earned_at' => null, 'manual_review_started_at' => null, 'manual_review_expires_at' => null])?->save();

            $this->notifyDecisionByEmail($request->user, false, $reason);

            return $request->fresh(['user', 'documents', 'reviewer']);
        });
    }

    /**
     * Decision email to the member's registered address — the same contract
     * the admin web panel (MemberController) already honours, now also fired
     * from the API review actions.
     *
     * Approved → the member is told to log in again. Rejected → they get the
     * reason and are asked to re-apply for verification (or reach the help
     * center if they believe their data was correct).
     */
    private function notifyDecisionByEmail($user, bool $approved, ?string $reason): void
    {
        if (! $user || ! $user->email) {
            return;
        }

        $name = trim((string) ($user->first_name . ' ' . $user->last_name));
        $siteName = get_setting('website_name') ?: config('app.name');

        if ($approved) {
            $subject = 'Your ' . $siteName . ' verification is approved';
            $message = '<p>' . e($name) . ',</p>'
                . '<p>Good news! Your verification has been approved.</p>'
                . '<p>You can now log in to the ' . e($siteName) . ' mobile app and web portal, and continue using your account.</p>'
                . '<p>Regards,<br>' . e($siteName) . '</p>';
        } else {
            $subject = 'Your ' . $siteName . ' verification was not approved';
            $message = '<p>' . e($name) . ',</p>'
                . '<p>We are sorry, but your verification could not be approved at this time.</p>'
                . '<p><strong>Reason:</strong> ' . nl2br(e((string) $reason)) . '</p>'
                . '<p>If you believe your information was correct, please re-apply for verification with clearer documents — or contact our Help Center and our team will review your case again.</p>'
                . '<p><a href="' . e(route('contact_us')) . '">Contact Us</a></p>'
                . '<p>Regards,<br>' . e($siteName) . '</p>';
        }

        try {
            \Notification::route('mail', $user->email)
                ->notify(new \App\Notifications\EmailNotification($subject, $message));
        } catch (\Throwable $e) {
            \Log::warning('Verification decision email failed.', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function storeDocument(
        ProfileVerificationRequest $request,
        VerificationDocumentType $type,
        UploadedFile $file
    ): ProfileVerificationDocument {
        $uploadId = upload_api_file($file);

        return ProfileVerificationDocument::create([
            'profile_verification_request_id' => $request->id,
            'type' => $type,
            'upload_id' => $uploadId,
            'metadata' => [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ],
        ]);
    }

    private function ensureModerator(User $user): void
    {
        if ($user->user_type === 'member') {
            throw new ApiException('Only moderators can manage verification requests.', 403, ApiErrorCode::Forbidden->value);
        }
    }

    private function ensureReviewable(ProfileVerificationRequest $request): void
    {
        if ($request->status->isFinal()) {
            throw new ApiException('This verification request has already been reviewed.', 409, ApiErrorCode::Conflict->value);
        }
    }
}

