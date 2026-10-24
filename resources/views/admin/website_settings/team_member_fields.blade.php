@php($fields=['name'=>'Name','initials'=>'Initials','role'=>'Job Title','badge'=>'Badge','group'=>'Practice Group','bio'=>'Biography','focus'=>'Practice Focus','location'=>'Location','action_text'=>'Button Label','action_link'=>'Button URL','image_id'=>'Media Library Image ID','sort_order'=>'Display Order'])
<div class="row">
@foreach($fields as $field=>$label)<div class="col-lg-6 form-group"><label>{{ $label }}</label>
@if(in_array($field,['bio','focus']))<textarea name="{{ $field }}" class="form-control" rows="3">{{ old($field,$item?->{$field} ?? '') }}</textarea>
@elseif($field==='image_id')<div class="input-group" data-toggle="aizuploader" data-type="image"><div class="input-group-prepend"><span class="input-group-text">Browse</span></div><div class="form-control file-amount">Choose profile image</div><input type="hidden" name="image_id" class="selected-files" value="{{ old('image_id',$item?->image_id) }}"></div><div class="file-preview"></div>
@elseif($field==='group')<select name="group" class="form-control">@foreach(['All','Family','Technology','Trust'] as $value)<option value="{{ $value }}" @selected(old('group',$item?->group ?? 'Family')===$value)>{{ $value }}</option>@endforeach</select>
@else<input class="form-control" name="{{ $field }}" type="{{ in_array($field,['sort_order','image_id'])?'number':'text' }}" @if(in_array($field,['name','role'])) required @endif value="{{ old($field,$item?->{$field} ?? ($field==='sort_order'?0:'')) }}">@endif
</div>@endforeach
<div class="col-12 form-group"><label><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$item?->is_active ?? true))> Visible on website</label></div>
</div>