<form action="{{ route('education.store') }}#education" method="POST">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title h6">{{translate('Add New Education Info')}}</h5>
        <button type="button" class="close" data-dismiss="modal"></button>
    </div>
    <div class="modal-body">
        <input type="hidden" name="user_id" value="{{ $member_id }}">
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Education Level')}}</label>
            <div class="col-md-9">
                <select name="education_level_id" class="form-control aiz-selectpicker" data-live-search="true">
                    <option value="">{{translate('Choose')}}</option>
                    @foreach(($education_levels ?? []) as $level)
                        <option value="{{ $level->id }}">{{ $level->name }}</option>
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
                        <option value="{{ $degree->id }}">{{ $degree->name }}</option>
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
                        <option value="{{ $institution->id }}">{{ $institution->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Start')}}</label>
            <div class="col-md-9">
                <input type="number" name="education_start" class="form-control" placeholder="{{translate('Start')}}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('End')}}</label>
            <div class="col-md-9">
                <input type="number" name="education_end" class="form-control" placeholder="{{ translate('End') }}">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Close')}}</button>
        <button type="submit" class="btn btn-primary">{{translate('Add New Education Info')}}</button>
    </div>
</form>
