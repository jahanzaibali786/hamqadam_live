<form action="{{ route('education.update', $education->id) }}#education" method="POST">
    <input name="_method" type="hidden" value="PATCH">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title h6">{{translate('Edit Education Info')}}</h5>
        <button type="button" class="close" data-dismiss="modal">
        </button>
    </div>
    <div class="modal-body">
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Education Level')}}</label>
            <div class="col-md-9">
                <select name="education_level_id" class="form-control aiz-selectpicker" data-live-search="true">
                    <option value="">{{translate('Choose')}}</option>
                    @foreach(\App\Models\EducationLevel::where('is_active', true)->orderBy('sort_order')->get() as $level)
                        <option value="{{ $level->id }}" {{ $education->education_level_id == $level->id ? 'selected' : '' }}>{{ $level->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Degree')}}</label>
            <div class="col-md-9">
                <select name="degree_id" class="form-control aiz-selectpicker" data-live-search="true">
                    <option value="">{{translate('Choose')}}</option>
                    @if($education->education_level_id)
                        @foreach(\App\Models\Degree::where('education_level_id', $education->education_level_id)->where('is_active', true)->orderBy('sort_order')->get() as $degree)
                            <option value="{{ $degree->id }}" {{ $education->degree_id == $degree->id ? 'selected' : '' }}>{{ $degree->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Field / Major')}}</label>
            <div class="col-md-9">
                <select name="field_of_study_id" class="form-control aiz-selectpicker" data-live-search="true">
                    <option value="">{{translate('Choose')}}</option>
                    @if($education->degree_id)
                        @foreach(\App\Models\FieldOfStudy::where('degree_id', $education->degree_id)->where('is_active', true)->orderBy('sort_order')->get() as $field)
                            <option value="{{ $field->id }}" {{ $education->field_of_study_id == $field->id ? 'selected' : '' }}>{{ $field->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Institution')}}</label>
            <div class="col-md-9">
                <select name="institution_id" class="form-control aiz-selectpicker" data-live-search="true">
                    <option value="">{{translate('Choose')}}</option>
                    @foreach(\App\Models\Institution::where('is_active', true)->orderBy('sort_order')->get() as $institution)
                        <option value="{{ $institution->id }}" {{ $education->institution_id == $institution->id ? 'selected' : '' }}>{{ $institution->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Degree (Legacy)')}}</label>
            <div class="col-md-9">
                <input type="text" name="degree" value="{{$education->degree}}" class="form-control" placeholder="{{translate('Degree')}}">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Institution (Legacy)')}}</label>
            <div class="col-md-9">
                <input type="text" name="institution" value="{{$education->institution}}"  placeholder="{{ translate('Institution') }}" class="form-control">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Start')}}</label>
            <div class="col-md-9">
                <input type="number" name="education_start" value="{{$education->start}}" class="form-control" placeholder="{{translate('Start')}}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('End')}}</label>
            <div class="col-md-9">
                <input type="number" name="education_end" value="{{$education->end}}"  placeholder="{{ translate('End') }}" class="form-control" >
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Graduation Year')}}</label>
            <div class="col-md-9">
                <input type="number" name="graduation_year" value="{{$education->graduation_year}}" class="form-control" placeholder="{{translate('Graduation Year')}}" min="1950" max="2100">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Education Status')}}</label>
            <div class="col-md-9">
                <select name="education_status" class="form-control">
                    <option value="">{{translate('Choose')}}</option>
                    <option value="completed" {{ $education->education_status == 'completed' ? 'selected' : '' }}>{{translate('Completed')}}</option>
                    <option value="in_progress" {{ $education->education_status == 'in_progress' ? 'selected' : '' }}>{{translate('In Progress')}}</option>
                    <option value="dropped" {{ $education->education_status == 'dropped' ? 'selected' : '' }}>{{translate('Dropped')}}</option>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Expected Graduation Year')}}</label>
            <div class="col-md-9">
                <input type="number" name="expected_graduation_year" value="{{$education->expected_graduation_year}}" class="form-control" placeholder="{{translate('Expected Graduation Year')}}" min="1950" max="2100">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Close')}}</button>
        <button type="submit" class="btn btn-primary">{{translate('Update Education Info')}}</button>
    </div>
</form>
