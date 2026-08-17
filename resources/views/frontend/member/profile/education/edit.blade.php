<form action="{{ route('education.update', $education->id) }}#education" method="POST">
    <input name="_method" type="hidden" value="PATCH">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title h6">{{translate('Edit Education Info')}}</h5>
        <button type="button" class="close" data-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Education Level')}}</label>
            <div class="col-md-9">
                <select name="education_level_id" class="form-control aiz-selectpicker" data-live-search="true">
                    <option value="">{{translate('Choose')}}</option>
                    @foreach(($education_levels ?? []) as $level)
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
                    @foreach(($degrees ?? []) as $degree)
                        <option value="{{ $degree->id }}" {{ $education->degree_id == $degree->id ? 'selected' : '' }}>{{ $degree->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Institution')}}</label>
            <div class="col-md-9">
                <select name="institution_id" class="form-control aiz-selectpicker" data-live-search="true">
                    <option value="">{{translate('Choose')}}</option>
                    @foreach(($institutions ?? []) as $institution)
                        <option value="{{ $institution->id }}" {{ $education->institution_id == $institution->id ? 'selected' : '' }}>{{ $institution->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Start')}}</label>
            <div class="col-md-9">
                <input type="number" name="education_start" value="{{ $education->start }}" class="form-control" placeholder="{{translate('Start')}}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('End')}}</label>
            <div class="col-md-9">
                <input type="number" name="education_end" value="{{ $education->end }}" class="form-control" placeholder="{{ translate('End') }}">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Graduation Year')}}</label>
            <div class="col-md-9">
                <input type="number" name="graduation_year" value="{{ $education->graduation_year }}" class="form-control" placeholder="{{translate('Graduation Year')}}" min="1950" max="2100">
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
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Close')}}</button>
        <button type="submit" class="btn btn-primary">{{translate('Update Education Info')}}</button>
    </div>
</form>
