<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SectMain extends Model
{
    use SoftDeletes;

    protected $table = 'sect_main';

    protected $fillable = [
        'religion_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function religion()
    {
        return $this->belongsTo(Religion::class);
    }

    public function schoolsOfThought()
    {
        return $this->hasMany(SchoolOfThought::class);
    }

    public function spiritualBackgrounds()
    {
        return $this->hasMany(SpiritualBackground::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }
}