<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Staff;
use App\Models\Role;
use App\Models\User;
use Hash;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;

class StaffController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_staffs'])->only('index');
        $this->middleware(['permission:add_staffs'])->only('create');
        $this->middleware(['permission:edit_staffs'])->only('edit');
        $this->middleware(['permission:delete_staffs'])->only('destroy');
    }

    public function index()
    {
        $staffs = Staff::latest()->paginate(10);
        return view('admin.staff.staffs.index', compact('staffs'));
    }

    public function create()
    {
        $roles = Role::latest()->get();
        return view('admin.staff.staffs.create', compact('roles'));
    }

    public function store(Request $request)
    {
        if(User::where('email', $request->email)->first() == null){
            $user = new User;
            $user->first_name = $request->first_name;
            $user->last_name  = $request->last_name;
            $user->email      = $request->email;
            $user->phone      = $request->mobile;
            $user->user_type  = 'staff';
            $role             = Role::findOrFail($request->role_id);
            $user->admin_identifier = str_contains(strtolower($role->name), 'sub') ? 'subadmin' : 'staff';
            $user->password   = Hash::make($request->password);
            if($user->save()){
                $staff = new Staff;
                $staff->user_id = $user->id;
                $staff->role_id = $request->role_id;
                $user->assignRole($role->name);
                if($staff->save()){
                    $role_name  = Role::where('id', $staff->role_id)->first()->name;

                    if($user->email != null && get_email_template('staff_account_opening_email','status'))
                    {
                        EmailUtility::staff_account_opening_email($user, $request->password, $role_name);
                    }

                    if($user->phone != null && addon_activation('otp_system') && (get_sms_template('staff_account_opening','status') == 1))
                    {
                        SmsUtility::staff_account_opening($user, $request->password, $role_name);
                    }

                    flash(translate('Staff has been inserted successfully'))->success();
                    return redirect()->route('staffs.index');
                }
            }
        }

        flash(translate('Email already used'))->error();
        return back();
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $staff = Staff::findOrFail(decrypt($id));
        $roles = Role::latest()->get();
        return view('admin.staff.staffs.edit', compact('staff','roles'));
    }

    public function update(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        $user  = $staff->user;
        $user->first_name = $request->first_name;
        $user->last_name  = $request->last_name;
        $user->email      = $request->email;
        $user->phone      = $request->mobile;
        if(strlen($request->password) > 0){
            $user->password = Hash::make($request->password);
        }
        if($user->save()){
            $staff->role_id = $request->role_id;
            $role = Role::findOrFail($request->role_id);
            $user->admin_identifier = str_contains(strtolower($role->name), 'sub') ? 'subadmin' : 'staff';
            $user->syncRoles($role->name);
            if($staff->save()){
                flash(translate('Staff has been updated successfully'))->success();
                return redirect()->route('staffs.index');
            }
        }

        flash(translate('Something went wrong'))->error();
        return back();
    }

    public function destroy($id)
    {
        User::destroy(Staff::findOrFail($id)->user->id);
        if(Staff::destroy($id)){
            flash(translate('Staff has been deleted successfully'))->success();
            return redirect()->route('staffs.index');
        }

        flash(translate('Something went wrong'))->error();
        return back();
    }
}
