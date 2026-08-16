<form action="{{ route('career.update', $career->id) }}#career" method="POST">
    <input name="_method" type="hidden" value="PATCH">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title h6">{{translate('Edit Career Info')}}</h5>
        <button type="button" class="close" data-dismiss="modal">
        </button>
    </div>
    <div class="modal-body">
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Profession Category')}}</label>
            <div class="col-md-9">
                <select name="profession_category_id" class="form-control aiz-selectpicker" data-live-search="true">
                    <option value="">{{translate('Choose')}}</option>
                    @foreach(\App\Models\ProfessionCategory::where('is_active', true)->orderBy('sort_order')->get() as $category)
                        <option value="{{ $category->id }}" {{ $career->profession_category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Profession')}}</label>
            <div class="col-md-9">
                <select name="profession_id" class="form-control aiz-selectpicker" data-live-search="true">
                    <option value="">{{translate('Choose')}}</option>
                    @if($career->profession_category_id)
                        @foreach(\App\Models\Profession::where('profession_category_id', $career->profession_category_id)->where('is_active', true)->orderBy('sort_order')->get() as $profession)
                            <option value="{{ $profession->id }}" {{ $career->profession_id == $profession->id ? 'selected' : '' }}>{{ $profession->name }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Designation')}}</label>
            <div class="col-md-9">
                <input type="text" name="designation" value="{{$career->designation}}" class="form-control" placeholder="{{translate('designation')}}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Company')}}</label>
            <div class="col-md-9">
                <input type="text" name="company" value="{{$career->company}}"  placeholder="{{ translate('company') }}" class="form-control" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('Start')}}</label>
            <div class="col-md-9">
                <input type="number" name="career_start" value="{{$career->start}}" class="form-control" placeholder="{{translate('Start')}}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-form-label">{{translate('End')}}</label>
            <div class="col-md-9">
                <input type="number" name="career_end" value="{{$career->end}}"  placeholder="{{ translate('End') }}" class="form-control">
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal">{{translate('Close')}}</button>
        <button type="submit" class="btn btn-primary">{{translate('Update Career Info')}}</button>
    </div>
</form>
