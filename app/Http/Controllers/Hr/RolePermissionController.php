<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\StaffCategory;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\CustomField;

class RolePermissionController extends Controller
{
    /**
     * Combined Roles & HR Lookups page
     */
    public function accessAndLookups()
    {
        // Roles and Permissions
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($perm) {
            return explode('.', $perm->name)[0]; // group by module prefix
        });

        // HR Lookups
        $categories   = StaffCategory::all();
        $departments  = Department::all();
        $jobTitles    = JobTitle::with('department')->get();
        $customFields = CustomField::where('module', 'staff')->get();

        return view('hr.access_lookups', compact(
            'roles', 'permissions', 'categories', 'departments', 'jobTitles', 'customFields'
        ));
    }

    /**
     * Show all roles
     */
    public function listRoles()
    {
        $roles = Role::all();
        return view('hr.roles.index', compact('roles'));
    }

    /**
     * Show edit page for a role's permissions
     */
    public function index($roleId)
    {
        $role = Role::with('permissions')->findOrFail($roleId);
        $permissions = Permission::all();
        return view('hr.roles.edit', compact('role', 'permissions'));
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

        return redirect()->route('hr.access-lookups')
            ->with('success', 'Permissions updated successfully.');
    }
}
