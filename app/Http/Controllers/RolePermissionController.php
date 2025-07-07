<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    public function index($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $permissions = Permission::all();
        return view('settings.roles.edit', compact('role', 'permissions'));
    }

    public function update(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);
        $data = $request->input('permissions', []);

        // Detach old permissions
        $role->permissions()->detach();

        // Attach new permissions with pivots
        foreach ($data as $permissionId => $actions) {
            $role->permissions()->attach($permissionId, [
                'can_view' => isset($actions['view']),
                'can_add' => isset($actions['add']),
                'can_edit' => isset($actions['edit']),
                'can_delete' => isset($actions['delete']),
            ]);
        }

        return redirect()->route('settings.role_permissions')->with('success', 'Permissions updated successfully.');
    }

    public function listRoles()
    {
        $roles = \App\Models\Role::all();
        return view('settings.roles.index', compact('roles'));
    }

}
