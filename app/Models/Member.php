<?php

namespace App\Models;
use App\Models\User;
use App\Models\ProfessionCategory;
use App\Models\Profession;
use App\Models\EducationLevel;
use App\Models\Degree;
use App\Models\FieldOfStudy;
use App\Models\Institution;
use App\Models\SectMain;
use App\Models\SchoolOfThought;
use App\Models\Tradition;
use App\Models\AnnualSalaryRange;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Member extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'user_id','gender','birthday','on_behalves_id','current_package_id','remaining_interest','remaining_contact_view','remaining_photo_gallery','auto_profile_match','auto_horoscope_profile_match','package_validity',
        'introduction','video_introduction','voice_introduction','ai_generated_bio','travel_preferences','future_goals','profile_completion_percentage','hide_profile','verification_status','marital_status_id','children','annual_salary_range_id','mothere_tongue','known_languages',
        'looking_for','life_values','personality_type','communication_style','love_language','conflict_resolution_style','disability',
        'religious_practice_level','prayer_frequency','community_biradari','hijab_beard_preference',
        'education_level','employment_status','work_location_city','annual_income',
        'father_occupation','mother_occupation','family_type','siblings_brothers','siblings_sisters','married_siblings','family_location',
        'guardian_name','guardian_contact','family_values','family_bio','family_expectations','parents_contact',
        'children_preference','relocation_preference','visa_immigration_status','future_living_preference','financial_responsibility',
        'marriage_timeline','expectations_after_marriage','willing_to_work_after_marriage','expects_spouse_to_work',
        'hobbies','interests_multi_select','health_conditions','languages_spoken_fluently','favorite_weekend_activities',
        'proposal_preferences','communication_preferences','cover_photo','private_gallery',
        // New controlled fields
        'profession_category_id','profession_id','job_title','organization','years_of_experience',
        'education_level_id','degree_id','field_of_study_id','institution_id','graduation_year','education_status','expected_graduation_year',
        'sect_main_id','school_of_thought_id','tradition_id',
    ];

    protected $casts = [
        'hide_profile' => 'boolean',
        'profile_completion_percentage' => 'integer',
        'life_values' => 'array',
        'love_language' => 'array',
        'expectations_after_marriage' => 'array',
        'interests_multi_select' => 'array',
        'languages_spoken_fluently' => 'array',
        'communication_preferences' => 'array',
        'private_gallery' => 'array',
        'willing_to_work_after_marriage' => 'boolean',
        'expects_spouse_to_work' => 'boolean',
        // AI identity verification. The datetime casts matter: the member
        // dashboard calls ->diffForHumans() on ai_verification_last_attempt_at,
        // which is a fatal error on a raw string.
        'ai_verification_attempts' => 'integer',
        'ai_verification_last_attempt_at' => 'datetime',
        'ai_verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function on_behalves(){
        return $this->belongsTo(OnBehalf::class)->withTrashed();
    }

    public function marital_status(){
        return $this->belongsTo(MaritalStatus::class)->withTrashed();
    }

    public function package()
    {
        return $this->belongsTo(Package::class,'current_package_id')->withTrashed();
    }

    public function mothereTongue()
    {
        return $this->belongsTo(MemberLanguage::class,'mothere_tongue')->withTrashed();
    }

    public function annualSalaryRange()
    {
        return $this->belongsTo(AnnualSalaryRange::class);
    }

    // New relationships for profession system
    public function professionCategory()
    {
        return $this->belongsTo(ProfessionCategory::class);
    }

    public function profession()
    {
        return $this->belongsTo(Profession::class);
    }

    // New relationships for education system
    public function educationLevel()
    {
        return $this->belongsTo(EducationLevel::class);
    }

    public function degree()
    {
        return $this->belongsTo(Degree::class);
    }

    public function fieldOfStudy()
    {
        return $this->belongsTo(FieldOfStudy::class);
    }

    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    // New relationships for religion/sect system
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
