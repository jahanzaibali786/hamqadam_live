<?php

namespace App\Models;
use App\Models\User;
use App\Models\SectMain;
use App\Models\SchoolOfThought;
use App\Models\Tradition;
use App\Models\SubCaste;
use App\Models\FamilyValue;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SpiritualBackground extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'religion_id',
        'caste_id',
        'sub_caste_id',
        'family_value_id',
        // New controlled fields
        'sect_main_id',
        'school_of_thought_id',
        'tradition_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function religion()
    {
        return $this->belongsTo(Religion::class)->withTrashed();
    }

    public function caste()
    {
        return $this->belongsTo(Caste::class)->withTrashed();
    }

    public function sub_caste()
    {
        return $this->belongsTo(SubCaste::class)->withTrashed();
    }

    public function family_value()
    {
        return $this->belongsTo(FamilyValue::class)->withTrashed();
    }

    // New relationships
    public function sectMain()
    {
        return $this->belongsTo(SectMain::class);
    }

    public function schoolOfThought()
    {
        return $this->belongsTo(SchoolOfThought::class);
    }

    public function tradition()
    {
        return $this->belongsTo(Tradition::class);
    }

}
