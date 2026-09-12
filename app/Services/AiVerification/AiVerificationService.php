<?php

declare(strict_types=1);

namespace App\Services\AiVerification;

use App\Models\AiVerificationAttempt;
use App\Models\ProfileVerificationRequest;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Orchestrates AI identity verification for a user.
 *
 * Contract with the rest of the app:
 *   - It NEVER throws. Registration must not fail because the model is down.
 *   - Every attempt is recorded in ai_verification_attempts, including
 *     skipped ones, so "why is this user not verified?" is always answerable.
 *   - The model returns a *recommendation*. Auto-applying it is opt-in via
 *     config('ai_verification.auto_apply').
 */
class AiVerificationService
{
    /** Temp files this instance created, cleaned up in the destructor. */
    private array $tempFiles = [];

    public function __construct(private readonly AiVerificationClient $client)
    {
    }

    public function __destruct()
    {
        foreach ($this->tempFiles as $f) {
            @unlink($f);
        }
    }

    /**
     * Run verification for a user using whatever images the database holds.
     *
     * @return array{status:string,recommendation:string|null,message:string,attempt_id:int|null}
     */
    public function verifyUser(User $user, string $source, ?ProfileVerificationRequest $request = null): array
    {
        $verificationId = $this->buildVerificationId($user, $source);

        $attempt = AiVerificationAttempt::create([
            'user_id' => $user->id,
            'source' => $source,
            'profile_verification_request_id' => $request?->id,
            'verification_id' => $verificationId,
            'status' => AiVerificationAttempt::STATUS_PENDING,
        ]);

        try {
            /*
             * When no request was handed in, look one up before falling back to
             * the profile photo. Registration step 13 ("Identity Verification")
             * creates a ProfileVerificationRequest and attaches the CNIC front,
             * CNIC back and selfie, so by the end of registration those images
             * DO exist. Using only the profile photo here threw away the very
             * images that let the model do a real identity comparison.
             */
            $request ??= $this->latestVerificationRequest($user);

            // Record which request we resolved to. Without this the audit row
            // says nothing about where its images came from, which defeats the
            // point of keeping the attempts table.
            if ($request && $attempt->profile_verification_request_id === null) {
                $attempt->profile_verification_request_id = $request->id;
                $attempt->save();
            }

            $images = $request
                ? $this->imagesFromRequest($request)
                : $this->imagesFromProfile($user);

            // A request may exist but hold no usable selfie yet (documents
            // still uploading, or a base64 decode failed). Fall back rather
            // than skipping verification entirely.
            if ($images === [] && $request) {
                $request = null;
                $attempt->profile_verification_request_id = null;
                $attempt->save();
                $images = $this->imagesFromProfile($user);
            }

            if ($images === []) {
                return $this->finishSkipped(
                    $user,
                    $attempt,
                    'No usable image on file. Upload a profile photo or submit your CNIC documents, then run verification again.'
                );
            }

            $result = $this->client->verify($verificationId, $images, [
                'user_reference' => (string) $user->code,
                'enrol_on_success' => config('ai_verification.enrol_on_success') ? 'true' : 'false',
            ]);

            $attempt->images_sent = array_keys($images);
            $attempt->http_status = $result['status'];
            $attempt->duration_ms = $result['duration_ms'];

            if (! $result['ok']) {
                return $this->finishFailed($user, $attempt, $result);
            }

            return $this->finishCompleted($user, $attempt, $result['body'], $request);
        } catch (Throwable $e) {
            Log::error('ai_verification.unexpected', [
                'user_id' => $user->id,
                'source' => $source,
                'message' => $e->getMessage(),
            ]);

            return $this->finishFailed($user, $attempt, [
                'status' => null,
                'body' => [],
                'error' => $e->getMessage(),
                'error_code' => 'unexpected_error',
                'duration_ms' => 0,
            ]);
        }
    }

