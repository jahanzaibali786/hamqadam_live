@php
    $filterAction = $filterAction ?? url()->current();
@endphp

<form method="GET" action="{{ $filterAction }}" class="border rounded p-3 mb-4 bg-light">
    @if(request()->has('type'))
        <input type="hidden" name="type" value="{{ request('type') }}">
    @endif
    @if(request()->has('search'))
        <input type="hidden" name="search" value="{{ request('search') }}">
    @endif
    @if(request()->has('status'))
        <input type="hidden" name="status" value="{{ request('status') }}">
    @endif

    <div class="row gutters-5 align-items-end">
        <div class="col-md-2 mb-2">
            <label class="mb-1">{{ translate('Member ID / Code') }}</label>
            <input type="text" name="member_id" class="form-control form-control-sm"
                   value="{{ request('member_id') }}" placeholder="{{ translate('ID or code') }}">
        </div>
        <div class="col-md-2 mb-2">
            <label class="mb-1">{{ translate('Gender') }}</label>
            <select name="gender" class="form-control form-control-sm aiz-selectpicker" data-live-search="true">
                <option value="">{{ translate('All genders') }}</option>
                <option value="1" @selected(request('gender') === '1')>{{ translate('Male') }}</option>
                <option value="2" @selected(request('gender') === '2')>{{ translate('Female') }}</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label class="mb-1">{{ translate('Account Status') }}</label>
            <select name="account_status" class="form-control form-control-sm aiz-selectpicker">
                <option value="">{{ translate('All statuses') }}</option>
                <option value="active" @selected(request('account_status') === 'active')>{{ translate('Active') }}</option>
                <option value="blocked" @selected(request('account_status') === 'blocked')>{{ translate('Blocked') }}</option>
                <option value="deactivated" @selected(request('account_status') === 'deactivated')>{{ translate('Deactivated') }}</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label class="mb-1">{{ translate('Approval') }}</label>
            <select name="approval_status" class="form-control form-control-sm aiz-selectpicker">
                <option value="">{{ translate('All approval states') }}</option>
                <option value="1" @selected(request('approval_status') === '1')>{{ translate('Approved') }}</option>
                <option value="0" @selected(request('approval_status') === '0')>{{ translate('Pending') }}</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label class="mb-1">{{ translate('Verification') }}</label>
            <select name="verification_status" class="form-control form-control-sm aiz-selectpicker" data-live-search="true">
                <option value="">{{ translate('All verification states') }}</option>
                @foreach($filterVerificationStatuses ?? [] as $value => $label)
                    <option value="{{ $value }}" @selected(request('verification_status') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label class="mb-1">{{ translate('Package') }}</label>
            <select name="package_id" class="form-control form-control-sm aiz-selectpicker" data-live-search="true">
                <option value="">{{ translate('All packages') }}</option>
                @foreach($filterPackages ?? [] as $package)
                    <option value="{{ $package->id }}" @selected((string) request('package_id') === (string) $package->id)>{{ $package->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label class="mb-1">{{ translate('Country') }}</label>
            <select name="country_id" class="form-control form-control-sm aiz-selectpicker" data-live-search="true">
                <option value="">{{ translate('All countries') }}</option>
                @foreach($filterCountries ?? [] as $country)
                    <option value="{{ $country->id }}" @selected((string) request('country_id') === (string) $country->id)>{{ $country->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label class="mb-1">{{ translate('Photo Approval') }}</label>
            <select name="photo_status" class="form-control form-control-sm aiz-selectpicker">
                <option value="">{{ translate('All photo states') }}</option>
                <option value="1" @selected(request('photo_status') === '1')>{{ translate('Approved') }}</option>
                <option value="0" @selected(request('photo_status') === '0')>{{ translate('Pending') }}</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <button type="submit" class="btn btn-primary btn-sm mr-1">{{ translate('Filter') }}</button>
            <a href="{{ $filterAction }}" class="btn btn-light btn-sm">{{ translate('Reset') }}</a>
        </div>
    </div>
</form>
