@extends('layouts.app')

@section('content')
<div class="container">
    <h4 class="mb-3">Roles & Permissions</h4>
    @foreach($roles as $role)
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <span>{{ ucfirst($role->name) }}</span>
@if(can_access('settings', 'roles', 'edit'))
                <a href="{{ route('permissions.edit', $role->id) }}" class="btn btn-sm btn-primary">Assign Permissions</a>
@endif
            </div>
        </div>
    @endforeach
</div>
@endsection
