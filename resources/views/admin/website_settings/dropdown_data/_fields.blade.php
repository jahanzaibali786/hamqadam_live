@php
    $fieldValue = function ($field) use ($record) {
        $value = old($field['name'], $record->{$field['name']} ?? null);

        if (($field['type'] ?? null) === 'toggle') {
            return (int) $value === 1;
        }

        return $value;
    };
@endphp

@foreach($config['fields'] as $field)
    @php
        $fieldId = $type.'_'.$field['name'];
        $currentValue = $fieldValue($field);
        $options = $field['options'] ?? (isset($field['options_key']) ? ($lookups[$field['options_key']] ?? []) : []);
    @endphp

    <div class="form-group mb-3">
        <label for="{{ $fieldId }}" class="mb-2">
            {{ $field['label'] }}
            @if(!empty($field['required']))
                <span class="text-danger">*</span>
            @endif
        </label>

        @if(($field['type'] ?? 'text') === 'text')
            <input type="text" id="{{ $fieldId }}" name="{{ $field['name'] }}" value="{{ $currentValue }}" class="form-control" @if(!empty($field['required'])) required @endif>
        @elseif(($field['type'] ?? 'text') === 'number')
            <input type="number" id="{{ $fieldId }}" name="{{ $field['name'] }}" value="{{ $currentValue }}" class="form-control" @if(!empty($field['required'])) required @endif>
        @elseif(($field['type'] ?? 'text') === 'textarea')
            <textarea id="{{ $fieldId }}" name="{{ $field['name'] }}" class="form-control" rows="3" @if(!empty($field['required'])) required @endif>{{ $currentValue }}</textarea>
        @elseif(($field['type'] ?? 'text') === 'select')
            <select id="{{ $fieldId }}" name="{{ $field['name'] }}" class="form-control aiz-selectpicker" data-live-search="true" @if(!empty($field['required'])) required @endif>
                <option value="">{{ translate('Choose') }}</option>
                @foreach($options as $optionKey => $optionValue)
                    @php
                        $optionId = is_object($optionValue) ? $optionValue->id : $optionKey;
                        $optionLabel = is_object($optionValue) ? ($optionValue->name ?? $optionValue->title ?? $optionValue->label ?? $optionId) : $optionValue;
                    @endphp
                    <option value="{{ $optionId }}" @selected((string) $currentValue === (string) $optionId)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif(($field['type'] ?? 'text') === 'toggle')
            <div class="d-flex align-items-center">
                <input type="hidden" name="{{ $field['name'] }}" value="0">
                <label class="aiz-switch aiz-switch-success mb-0">
                    <input type="checkbox" name="{{ $field['name'] }}" value="1" @checked((bool) $currentValue)>
                    <span></span>
                </label>
                <small class="ml-2 text-muted">{{ $field['label'] }}</small>
            </div>
        @else
            <input type="text" id="{{ $fieldId }}" name="{{ $field['name'] }}" value="{{ $currentValue }}" class="form-control" @if(!empty($field['required'])) required @endif>
        @endif

        @error($field['name'])
            <small class="form-text text-danger">{{ $message }}</small>
        @enderror
    </div>
@endforeach
