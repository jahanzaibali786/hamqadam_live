<?php

namespace App\Models;
use App\Models\User;
use App\Models\EducationLevel;
use App\Models\Degree;
use App\Models\FieldOfStudy;
use App\Models\Institution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Education extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'degree',
        'institution',
        'start',
        'end',
        // New controlled fields
        'education_level_id',
        'degree_id',
        'field_of_study_id',
        'institution_id',
        'graduation_year',
        'education_status',
        'expected_graduation_year',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    // New relationships
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
}
