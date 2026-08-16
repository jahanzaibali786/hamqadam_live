<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageUsage extends Model
{
    protected $table = 'package_usages';

    protected $fillable = [
        'user_id',
        'feature',
        'feature_label',
        'amount',
        'reference_type',
        'reference_id',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function record(int $userId, string $feature, string $featureLabel, int $amount = 1, ?string $referenceType = null, ?int $referenceId = null, ?string $note = null): self
    {
        return self::create([
            'user_id' => $userId,
            'feature' => $feature,
            'feature_label' => $featureLabel,
            'amount' => $amount,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'note' => $note,
        ]);
    }
}
