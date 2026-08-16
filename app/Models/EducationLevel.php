<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EducationLevel extends Model
{
    use SoftDeletes;

    protected $table = 'education_levels';

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function degrees()
    {
        return $this->hasMany(Degree::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function education()
    {
        return $this->hasMany(Education::class);
    }
}