    /** Current AI verification state for a user, safe to expose to app/web. */
    public function statusFor(User $user): array
    {
        $member = $user->member;
        $latest = AiVerificationAttempt::where('user_id', $user->id)->latest('id')->first();

        return [
            'status' => $member->ai_verification_status ?? 'not_started',
            'recommendation' => $member->ai_verification_recommendation ?? null,
            'reason' => $member->ai_verification_reason ?? $latest?->review_reason,
            'attempts' => (int) ($member->ai_verification_attempts ?? 0),
            'verified_at' => optional($member?->ai_verified_at)->toISOString(),
            'last_attempt_at' => optional($member?->ai_verification_last_attempt_at)->toISOString(),
            'can_retry' => $latest ? $latest->isRetryable() : true,
            'message' => $this->humanMessage($member->ai_verification_status ?? 'not_started', $latest),
            'last_error' => $latest?->error_message,
        ];
    }

    // ---------------------------------------------------------------- images

    /**
     * Registration-time images. Only the profile photo exists at this point -
     * registration collects no CNIC and no separate live selfie.
     *
     * The photo is sent as live_selfie ONLY. Sending the same file as both
     * live_selfie and profile_image would make the model compare an image with
     * itself, score ~1.0, and report a meaningless identity "match".
     *
     * @return array<string,string>
     */
    private function imagesFromProfile(User $user): array
    {
        $path = $this->resolveUploadPath($user->photo);

        return $path ? ['live_selfie' => $path] : [];
    }

    /**
     * Document-submission images. This is where the model earns its keep: a
     * real selfie plus the CNIC front means it can do identity comparison and
     * CNIC portrait matching, not just face detection.
     *
     * @return array<string,string>
     */
    private function imagesFromRequest(ProfileVerificationRequest $request): array
    {
        $images = [];

        foreach ($request->documents as $doc) {
            $type = $doc->type instanceof \BackedEnum ? $doc->type->value : (string) $doc->type;

            $field = match ($type) {
                'selfie' => 'live_selfie',
                'cnic_front' => 'cnic_image',
                'face' => 'profile_image',
                default => null,
            };

            if ($field === null || isset($images[$field])) {
                continue;
            }

            $path = $doc->upload_id
                ? $this->resolveUploadPath($doc->upload_id)
                : $this->resolveRelativePath((string) $doc->file_path);

            if ($path) {
                $images[$field] = $path;
            }
        }

        // Fall back to the account photo for profile_image if the submission
        // did not include a separate face image. This is a genuinely different
        // photograph from the selfie, so the comparison stays meaningful.
        if (! isset($images['profile_image'])) {
            $photo = $this->resolveUploadPath($request->user?->photo);
            if ($photo && ! in_array($photo, $images, true)) {
                $images['profile_image'] = $photo;
            }
        }

        // The model requires a live selfie; without one there is nothing to
        // verify the other images against.
        return isset($images['live_selfie']) ? $images : [];
    }

    /**
     * Newest verification request that still has a chance of carrying usable
     * documents. Ordered newest-first; final (approved/rejected) requests are
     * excluded because re-running the model against a settled decision is not
     * what any caller wants.
     */
    private function latestVerificationRequest(User $user): ?ProfileVerificationRequest
    {
        return ProfileVerificationRequest::with(['documents', 'user'])
            ->where('user_id', $user->id)
            ->whereNotIn('status', ['approved', 'rejected'])
            ->latest('id')
            ->first();
    }

    private function resolveUploadPath(mixed $uploadId): ?string
    {
        if (! $uploadId) {
            return null;
        }

        $upload = Upload::find($uploadId);

        return $upload?->file_name ? $this->resolveRelativePath((string) $upload->file_name) : null;
    }

