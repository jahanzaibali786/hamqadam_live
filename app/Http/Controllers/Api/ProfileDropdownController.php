<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Controller;
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
use App\Models\FamilyValue;
use App\Models\Language;
use App\Models\MaritalStatus;
use App\Models\MemberLanguage;
use App\Models\OnBehalf;
use App\Models\Religion;
use App\Models\State;
use App\Models\SubCaste;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;

class ProfileDropdownController extends Controller
{
    public function profile_dropdown(){
        $data['onbehalf_list'] = OnBehalfResource::collection(OnBehalf::latest()->get());
        $data['maritial_status'] = MaritialStatusResource::collection(MaritalStatus::latest()->get());
        $data['language_list'] = LanguageResource::collection(MemberLanguage::all());
        $data['religion_list'] = ReligionResource::collection(Religion::all());
        $data['family_value_list'] = FamilyValuesResource::collection(FamilyValue::all());
        $data['country_list'] = CountryResource::collection(Country::where('status',1)->get());
        $data['profession_categories'] = \App\Models\ProfessionCategory::where('is_active', true)->orderBy('sort_order')->get();
        $data['education_levels'] = \App\Models\EducationLevel::where('is_active', true)->orderBy('sort_order')->get();
        $data['horoscope_dropdowns'] = [
            'sun_signs' => [
                ['value' => 'aries', 'label' => 'Aries (Mar 21 – Apr 19)'],
                ['value' => 'taurus', 'label' => 'Taurus (Apr 20 – May 20)'],
                ['value' => 'gemini', 'label' => 'Gemini (May 21 – Jun 20)'],
                ['value' => 'cancer', 'label' => 'Cancer (Jun 21 – Jul 22)'],
                ['value' => 'leo', 'label' => 'Leo (Jul 23 – Aug 22)'],
                ['value' => 'virgo', 'label' => 'Virgo (Aug 23 – Sep 22)'],
                ['value' => 'libra', 'label' => 'Libra (Sep 23 – Oct 22)'],
                ['value' => 'scorpio', 'label' => 'Scorpio (Oct 23 – Nov 21)'],
                ['value' => 'sagittarius', 'label' => 'Sagittarius (Nov 22 – Dec 21)'],
                ['value' => 'capricorn', 'label' => 'Capricorn (Dec 22 – Jan 19)'],
                ['value' => 'aquarius', 'label' => 'Aquarius (Jan 20 – Feb 18)'],
                ['value' => 'pisces', 'label' => 'Pisces (Feb 19 – Mar 20)'],
            ],
            'moon_signs' => [
                ['value' => 'aries', 'label' => 'Aries (Mesha)'],
                ['value' => 'taurus', 'label' => 'Taurus (Vrishabha)'],
                ['value' => 'gemini', 'label' => 'Gemini (Mithuna)'],
                ['value' => 'cancer', 'label' => 'Cancer (Karka)'],
                ['value' => 'leo', 'label' => 'Leo (Simha)'],
                ['value' => 'virgo', 'label' => 'Virgo (Kanya)'],
                ['value' => 'libra', 'label' => 'Libra (Tula)'],
                ['value' => 'scorpio', 'label' => 'Scorpio (Vrishchika)'],
                ['value' => 'sagittarius', 'label' => 'Sagittarius (Dhanu)'],
                ['value' => 'capricorn', 'label' => 'Capricorn (Makara)'],
                ['value' => 'aquarius', 'label' => 'Aquarius (Kumbha)'],
                ['value' => 'pisces', 'label' => 'Pisces (Meena)'],
            ],
            'nakshatras' => ['anuradha','ardra','ashlesha','ashwini','bharani','chitra','dhanishta','hasta','jyeshtha','krittika','magha','mrigashira','mula','punarvasu','purva_ashadha','purva_bhadrapada','purva_phalguni','pushya','revati','rohini','shatabhisha','shravana','swati','uttara_ashadha','uttara_bhadrapada','uttara_phalguni','vishakha'],
            'gana' => [['value' => 'deva', 'label' => 'Deva'], ['value' => 'manushya', 'label' => 'Manushya'], ['value' => 'rakshasa', 'label' => 'Rakshasa']],
            'nadi' => [['value' => 'aadi', 'label' => 'Aadi'], ['value' => 'antya', 'label' => 'Antya'], ['value' => 'madhya', 'label' => 'Madhya']],
            'manglik' => [['value' => 'yes', 'label' => 'Yes'], ['value' => 'no', 'label' => 'No']],
        ];
        return $this->response_data($data);
    }

    public function onbehalf_list(){
        return OnBehalfResource::collection(OnBehalf::latest()->get());
    }

    public function maritial_status(){
        return MaritialStatusResource::collection(MaritalStatus::latest()->get());
    }
    public function country_list(){
        return CountryResource::collection(Country::where('status',1)->get());
    }
    public function state_list($id){
        return StateResource::collection(State::where('country_id',$id)->get());
    }
    public function city_list($id){
        return CityResource::collection(City::where('state_id',$id)->get());
    }
    public function language_list(){
        return LanguageResource::collection(MemberLanguage::all());
    }
    public function religion_list(){
        return ReligionResource::collection(Religion::all());
    }
    public function caste_list($id){
        return CasteResource::collection(Caste::where('religion_id',$id)->get());
    }
    public function sub_caste_list($id){
        return SubCasteResource::collection(SubCaste::where('caste_id',$id)->get());
    }
    public function family_value_list(){
        return FamilyValuesResource::collection(FamilyValue::all());
    }
    
    // New methods for controlled dropdowns
    public function profession_categories(){
        return \App\Models\ProfessionCategory::where('is_active', true)->orderBy('sort_order')->get();
    }
    
    public function professions_by_category($id){
        return \App\Models\Profession::where('profession_category_id', $id)->where('is_active', true)->orderBy('sort_order')->get();
    }
    
    public function education_levels(){
        return \App\Models\EducationLevel::where('is_active', true)->orderBy('sort_order')->get();
    }
    
    public function degrees_by_education_level($id){
        return \App\Models\Degree::where('education_level_id', $id)->where('is_active', true)->orderBy('sort_order')->get();
    }
    
    public function fields_of_study_by_degree($id){
        return \App\Models\FieldOfStudy::where('degree_id', $id)->where('is_active', true)->orderBy('sort_order')->get();
    }
    
    public function institutions_by_location(Request $request){
        $query = \App\Models\Institution::where('is_active', true);
        
        if ($request->country_id) {
            $query->where('country_id', $request->country_id);
        }
        if ($request->state_id) {
            $query->where('state_id', $request->state_id);
        }
        if ($request->city_id) {
            $query->where('city_id', $request->city_id);
        }
        if ($request->type) {
            $query->where('type', $request->type);
        }
        
        return $query->orderBy('sort_order')->get();
    }
    
    public function sect_main_by_religion($id){
        return \App\Models\SectMain::where('religion_id', $id)->where('is_active', true)->orderBy('sort_order')->get();
    }
    
    public function school_of_thought_by_sect($id){
        return \App\Models\SchoolOfThought::where('sect_main_id', $id)->where('is_active', true)->orderBy('sort_order')->get();
    }
    
    public function traditions_by_school_of_thought($id){
        return \App\Models\Tradition::where('school_of_thought_id', $id)->where('is_active', true)->orderBy('sort_order')->get();
    }
}