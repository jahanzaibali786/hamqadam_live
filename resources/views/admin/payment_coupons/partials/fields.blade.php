@php($value = fn ($key, $default = null) => old($key, $coupon?->{$key} ?? $default))
<div class="form-group"><label>{{ translate('Code') }} *</label><input class="form-control text-uppercase" name="code" value="{{ $value('code') }}" required maxlength="40"></div>
<div class="form-row">
    <div class="form-group col-6"><label>{{ translate('Discount Type') }} *</label><select class="form-control aiz-selectpicker" name="discount_type"><option value="percentage" @selected($value('discount_type', 'percentage') === 'percentage')>{{ translate('Percentage') }}</option><option value="fixed" @selected($value('discount_type') === 'fixed')>{{ translate('Fixed Amount') }}</option></select></div>
    <div class="form-group col-6"><label>{{ translate('Discount Value') }} *</label><input class="form-control" type="number" step="0.01" min="0.01" name="discount_value" value="{{ $value('discount_value') }}" required></div>
</div>
<div class="form-row">
    <div class="form-group col-6"><label>{{ translate('Minimum Amount') }}</label><input class="form-control" type="number" step="0.01" min="0" name="minimum_amount" value="{{ $value('minimum_amount', 0) }}"></div>
    <div class="form-group col-6"><label>{{ translate('Total Usage Limit') }}</label><input class="form-control" type="number" min="1" name="usage_limit" value="{{ $value('usage_limit') }}" placeholder="{{ translate('Unlimited') }}"></div>
</div>
<div class="form-row">
    <div class="form-group col-6"><label>{{ translate('Starts At') }}</label><input class="form-control" type="datetime-local" name="starts_at" value="{{ $coupon?->starts_at?->format('Y-m-d\TH:i') }}"></div>
    <div class="form-group col-6"><label>{{ translate('Expires At') }}</label><input class="form-control" type="datetime-local" name="expires_at" value="{{ $coupon?->expires_at?->format('Y-m-d\TH:i') }}"></div>
</div>
<label class="aiz-switch aiz-switch-success mb-3"><input type="checkbox" name="active" value="1" @checked((bool) $value('active', true))><span></span></label> <span class="ml-2">{{ translate('Active') }}</span>
