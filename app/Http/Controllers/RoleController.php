<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['permission:show_staff_roles'])->only('index');
        $this->middleware(['permission:add_staff_roles'])->only('create');
        $this->middleware(['permission:edit_staff_roles'])->only('edit');
        $this->middleware(['permission:delete_staff_roles'])->only('destroy');
    }

    public function index()
    {
        $this->ensureUserActivityPermission();
        $this->ensureMemberVerificationPermission();

        $roles = $this->visibleRoles();
        return view('admin.staff.roles.index', compact('roles'));
    }

    public function add_permission(Request $request)
    {
        abort_unless($this->isSuperAdmin(), 403);

        Permission::create(['name' => $request->name, 'parent' => $request->parent]);
        return redirect()->route('roles.index');
    }

    public function create()
    {
        abort_unless($this->isSuperAdmin(), 403);

        $this->ensureUserActivityPermission();
        $this->ensureMemberVerificationPermission();

        return view('admin.staff.roles.create');
    }

    public function store(Request $request)
    {
        abort_unless($this->isSuperAdmin(), 403);

        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($this->permissionNames($request->permissions ?? []));
        flash(translate('New Role has been added successfully'))->success();
        return redirect()->route('roles.index');
    }

    public function show($id)
    {
        // Role details are managed through the edit screen.
    }

    public function edit($id)
    {
        $this->ensureUserActivityPermission();
        $this->ensureMemberVerificationPermission();

        $role = Role::findOrFail(decrypt($id));
        $this->assertRoleIsVisible($role);
        $permissions = $this->visiblePermissions();

        return view('admin.staff.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, $id)
    {
        $this->ensureUserActivityPermission();
        $this->ensureMemberVerificationPermission();

        $role = Role::findOrFail($id);
        $this->assertRoleIsVisible($role);
        $role->name = $this->isSuperAdmin() ? $request->name : 'Sub Admin';
        $role->save();
        $role->syncPermissions($this->permissionNames($request->permissions ?? []));

        flash(translate('Role has been updated successfully'))->success();
        return back();
    }

    public function destroy($id)
    {
        abort_unless($this->isSuperAdmin(), 403);

        if (Role::destroy($id)) {
            flash(translate('Role has been deleted successfully'))->success();
            return redirect()->route('roles.index');
        }

        flash(translate('Something went wrong'))->error();
        return back();
    }

    private function ensureUserActivityPermission(): void
    {
        Permission::findOrCreate('view_user_activity', 'web');

        Permission::query()
            ->where('name', 'view_user_activity')
            ->where(function ($query) {
                $query->whereNull('parent')->orWhere('parent', '!=', 'Members');
            })
            ->update(['parent' => 'Members']);
    }

    private function ensureMemberVerificationPermission(): void
    {
        Permission::findOrCreate('review_member_verification', 'web');

        Permission::query()
            ->where('name', 'review_member_verification')
            ->where(function ($query) {
                $query->whereNull('parent')->orWhere('parent', '!=', 'Members');
            })
            ->update(['parent' => 'Members']);
    }

    private function permissionNames(array $permissions): array
    {
        $names = collect($permissions)
            ->map(function ($permission) {
                if (is_numeric($permission)) {
                    return Permission::find((int) $permission)?->name;
                }

                return (string) $permission;
            })
            ->filter()
            ->values()
            ->all();

        if (! $this->isSuperAdmin()) {
            $names = array_values(array_intersect(
                $names,
                auth()->user()->getAllPermissions()->pluck('name')->all()
            ));
        }

        return $names;
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

    private function visiblePermissions(): Collection
    {
        return $this->isSuperAdmin()
            ? Permission::query()->get()
            : auth()->user()->getAllPermissions();
    }

    private function assertRoleIsVisible(Role $role): void
    {
        if (! $this->isSuperAdmin() && ! in_array(strtolower($role->name), ['sub admin', 'subadmin'], true)) {
            abort(403, 'You may only manage the Sub Admin role.');
        }
    }
}
