<?php

namespace App\Models;
use App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PartnerExpectation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'general',
        'preferred_age_min',
        'preferred_age_max',
        'height',
        'height_min',
        'height_max',
        'weight',
        'marital_status_id',
        'children_acceptable',
        'children_preference',
        'residence_country_id',
        'religion_id',
        'caste_id',
        'sub_caste_id',
        'education',
        'profession',
        'income_min',
        'income_max',
        'smoking_acceptable',
        'drinking_acceptable',
        'diet',
        'lifestyle',
        'prayer',
        'religious_practice',
        'body_type',
        'personal_value',
        'manglik',
        'language_id',
        'preferred_language_ids',
        'family_value_id',
        'preferred_country_id',
        'preferred_state_id',
        'preferred_city_id',
        'complexion',
        'deal_breakers',
    ];

    protected $casts = [
        'preferred_language_ids' => 'array',
        'deal_breakers' => 'array',
        'preferred_age_min' => 'integer',
        'preferred_age_max' => 'integer',
        'income_min' => 'decimal:2',
        'income_max' => 'decimal:2',
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
    public function member_language()
    {
        return $this->belongsTo(MemberLanguage::class, 'language_id')->withTrashed();
    }
    public function marital_status()
    {
        return $this->belongsTo(MaritalStatus::class)->withTrashed();
    }

}
