<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tradition extends Model
{
    use SoftDeletes;

    protected $table = 'traditions';

    protected $fillable = [
        'school_of_thought_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function schoolOfThought()
    {
        return $this->belongsTo(SchoolOfThought::class);
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