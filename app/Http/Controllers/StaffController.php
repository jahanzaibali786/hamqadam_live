<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Staff;
use App\Models\User;
use App\Utility\EmailUtility;
use App\Utility\SmsUtility;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

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
        $roles = $this->visibleRoles();
        return view('admin.staff.staffs.create', compact('roles'));
    }

    public function store(Request $request)
    {
        if (User::where('email', $request->email)->exists()) {
            flash(translate('Email already used'))->error();
            return back()->withInput();
        }

        $role = $this->roleForCreator($request->role_id);
        $user = new User;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->mobile;
        $user->user_type = 'staff';
        $user->admin_identifier = $this->isSuperAdmin() ? $this->adminIdentifierForRole($role) : 'subadmin';
        $user->password = Hash::make($request->password);

        if (! $user->save()) {
            flash(translate('Something went wrong'))->error();
            return back()->withInput();
        }

        $staff = new Staff;
        $staff->user_id = $user->id;
        $staff->role_id = $role->id;
        $user->assignRole($role->name);

        if (! $staff->save()) {
            $user->delete();
            flash(translate('Something went wrong'))->error();
            return back()->withInput();
        }

        $roleName = $role->name;
        if ($user->email && get_email_template('staff_account_opening_email', 'status')) {
            EmailUtility::staff_account_opening_email($user, $request->password, $roleName);
        }
        if ($user->phone && addon_activation('otp_system') && get_sms_template('staff_account_opening', 'status') == 1) {
            SmsUtility::staff_account_opening($user, $request->password, $roleName);
        }

        flash(translate('Moderator has been inserted successfully'))->success();
        return redirect()->route('staffs.index');
    }

    public function show($id)
    {
        // Moderator details are managed through the edit screen.
    }

    public function edit($id)
    {
        $staff = Staff::findOrFail(decrypt($id));
        $this->assertStaffIsManageable($staff);
        $roles = $this->visibleRoles();
        return view('admin.staff.staffs.edit', compact('staff', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $staff = Staff::findOrFail($id);
        $this->assertStaffIsManageable($staff);
        $user = $staff->user;
        $role = $this->roleForCreator($request->role_id);

        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->email = $request->email;
        $user->phone = $request->mobile;
        if (strlen((string) $request->password) > 0) {
            $user->password = Hash::make($request->password);
        }
        $user->admin_identifier = $this->isSuperAdmin() ? $this->adminIdentifierForRole($role) : 'subadmin';

        if (! $user->save()) {
            flash(translate('Something went wrong'))->error();
            return back()->withInput();
        }

        $staff->role_id = $role->id;
        $user->syncRoles([$role->name]);
        $staff->save();

        flash(translate('Moderator has been updated successfully'))->success();
        return redirect()->route('staffs.index');
    }

    public function destroy($id)
    {
        $staff = Staff::findOrFail($id);
        $this->assertStaffIsManageable($staff);
        User::destroy($staff->user->id);

        if (Staff::destroy($id)) {
            flash(translate('Moderator has been deleted successfully'))->success();
            return redirect()->route('staffs.index');
        }

        flash(translate('Something went wrong'))->error();
        return back();
    }

    private function isSuperAdmin(): bool
    {
        $user = auth()->user();

        return (bool) $user && (
            in_array($user->admin_identifier, ['admin', 'superadmin'], true)
            || $user->user_type === 'admin'
        );
    }

    private function visibleRoles(): Collection
    {
        if ($this->isSuperAdmin()) {
            return Role::query()->latest()->get();
        }

        return Role::query()
            ->whereIn('name', ['Sub Admin', 'subadmin'])
            ->latest()
            ->get();
    }

    private function roleForCreator($roleId): Role
    {
        $role = Role::findOrFail($roleId);
        if (! $this->isSuperAdmin() && ! in_array(strtolower($role->name), ['sub admin', 'subadmin'], true)) {
            abort(403, 'You may only create or manage Sub Admin moderators.');
        }

        return $role;
    }

    private function assertStaffIsManageable(Staff $staff): void
    {
        if (! $this->isSuperAdmin() && strtolower((string) $staff->role?->name) !== 'sub admin'
            && strtolower((string) $staff->role?->name) !== 'subadmin') {
            abort(403, 'You may only manage Sub Admin moderators.');
        }
    }

    private function adminIdentifierForRole(Role $role): string
    {
        return in_array(strtolower($role->name), ['sub admin', 'subadmin'], true) ? 'subadmin' : 'moderator';
    }
}
