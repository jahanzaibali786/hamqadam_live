@extends('admin.layouts.app')

@section('content')

    <div class="card">
        <div class="card-header">
            <h5 class="mb-md-0 h6">{{translate('Update Role Information')}}</h5>
        </div>

        <form class="form-horizontal" action="{{ route('roles.update', $role->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="_method" value="PATCH">
            <div class="card-body">
                <div class="row">
                    <div class="col-8 mx-auto">
                        <div class="form-group row">
                            <label class="col-md-2 col-form-label">{{translate('Role Name')}}<span class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input type="text" placeholder="{{translate('Name')}}" id="name" name="name" value="{{ $role->name }}" class="form-control" required {{ auth()->user()->admin_identifier === 'subadmin' ? 'readonly' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-header bord-btm">
                    <h5 class="mb-md-0 h6">{{ translate('Permissions') }}</h5>
                    @if(auth()->user()->admin_identifier === 'subadmin')
                        <small class="text-muted">{{ translate('Only permissions assigned to your account are available.') }}</small>
                    @endif
                </div>
                <br>

                @php
                    $permission_groups = $permissions->groupBy('parent');
                @endphp
                @foreach ($permission_groups as $key => $permission_group)
                    @php
                        $check = true;
                        if(in_array($permission_group[0]['parent'], ['otp_system', 'support_tickets', 'referral_system'], true)) {
                            $check = addon_activation($permission_group[0]['parent']);
                        }
                    @endphp
                    @if($check)
                        <div class="bd-example">
                            <ul class="list-group">
                                <li class="list-group-item bg-light" aria-current="true">
                                    {{ translate(Str::headline($permission_group[0]['parent'])) }}
                                </li>
                                <li class="list-group-item">
                                    <div class="row">
                                        @foreach ($permission_group as $permission)
                                            <div class="col-lg-2 col-md-3 col-sm-4 col-xs-6">
                                                <div class="p-2 border mt-1 mb-2">
                                                    <label class="control-label d-flex">{{ translate(Str::headline($permission->name)) }}</label>
                                                    <label class="aiz-switch aiz-switch-success">
                                                        <input type="checkbox" name="permissions[]" class="form-control demo-sw" value="{{ $permission->id }}" @checked($role->hasPermissionTo($permission->name))>
                                                        <span class="slider round"></span>
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <br>
                    @endif
                @endforeach

                <div class="form-group mb-3 mt-3 text-right">
                    <button type="submit" class="btn btn-primary">{{translate('Update')}}</button>
                </div>
            </div>
        </form>
    </div>

@endsection
