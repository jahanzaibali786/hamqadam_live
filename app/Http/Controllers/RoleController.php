<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

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
        $roles = Role::all();
        return view('admin.staff.roles.index', compact('roles'));
    }

    public function add_permission(Request $request)
    {
        $permission = Permission::create(['name' => $request->name, 'parent' => $request->parent]);
        return redirect()->route('roles.index');
    }

    public function create()
    {
        return view('admin.staff.roles.create');
    }

    public function store(Request $request)
    {
        $role = Role::create(['name' => $request->name]);
        $role->syncPermissions($this->permissionNames($request->permissions ?? []));
        flash(translate('New Role has been added successfully'))->success();
        return redirect()->route('roles.index');
    }

    public function show($id)
    {
        //
    }

    public function edit($id)
    {
        $role = Role::findOrFail(decrypt($id));
        return view('admin.staff.roles.edit', compact('role'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $role->name = $request->name;
        $role->save();
        $role->syncPermissions($this->permissionNames($request->permissions ?? []));
        flash(translate('Role has been updated successfully'))->success();
        return back();
    }

    public function destroy($id)
    {
        if(Role::destroy($id)){
            flash(translate('Role has been deleted successfully'))->success();
            return redirect()->route('roles.index');
        }

        flash(translate('Something went wrong'))->error();
        return back();
    }

    private function permissionNames(array $permissions): array
    {
        return collect($permissions)
            ->map(function ($permission) {
                if (is_numeric($permission)) {
                    return Permission::find((int) $permission)?->name;
                }

                return (string) $permission;
            })
            ->filter()
            ->values()
            ->all();
    }
}
