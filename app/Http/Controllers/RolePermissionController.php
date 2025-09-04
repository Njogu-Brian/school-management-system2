<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    /**
     * Show all roles
     */
    public function listRoles()
    {
        $roles = Role::all();
        return view('settings.roles.index', compact('roles'));
    }

    /**
     * Show edit page for a role's permissions
     */
    public function index($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $permissions = Permission::all();
        return view('settings.roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update permissions for a role
     */
    public function update(Request $request, $roleId)
    {
        $role = Role::findOrFail($roleId);

        // Input will just be an array of permission IDs or names
        $permissionIds = $request->input('permissions', []);

        // Convert IDs to names if needed
        $permissions = Permission::whereIn('id', $permissionIds)->pluck('name')->toArray();

        // Sync permissions with the role
        $role->syncPermissions($permissions);

        return redirect()
            ->route('settings.role_permissions')
            ->with('success', 'Permissions updated successfully.');
    }
}
