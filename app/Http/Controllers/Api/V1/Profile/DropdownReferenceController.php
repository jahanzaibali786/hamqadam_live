<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\Profile\CasteResource;
use App\Http\Resources\Profile\CityResource;
use App\Http\Resources\Profile\CountryResource;
use App\Http\Resources\Profile\FamilyValuesResource;
use App\Http\Resources\Profile\LanguageResource;
use App\Http\Resources\Profile\MaritialStatusResource;
use App\Http\Resources\Profile\OnBehalfResource;
use App\Http\Resources\Profile\ReligionResource;
use App\Http\Resources\Profile\StateResource;
use App\Http\Resources\Profile\SubCasteResource;
use App\Models\Caste;
use App\Models\City;
use App\Models\Country;
use App\Models\Degree;
use App\Models\EducationLevel;
use App\Models\FamilyValue;
use App\Models\FieldOfStudy;
use App\Models\Institution;
use App\Models\Language;
use App\Models\MaritalStatus;
use App\Models\MemberLanguage;
use App\Models\OnBehalf;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use App\Models\Religion;
use App\Models\SchoolOfThought;
use App\Models\SectMain;
use App\Models\State;
use App\Models\SubCaste;
use App\Models\Tradition;
use Illuminate\Http\JsonResponse;

class DropdownReferenceController extends ApiController
{
    public function index(): JsonResponse
    {
        $data = [
            'countries' => CountryResource::collection(Country::where('status', 1)->get()),
            'states' => StateResource::collection(State::all()),
            'cities' => CityResource::collection(City::all()),
            'areas' => [],

            'religions' => ReligionResource::collection(Religion::all()),
            'languages' => LanguageResource::collection(MemberLanguage::all()),
            'castes' => CasteResource::collection(Caste::all()),
            'sub_castes' => SubCasteResource::collection(SubCaste::all()),
            'sect_main' => SectMain::where('is_active', true)->orderBy('sort_order')->get(),
            'school_of_thought' => SchoolOfThought::where('is_active', true)->orderBy('sort_order')->get(),
            'traditions' => Tradition::where('is_active', true)->orderBy('sort_order')->get(),

            'marital_statuses' => MaritialStatusResource::collection(MaritalStatus::all()),
            'on_behalves' => OnBehalfResource::collection(OnBehalf::all()),
            'family_values' => FamilyValuesResource::collection(FamilyValue::all()),
            'hobbies' => [
                ['id' => 'Reading', 'name' => 'Reading'],
                ['id' => 'Traveling', 'name' => 'Traveling'],
                ['id' => 'Cooking', 'name' => 'Cooking'],
                ['id' => 'Gardening', 'name' => 'Gardening'],
                ['id' => 'Painting', 'name' => 'Painting'],
                ['id' => 'Dancing', 'name' => 'Dancing'],
                ['id' => 'Gaming', 'name' => 'Gaming'],
                ['id' => 'Yoga', 'name' => 'Yoga'],
                ['id' => 'Writing', 'name' => 'Writing'],
                ['id' => 'Hiking', 'name' => 'Hiking'],
                ['id' => 'Volunteering', 'name' => 'Volunteering'],
            ],

            'education_levels' => EducationLevel::where('is_active', true)->orderBy('sort_order')->get(),
            'degrees' => Degree::where('is_active', true)->orderBy('sort_order')->get(),
            'fields_of_study' => FieldOfStudy::where('is_active', true)->orderBy('sort_order')->get(),
            'institutions' => Institution::where('is_active', true)->orderBy('sort_order')->get(),

            'profession_categories' => ProfessionCategory::where('is_active', true)->orderBy('sort_order')->get(),
            'professions' => Profession::where('is_active', true)->orderBy('sort_order')->get(),

            'gender' => [
                ['id' => 1, 'name' => 'Male'],
                ['id' => 2, 'name' => 'Female'],
            ],
            'marriage_timeline' => [
                ['id' => 'immediate', 'name' => 'Immediate'],
                ['id' => 'within_3_months', 'name' => 'Within 3 Months'],
                ['id' => 'within_6_months', 'name' => 'Within 6 Months'],
                ['id' => 'within_1_year', 'name' => '1 Year'],
            ],
            'willing_to_work_after_marriage' => [
                ['id' => 'yes', 'name' => 'Yes'],
                ['id' => 'no', 'name' => 'No'],
                ['id' => 'depends_on_mutual_understanding', 'name' => 'Depends on Mutual Understanding'],
            ],
            'expects_spouse_to_work' => [
                ['id' => 'yes', 'name' => 'Yes'],
                ['id' => 'no', 'name' => 'No'],
                ['id' => 'depends_on_mutual_understanding', 'name' => 'Depends on Mutual Understanding'],
            ],
            'diet' => [
                ['id' => 'Vegetarian', 'name' => 'Vegetarian'],
                ['id' => 'Non-Vegetarian', 'name' => 'Non-Vegetarian'],
            ],
            'employment_status' => [
                ['id' => 'government', 'name' => 'Government'],
                ['id' => 'private', 'name' => 'Private'],
                ['id' => 'civil', 'name' => 'Civil'],
                ['id' => 'defence', 'name' => 'Defence'],
                ['id' => 'self_employed', 'name' => 'Self-Employed'],
                ['id' => 'unemployed', 'name' => 'Unemployed'],
                ['id' => 'retired', 'name' => 'Retired'],
            ],
            'education_status' => [
                ['id' => 'completed', 'name' => 'Completed'],
                ['id' => 'in_progress', 'name' => 'In Progress'],
                ['id' => 'dropped', 'name' => 'Dropped'],
            ],
            'live_with_family' => [
                ['id' => 'yes', 'name' => 'Yes'],
                ['id' => 'no', 'name' => 'No'],
            ],
        ];

        return $this->success($data);
    }
}
