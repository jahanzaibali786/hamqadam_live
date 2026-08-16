<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProfessionCategory extends Model
{
    use SoftDeletes;

    protected $table = 'profession_categories';

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

    public function professions()
    {
        return $this->hasMany(Profession::class);
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