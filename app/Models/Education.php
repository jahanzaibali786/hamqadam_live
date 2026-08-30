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

    public function getDegreeAttribute($value): string
    {
        $relation = $this->getRelationValue('degree');

        if ($relation instanceof Degree && !empty($relation->name)) {
            return (string) $relation->name;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded) && isset($decoded['name']) && trim((string) $decoded['name']) !== '') {
                return (string) $decoded['name'];
            }

            return $value;
        }

        if (!empty($this->degree_id)) {
            return (string) optional(Degree::find($this->degree_id))->name;
        }

        if (!empty($this->education_level_id)) {
            return (string) optional(EducationLevel::find($this->education_level_id))->name;
        }

        return '';
    }

    public function getInstitutionAttribute($value): string
    {
        $relation = $this->getRelationValue('institution');

        if ($relation instanceof Institution && !empty($relation->name)) {
            return (string) $relation->name;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded) && isset($decoded['name']) && trim((string) $decoded['name']) !== '') {
                return (string) $decoded['name'];
            }

            return $value;
        }

        if (!empty($this->institution_id)) {
            return (string) optional(Institution::find($this->institution_id))->name;
        }

        return '';
    }
}
