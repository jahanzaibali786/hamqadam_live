<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use SoftDeletes;

    protected $table = 'institutions';

    protected $fillable = [
        'country_id',
        'state_id',
        'city_id',
        'name',
        'slug',
        'type',
        'description',
        'website',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
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