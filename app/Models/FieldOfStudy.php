<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FieldOfStudy extends Model
{
    use SoftDeletes;

    protected $table = 'fields_of_study';

    protected $fillable = [
        'degree_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function degree()
    {
        return $this->belongsTo(Degree::class);
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