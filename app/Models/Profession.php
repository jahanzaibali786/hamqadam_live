<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profession extends Model
{
    use SoftDeletes;

    protected $table = 'professions';

    protected $fillable = [
        'profession_category_id',
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function professionCategory()
    {
        return $this->belongsTo(ProfessionCategory::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }

    public function careers()
    {
        return $this->hasMany(Career::class);
    }
}