    /**
     * uploads.file_name is relative, e.g. "uploads/all/abc.jpeg". static_asset()
     * serves it from <project>/public/, so that is the primary location. A local
     * clone restored from a live DB dump has the rows but not the files, hence
     * the optional remote fetch.
     */
    private function resolveRelativePath(string $relative): ?string
    {
        if ($relative === '') {
            return null;
        }

        if (is_file($relative) && is_readable($relative)) {
            return $relative;
        }

        foreach ([
            public_path($relative),
            base_path('public/'.$relative),
            base_path($relative),
            storage_path('app/public/'.$relative),
            storage_path('app/'.$relative),
        ] as $candidate) {
            if (is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }

        return $this->fetchRemote($relative);
    }

    private function fetchRemote(string $relative): ?string
    {
        $base = config('ai_verification.remote_asset_base');
        if (! $base) {
            return null;
        }

        try {
            $url = rtrim((string) $base, '/').'/'.ltrim($relative, '/');
            $response = Http::timeout(30)->get($url);

            if (! $response->successful() || $response->body() === '') {
                return null;
            }

            $ext = pathinfo($relative, PATHINFO_EXTENSION) ?: 'jpg';
            $tmp = tempnam(sys_get_temp_dir(), 'aiverif_').'.'.$ext;
            file_put_contents($tmp, $response->body());
            $this->tempFiles[] = $tmp;

            return $tmp;
        } catch (Throwable) {
            return null;
        }
    }

    // --------------------------------------------------------------- results

    private function finishCompleted(
        User $user,
        AiVerificationAttempt $attempt,
        array $body,
        ?ProfileVerificationRequest $request
    ): array {
        $recommendation = (string) ($body['recommendation'] ?? 'MANUAL_REVIEW');
        $selfie = $body['selfie_detection'] ?? [];
        $reviewReason = $this->extractReviewReason($body, $recommendation);

        $attempt->fill([
            'status' => AiVerificationAttempt::STATUS_COMPLETED,
            'recommendation' => $recommendation,
            'review_reason' => $reviewReason,
            'identity_confidence_score' => $body['identity_confidence_score'] ?? null,
            'fraud_risk_score' => $body['fraud_risk_score'] ?? null,
            'fraud_risk_level' => $body['fraud_risk_level'] ?? null,
            'face_detected' => $selfie['face_detected'] ?? null,
            'response_payload' => json_encode($body),
        ])->save();

        $requiresHumanReview = ($body['requires_human_review'] ?? false) === true;
        $memberStatus = $requiresHumanReview ? 'manual_review' : match ($recommendation) {
            'APPROVE' => 'approved',
            'REJECT' => 'rejected',
            default => 'manual_review',
        };

        $this->updateMember($user, $memberStatus, $recommendation, $recommendation === 'APPROVE', $reviewReason);

        if ($request) {
            $this->applyToRequest($request, $body, $recommendation);
        }

        return [
            'status' => $memberStatus,
            'recommendation' => $recommendation,
            'review_reason' => $reviewReason,
            'message' => $this->humanMessage($memberStatus, $attempt),
            'attempt_id' => $attempt->id,
        ];
    }

    private function finishFailed(User $user, AiVerificationAttempt $attempt, array $result): array
    {
        $attempt->fill([
            'status' => AiVerificationAttempt::STATUS_FAILED,
            'error_message' => Str::limit((string) ($result['error'] ?? 'Unknown error'), 500),
            'error_code' => $result['error_code'] ?? null,
            'http_status' => $result['status'] ?? null,
            'duration_ms' => $result['duration_ms'] ?? null,
            'response_payload' => ! empty($result['body']) ? json_encode($result['body']) : null,
        ])->save();

        $this->updateMember($user, 'failed', null, false);

        return [
            'status' => 'failed',
            'recommendation' => null,
            'message' => 'Verification could not be completed right now. You can try again from your dashboard.',
            'attempt_id' => $attempt->id,
        ];
    }

    private function finishSkipped(User $user, AiVerificationAttempt $attempt, string $why): array
    {
        $attempt->fill([
            'status' => AiVerificationAttempt::STATUS_SKIPPED,
            'error_message' => $why,
            'error_code' => 'no_images',
        ])->save();

        $this->updateMember($user, 'not_started', null, false);

        return [
            'status' => 'not_started',
            'recommendation' => null,
            'message' => $why,
            'attempt_id' => $attempt->id,
        ];
    }

    private function updateMember(User $user, string $status, ?string $recommendation, bool $verified, ?string $reason = null): void
    {
        $member = $user->member;
        if (! $member) {
            return;
        }

        $now = now();
        $member->forceFill([
            'ai_verification_status' => $status,
            'ai_verification_recommendation' => $recommendation,
            'ai_verification_reason' => $status === 'manual_review' ? $reason : null,
            'ai_verification_attempts' => (int) ($member->ai_verification_attempts ?? 0) + 1,
            'ai_verification_last_attempt_at' => $now,
            'ai_verified_at' => $verified ? $now : $member->ai_verified_at,
            'manual_review_started_at' => $status === 'manual_review' ? ($member->manual_review_started_at ?? $now) : null,
            'manual_review_expires_at' => $status === 'manual_review' ? ($member->manual_review_expires_at ?? $now->copy()->addHours(12)) : null,
        ])->save();
    }

    /**
     * Write the AI outcome onto the document request. face_match_status and
     * face_match_score already existed on this table unused - they were
     * plainly meant for this, so populate them instead of adding duplicates.
     */
    private function applyToRequest(ProfileVerificationRequest $request, array $body, string $recommendation): void
    {
        $fields = [
            'face_match_status' => (($body['requires_human_review'] ?? false) === true) ? 'manual_review' : match ($recommendation) {
                'APPROVE' => 'matched',
                'REJECT' => 'not_matched',
                default => 'manual_review',
            },
            'face_match_score' => $body['identity_confidence_score'] ?? null,
            'ai_recommendation' => $recommendation,
            'ai_fraud_risk_score' => $body['fraud_risk_score'] ?? null,
            'ai_checked_at' => now(),
        ];

        // Auto-applying a decision is opt-in. By default a human still reviews.
        if (($body['requires_human_review'] ?? false) !== true && $recommendation === 'APPROVE' && config('ai_verification.auto_apply.approve')) {
            $fields['status'] = 'approved';
            $fields['reviewed_at'] = now();
        } elseif ($recommendation === 'REJECT' && config('ai_verification.auto_apply.reject')) {
            $fields['status'] = 'rejected';
            $fields['reviewed_at'] = now();
            $fields['rejection_reason'] = $this->firstReason($body) ?? 'Automated identity check failed.';
        }

        $request->forceFill($fields)->save();
    }

    private function extractReviewReason(array $body, string $recommendation): ?string
    {
        $reasons = [];
        $add = function (mixed $value) use (&$reasons): void {
            if (! is_string($value) || trim($value) === '') {
                return;
            }

            $value = $this->humanizeReviewFragment($value);
            if ($value !== '') {
                $reasons[] = $value;
            }
        };

        if (($body['matching']['comparisons_made'] ?? null) === 0) {
            $add('No face comparison could be completed because no usable comparison was available.');
        }
        if (($body['cnic_ocr']['is_cnic'] ?? true) === false) {
            $add('The uploaded document was not recognized as a Pakistani CNIC.');
        }
        if (($body['cnic_portrait']['usable'] ?? true) === false) {
            $add('A usable portrait could not be located on the CNIC for comparison.');
        }
        if (($body['selfie_detection']['occlusion']['occluded'] ?? false) === true) {
            $add('The face in the selfie appears partially covered, which adds uncertainty to verification.');
        }
        if (($body['selfie_detection']['pose']['within_hard_limits'] ?? true) === false) {
            $add('The face is not positioned clearly enough for a reliable comparison.');
        }
        if (
            isset($body['cnic_ocr']['fields_present'], $body['cnic_ocr']['fields_missing'])
            && count((array) $body['cnic_ocr']['fields_present']) < 4
        ) {
            $add('The CNIC image did not contain enough readable information for verification.');
        }

        foreach (($body['recommendation_reasons'] ?? []) as $reason) {
            if (is_string($reason)) {
                $add($reason);
            } elseif (is_array($reason)) {
                $add($reason['message'] ?? null);
                $add($reason['reason'] ?? null);
            }
        }
        foreach (($body['matching']['reasons'] ?? []) as $reason) {
            $add(is_array($reason) ? ($reason['message'] ?? $reason['reason'] ?? null) : $reason);
        }
        foreach (($body['matching']['warnings'] ?? []) as $warning) {
            $add(is_array($warning) ? ($warning['message'] ?? null) : $warning);
        }
        foreach (($body['fraud']['top_factors'] ?? []) as $factor) {
            $add($factor);
        }
        foreach (($body['fraud']['signals'] ?? []) as $signal) {
            $add(is_array($signal) ? ($signal['message'] ?? null) : $signal);
        }
        foreach (($body['warnings'] ?? []) as $warning) {
            $add(is_array($warning) ? ($warning['message'] ?? null) : $warning);
        }

        $add($body['selfie_detection']['error_message'] ?? null);
        $add($body['cnic_ocr']['error_message'] ?? null);
        $add($body['cnic_ocr']['findings'][0]['message'] ?? null);

        if (($body['requires_human_review'] ?? false) === true) {
            $add(sprintf(
                'The verification model requested human review (recommendation: %s; automated decision: %s).',
                $recommendation,
                (($body['automated'] ?? false) ? 'yes' : 'no')
            ));
        }

        $reasons = array_values(array_unique(array_filter(array_map('trim', $reasons))));
        return $reasons === [] ? null : Str::limit(implode(' ', $reasons), 2000);
    }

    private function humanizeReviewFragment(string $value): string
    {
        $engineeringNote = 'Match thresholds are engineering defaults';
        $notePosition = stripos($value, $engineeringNote);
        if ($notePosition !== false) {
            $value = substr($value, 0, $notePosition);
        }

        $value = str_replace([
            'OCR_NOT_A_CNIC',
            'CNIC_FACE_NOT_FOUND',
            'PARTIAL_OCCLUSION',
            'NO_FACE_FOUND',
            'NO_COMPARISON',
            'INVALID_IMAGE',
        ], [
            'The uploaded document was not recognized as a Pakistani CNIC.',
            'No portrait could be found on the CNIC for comparison.',
            'The face appears partially covered or obstructed.',
            'No face could be found in one of the submitted images.',
            'No face comparison was available.',
            'The submitted image could not be processed.',
        ], $value);

        $value = str_replace([chr(9), chr(10), chr(13)], ' ', trim($value));
        if (in_array(strtolower($value), ['string', 'additionalprop1', 'additionalprop2', 'additionalprop3'], true)) {
            return '';
        }
        $value = trim($value, ' .');
        if ($value === '') {
            return '';
        }

        return str_ends_with($value, '.') ? $value : $value . '.';
    }

    private function firstReason(array $body): ?string
    {
        return $this->extractReviewReason($body, (string) ($body['recommendation'] ?? 'MANUAL_REVIEW'));
    }

    private function buildVerificationId(User $user, string $source): string
    {
        return sprintf('hq-%d-%s-%s', $user->id, $source, Str::lower(Str::random(8)));
    }

    private function humanMessage(string $status, ?AiVerificationAttempt $attempt): string
    {
        return match ($status) {
            'approved' => 'Your identity has been verified.',
            'rejected' => 'Automated identity verification did not pass. Our team will review your documents.',
            'manual_review' => 'Your verification needs a manual review. Submit your CNIC and a selfie to speed this up.',
            'failed' => 'Verification could not be completed. Please try again.',
            'pending' => 'Verification is in progress.',
            default => $attempt?->error_message
                ?: 'Verification has not run yet. Upload a photo and start verification.',
        };
    }
}









