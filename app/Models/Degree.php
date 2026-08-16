<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Degree extends Model
{
    use SoftDeletes;

    protected $table = 'degrees';

    protected $fillable = [
        'education_level_id',
        'name',
        'slug',
        'category',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function fieldsOfStudy()
    {
        return $this->hasMany(FieldOfStudy::class);
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