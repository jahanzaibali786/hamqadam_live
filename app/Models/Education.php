<?php

namespace App\Models;
use App\Models\User;
use App\Models\EducationLevel;
use App\Models\Degree;
use App\Models\FieldOfStudy;
use App\Models\Institution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A member's education row.
 *
 * SCHEMA NOTE (the 500 fix): the `education` table has NO `degree` /
 * `institution` columns — the free-text history lives in `degree_legacy` /
 * `institution_legacy`, and the canonical value is `degree_id` /
 * `institution_id`. The accessors below resolve the display name from those,
 * and the mutators write free-text input to the legacy columns so
 * `Education::updateOrCreate(['degree' => ..., 'institution' => ...])` from
 * the profile-update flow cannot trip "Unknown column" anymore.
 */
class Education extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'degree',
        'institution',
        'degree_legacy',
        'institution_legacy',
        'start',
        'end',
        'present',
        'is_highest_degree',
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

        // Free-text history first (where old writes landed), then the row.
        $legacy = $this->attributes['degree_legacy'] ?? null;
        if (is_string($legacy) && trim($legacy) !== '') {
            $decoded = json_decode($legacy, true);

            if (is_array($decoded) && isset($decoded['name']) && trim((string) $decoded['name']) !== '') {
                return (string) $decoded['name'];
            }

            return $legacy;
        }

        if (!empty($this->degree_id)) {
            return (string) optional(Degree::find($this->degree_id))->name;
        }

        if (!empty($this->education_level_id)) {
            return (string) optional(EducationLevel::find($this->education_level_id))->name;
        }

        return '';
    }

    /** Free-text degree input lands in degree_legacy (the real column). */
    public function setDegreeAttribute($value): void
    {
        $this->attributes['degree_legacy'] = is_string($value) ? trim($value) : $value;
    }

    public function getInstitutionAttribute($value): string
    {
        $relation = $this->getRelationValue('institution');

        if ($relation instanceof Institution && !empty($relation->name)) {
            return (string) $relation->name;
        }

        $legacy = $this->attributes['institution_legacy'] ?? null;
        if (is_string($legacy) && trim($legacy) !== '') {
            $decoded = json_decode($legacy, true);

            if (is_array($decoded) && isset($decoded['name']) && trim((string) $decoded['name']) !== '') {
                return (string) $decoded['name'];
            }

            return $legacy;
        }

        if (!empty($this->institution_id)) {
            return (string) optional(Institution::find($this->institution_id))->name;
        }

        return '';
    }

    /** Free-text institution input lands in institution_legacy (the real column). */
    public function setInstitutionAttribute($value): void
    {
        $this->attributes['institution_legacy'] = is_string($value) ? trim($value) : $value;
    }
}
