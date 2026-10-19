<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class CityGeoCoordinate extends Model
{
    protected $fillable = ['city_id', 'latitude', 'longitude', 'updated_by'];
    protected $casts = ['latitude'=>'float','longitude'=>'float'];
    public function city() { return $this->belongsTo(City::class); }
}
