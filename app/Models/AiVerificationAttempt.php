<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiVerificationAttempt extends Model
{
    use HasFactory;

    public const SOURCE_REGISTRATION_API = 'registration_api';
    public const SOURCE_REGISTRATION_WEB = 'registration_web';
    public const SOURCE_DOCUMENT_SUBMIT = 'document_submit';
    public const SOURCE_MANUAL_RETRY = 'manual_retry';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'user_id',
        'source',
        'profile_verification_request_id',
        'verification_id',
        'status',
        'recommendation',
        'identity_confidence_score',
        'fraud_risk_score',
        'fraud_risk_level',
        'face_detected',
        'images_sent',
        'response_payload',
        'error_message',
        'error_code',
        'http_status',
        'duration_ms',
    ];

    protected $casts = [
        'images_sent' => 'array',
        'face_detected' => 'boolean',
        'identity_confidence_score' => 'float',
        'fraud_risk_score' => 'float',
        'http_status' => 'integer',
        'duration_ms' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Full model response, decoded. Stored as text to survive large payloads. */
    public function responseArray(): array
    {
        if (! $this->response_payload) {
            return [];
        }

        return json_decode((string) $this->response_payload, true) ?: [];
    }

    public function isRetryable(): bool
    {
        return in_array($this->status, [self::STATUS_FAILED, self::STATUS_SKIPPED], true)
            || $this->recommendation === 'MANUAL_REVIEW';
    }
}
