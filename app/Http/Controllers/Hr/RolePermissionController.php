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
    public function accessAndLookups(Request $request)
    {
        // Roles and Permissions
        $roles = \App\Support\NavAccess::orderedRoles(
            Role::with('permissions')->get()
        );

        $selectedRole = $this->resolveSelectedRole($request, $roles);

        $permissionMatrix = $this->permissionMatrix();
        $permissions = $permissionMatrix['flat'];
        $moduleLabels = config('nav_access.module_labels', []);

        // HR Lookups
        $categories   = StaffCategory::all();
        $departments  = Department::all();
        $jobTitles    = JobTitle::with('department')->get();
        $customFields = CustomField::where('module', 'staff')->get();

        return view('hr.access_lookups', compact(
            'roles',
            'selectedRole',
            'permissions',
            'categories',
            'departments',
            'jobTitles',
            'customFields',
            'moduleLabels',
            'permissionMatrix'
        ));
    }

    /**
     * Catalog rows with view / create / edit / delete, plus any extra actions.
     *
     * @return array{groups: array<int, array<string, mixed>>, flat: \Illuminate\Support\Collection}
     */
    protected function permissionMatrix(): array
    {
        $guard = 'web';
        $catalog = config('nav_access.permission_catalog', []);
        $keys = [];
        foreach ($catalog as $group) {
            foreach ($group['rows'] as $row) {
                $keys[] = $row['key'];
                foreach (['view', 'create', 'edit', 'delete'] as $action) {
                    Permission::findOrCreate($row['key'].'.'.$action, $guard);
                }
            }
        }

        $all = Permission::orderBy('name')->get()->keyBy('name');
        $used = [];
        $groups = [];

        foreach ($catalog as $group) {
            $rows = [];
            foreach ($group['rows'] as $row) {
                $crud = [];
                foreach (['view', 'create', 'edit', 'delete'] as $action) {
                    $name = $row['key'].'.'.$action;
                    $perm = $all->get($name);
                    if ($perm) {
                        $crud[$action] = $perm;
                        $used[$perm->id] = true;
                    }
                }

                $extras = $all->filter(function ($perm) use ($row, $keys, $used) {
                    if (isset($used[$perm->id])) {
                        return false;
                    }
                    $owner = $this->permissionOwnerKey($perm->name, $keys);

                    return $owner === $row['key'];
                })->values();

                foreach ($extras as $extra) {
                    $used[$extra->id] = true;
                }

                $rows[] = [
                    'key' => $row['key'],
                    'label' => $row['label'],
                    'crud' => $crud,
                    'extras' => $extras,
                ];
            }

            $groups[] = [
                'label' => $group['label'],
                'rows' => $rows,
            ];
        }

        $unassigned = $all->filter(fn ($perm) => ! isset($used[$perm->id]))->values();
        if ($unassigned->isNotEmpty()) {
            $groups[] = [
                'label' => 'Other',
                'rows' => [[
                    'key' => 'other',
                    'label' => 'Other permissions',
                    'crud' => [],
                    'extras' => $unassigned,
                ]],
            ];
        }

        return [
            'groups' => $groups,
            'flat' => $all->values(),
        ];
    }

    /**
     * @param  list<string>  $keys
     */
    protected function permissionOwnerKey(string $permissionName, array $keys): ?string
    {
        $owner = null;
        foreach ($keys as $key) {
            if ($permissionName === $key || str_starts_with($permissionName, $key.'.')) {
                if ($owner === null || strlen($key) > strlen($owner)) {
                    $owner = $key;
                }
            }
        }

        return $owner;
    }

    protected function resolveSelectedRole(Request $request, $roles)
    {
        if ($roles->isEmpty()) {
            return null;
        }

        $key = $request->query('role');
        if ($key !== null && $key !== '') {
            if (is_numeric($key)) {
                $match = $roles->firstWhere('id', (int) $key);
            } else {
                $match = $roles->first(fn ($role) => strcasecmp($role->name, (string) $key) === 0);
            }

            if ($match) {
                return $match;
            }
        }

        return $roles->first();
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

        return redirect()->route('hr.access-lookups', ['role' => $role->id])
            ->with('success', "Permissions updated for {$role->name}.");
    }
}
