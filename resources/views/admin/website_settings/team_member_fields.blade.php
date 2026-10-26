@php
 $fields = ['name'=>'Name','initials'=>'Initials','role'=>'Job Title','badge'=>'Badge','group'=>'Practice Group','bio'=>'Biography','focus'=>'Practice Focus','location'=>'Location','action_text'=>'Button Label','action_link'=>'Button URL','image_id'=>'Profile Image','sort_order'=>'Display Order'];
@endphp
<div class="row">
@foreach($fields as $field=>$label)
 <div class="col-lg-6 form-group">
 <label for="team-{{ $field }}">{{ $label }} @if(in_array($field,['name','role','group']))<span class="text-danger">*</span>@endif</label>
 @if(in_array($field,['bio','focus']))
  <textarea id="team-{{ $field }}" name="{{ $field }}" class="form-control" rows="3" placeholder="{{ $label }}">{{ old($field,$item?->{$field} ?? '') }}</textarea>
 @elseif($field==='image_id')
  <div class="input-group" data-toggle="aizuploader" data-type="image"><div class="input-group-prepend"><span class="input-group-text">Browse</span></div><div class="form-control file-amount">Choose profile image</div><input type="hidden" id="team-image_id" name="image_id" class="selected-files" value="{{ old('image_id',$item?->image_id ?? '') }}"></div><div class="file-preview"></div>
 @elseif($field==='group')
  <select id="team-group" name="group" class="form-control" required><option value="" disabled @selected(!old('group',$item?->group ?? ''))>Select practice group</option>@foreach(['All','Family','Technology','Trust'] as $value)<option value="{{ $value }}" @selected(old('group',$item?->group ?? '')===$value)>{{ $value }}</option>@endforeach</select>
 @else
  <input id="team-{{ $field }}" class="form-control @error($field) is-invalid @enderror" name="{{ $field }}" type="{{ $field==='sort_order' ? 'number' : 'text' }}" @if(in_array($field,['name','role'])) required @endif @if($field==='sort_order') min="0" max="99999" @endif value="{{ old($field,$item?->{$field} ?? '') }}" placeholder="{{ $field==='sort_order' ? 'Leave blank to place last' : $label }}">
 @endif
 @error($field)<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
 </div>
@endforeach
<div class="col-12 form-group"><label><input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active',$item?->is_active ?? true))> Visible on website</label></div>
</div>