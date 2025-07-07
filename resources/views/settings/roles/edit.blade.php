@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-4">Assign Permissions for: {{ ucfirst($role->name) }}</h4>

@if(can_access('settings', 'roles', 'edit'))
    <form action="{{ route('permissions.update', $role->id) }}" method="POST">
@endif
        @csrf

        <table class="table table-bordered">
            <thead class="table-light">
                <tr>
                    <th>Module</th>
                    <th>Feature</th>
                    <th>View</th>
                    <th>Add</th>
                    <th>Edit</th>
                    <th>Delete</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($permissions as $permission)
                    @php
                        $pivot = $role->permissions->firstWhere('id', $permission->id)?->pivot;
                    @endphp
                    <tr>
                        <td>{{ ucfirst($permission->module) }}</td>
                        <td>{{ ucfirst(str_replace('_', ' ', $permission->feature)) }}</td>
                        <td class="text-center">
                            <input type="checkbox" name="permissions[{{ $permission->id }}][view]" {{ $pivot && $pivot->can_view ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="permissions[{{ $permission->id }}][add]" {{ $pivot && $pivot->can_add ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="permissions[{{ $permission->id }}][edit]" {{ $pivot && $pivot->can_edit ? 'checked' : '' }}>
                        </td>
                        <td class="text-center">
                            <input type="checkbox" name="permissions[{{ $permission->id }}][delete]" {{ $pivot && $pivot->can_delete ? 'checked' : '' }}>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <button type="submit" class="btn btn-success">Save Permissions</button>
        <a href="{{ route('settings.role_permissions') }}" class="btn btn-secondary">Back</a>
    </form>
</div>
@endsection
