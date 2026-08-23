<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Degree;
use App\Models\Education;
use App\Models\EducationLevel;
use App\Models\Institution;
use Validator;
use Redirect;

class EducationController extends Controller
{
    public function __construct()
    {
        $this->rules = [
            'education_level_id' => ['required', 'integer', 'exists:education_levels,id'],
            'degree_id' => ['required', 'integer', 'exists:degrees,id'],
            'institution_id' => ['required', 'integer', 'exists:institutions,id'],
            'education_start' => ['required', 'numeric'],
            'education_end' => ['nullable', 'numeric'],
        ];
    }

    public function index()
    {
        //
    }

    public function create(Request $request)
    {
        $member_id = $request->id;
        $education_levels = EducationLevel::where('is_active', true)->orderBy('sort_order')->get();
        $degrees = Degree::where('is_active', true)->orderBy('sort_order')->get();
        $institutions = Institution::where('is_active', true)->orderBy('sort_order')->get();

        return view('frontend.member.profile.education.create', compact('member_id', 'education_levels', 'degrees', 'institutions'));
    }

    public function store(Request $request)
    {
        $rules = $this->rules;
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            flash(translate('Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        $education = new Education;
        $education->user_id = $request->user_id;
        $education->education_level_id = $request->education_level_id;
        $education->degree_id = $request->degree_id;
        $education->institution_id = $request->institution_id;
        $education->degree = optional(Degree::find($request->degree_id))->name;
        $education->institution = optional(Institution::find($request->institution_id))->name;
        $education->start = $request->education_start;
        $education->end = $request->education_end;

        if($education->save()){
            flash(translate('Education Info has been added successfully'))->success();
            return back();
        }

        flash(translate('Sorry! Something went wrong.'))->error();
        return back();
    }

    public function show($id)
    {
        //
    }

    public function edit(Request $request)
    {
        $education = Education::findOrFail($request->id);
        $education_levels = EducationLevel::where('is_active', true)->orderBy('sort_order')->get();
        $degrees = Degree::where('is_active', true)->orderBy('sort_order')->get();
        $institutions = Institution::where('is_active', true)->orderBy('sort_order')->get();

        return view('frontend.member.profile.education.edit', compact('education', 'education_levels', 'degrees', 'institutions'));
    }

    public function update(Request $request, $id)
    {
        $rules = $this->rules;
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            flash(translate('Something went wrong'))->error();
            return Redirect::back()->withErrors($validator);
        }

        $education = Education::findOrFail($id);
        $education->education_level_id = $request->education_level_id;
        $education->degree_id = $request->degree_id;
        $education->institution_id = $request->institution_id;
        $education->degree = optional(Degree::find($request->degree_id))->name;
        $education->institution = optional(Institution::find($request->institution_id))->name;
        $education->start = $request->education_start;
        $education->end = $request->education_end;

        if($education->save()){
            flash(translate('Education Info has been updated successfully'))->success();
            return back();
        }

        flash(translate('Sorry! Something went wrong.'))->error();
        return back();
    }

    public function update_education_present_status(Request $request)
    {
        $education = Education::findOrFail($request->id);
        $education->present = $request->status;
        if ($education->save()) {
            $msg = $education->present == 1 ? translate('Enabled') : translate('Disabled');
            flash(translate($msg))->success();
            return 1;
        }
        return 0;
    }

    public function updateHighestDegree(Request $request){
        $education = Education::findOrFail($request->id);
        $education->is_highest_degree = $request->status;
        if ($education->save()) {
            if(Education::where('is_highest_degree', 1)->count() > 1){
                Education::where('id','!=', $education->id)->update(['is_highest_degree' => 0]);
            }
            return 1;
        }
        return 0;
    }

    public function destroy($id)
    {
        if(Education::destroy($id))
        {
            flash(translate('Education info has been deleted successfully'))->success();
            return back();
        }
        else {
            flash(translate('Sorry! Something went wrong.'))->error();
            return back();
        }
    }
}