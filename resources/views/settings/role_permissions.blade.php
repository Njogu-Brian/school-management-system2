@extends('layouts.app')
@section('content')
<div class="container">
    <h4 class="mb-3">Assign Permissions</h4>
    <form method="POST" action="{{ route('settings.update.permissions') }}">
        @csrf
        @foreach($permissions as $module => $features)
            <h5 class="mt-4">{{ $module }}</h5>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Feature</th>
                        @foreach($roles as $role)
                            <th>{{ $role->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($features as $permission)
                        <tr>
                            <td>{{ $permission->feature }}</td>
                            @foreach($roles as $role)
                                @php
                                    $pivot = $role->permissions->find($permission->id)?->pivot ?? null;
                                @endphp
                                <td>
                                    <div class="form-check">
                                        <label>V <input type="checkbox" name="permissions[{{ $role->id }}][{{ $permission->id }}][view]" {{ $pivot?->can_view ? 'checked' : '' }}></label>
                                        <label>A <input type="checkbox" name="permissions[{{ $role->id }}][{{ $permission->id }}][add]" {{ $pivot?->can_add ? 'checked' : '' }}></label>
                                        <label>E <input type="checkbox" name="permissions[{{ $role->id }}][{{ $permission->id }}][edit]" {{ $pivot?->can_edit ? 'checked' : '' }}></label>
                                        <label>D <input type="checkbox" name="permissions[{{ $role->id }}][{{ $permission->id }}][delete]" {{ $pivot?->can_delete ? 'checked' : '' }}></label>
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
        <button class="btn btn-success mt-3">Save Permissions</button>
    </form>
</div>
@endsection
