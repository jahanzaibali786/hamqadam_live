<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SchoolOfThought extends Model
{
    use SoftDeletes;

    protected $table = 'school_of_thought';

    protected $fillable = [
        'sect_main_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function sectMain()
    {
        return $this->belongsTo(SectMain::class);
    }

    public function traditions()
    {
        return $this->hasMany(Tradition::class);
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