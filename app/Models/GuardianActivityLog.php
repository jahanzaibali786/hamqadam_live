<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianActivityLog extends Model
{
    protected $fillable = [
        'guardian_link_id',
        'guardian_user_id',
        'profile_user_id',
        'action',
        'resource_type',
        'resource_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_user_id');
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(User::class, 'profile_user_id');
    }

    /** Records one audit entry; never allowed to fail the action it logs. */
    public static function record(
        ?int $guardianLinkId,
        ?int $guardianUserId,
        int $profileUserId,
        string $action,
        ?string $resourceType = null,
        ?int $resourceId = null,
        array $metadata = [],
    ): void {
        try {
            self::create([
                'guardian_link_id' => $guardianLinkId,
                'guardian_user_id' => $guardianUserId,
                'profile_user_id' => $profileUserId,
                'action' => $action,
                'resource_type' => $resourceType,
                'resource_id' => $resourceId,
                'metadata' => $metadata ?: null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
