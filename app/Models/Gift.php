<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gift extends Model
{
    /** Animation formats the admin may upload, keyed by file extension. */
    public const MEDIA_TYPES = [
        'json' => 'lottie',
        'gif' => 'gif',
        'svga' => 'svga',
        'mp4' => 'mp4',
    ];

    protected $fillable = [
        'name',
        'slug',
        'thumbnail',
        'animated_asset',
        'media_type',
        'coins',
        'category',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'coins' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(GiftTransaction::class);
    }

    /** lottie | gif | svga | mp4 — stored value first, else from the extension. */
    public function resolvedMediaType(): string
    {
        if ($this->media_type) {
            return $this->media_type;
        }
        $ext = strtolower(pathinfo((string) $this->animated_asset, PATHINFO_EXTENSION));

        return self::MEDIA_TYPES[$ext] ?? 'lottie';
    }

    /**
     * Seeded gifts point at files bundled in the app ("assets/..."), which
     * have no URL; uploaded ones live under public/ and get an absolute URL.
     */
    public static function publicUrl(?string $path): ?string
    {
        if (! $path || str_starts_with($path, 'assets/')) {
            return null;
        }
        if (preg_match('#^https?://#', $path)) {
            return $path;
        }

        return static_asset($path);
    }
}
