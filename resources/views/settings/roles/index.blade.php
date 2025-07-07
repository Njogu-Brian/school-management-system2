@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Role List</h4>
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($roles as $role)
                <tr>
                    <td>{{ ucfirst($role->name) }}</td>
                    <td>
<!-- @if(can_access('settings', 'roles', 'view'))
                        <a href="{{ route('permissions.edit', $role->id) }}" class="btn btn-sm btn-primary">Assign Permissions</a>
@endif -->
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
