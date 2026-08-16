<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VerificationDocumentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileVerificationDocument extends Model
{
    protected $fillable = [
        'profile_verification_request_id',
        'type',
        'upload_id',
        'file_path',
        'metadata',
    ];

    protected $casts = [
        'type' => VerificationDocumentType::class,
        'metadata' => 'array',
    ];

    public function verificationRequest(): BelongsTo
    {
        return $this->belongsTo(ProfileVerificationRequest::class, 'profile_verification_request_id');
    }
}
