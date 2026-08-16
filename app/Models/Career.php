<?php

namespace App\Models;
use App\Models\User;
use App\Models\ProfessionCategory;
use App\Models\Profession;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Career extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    // New relationships
    public function professionCategory()
    {
        return $this->belongsTo(ProfessionCategory::class);
    }

    public function profession()
    {
        return $this->belongsTo(Profession::class);
    }
}
