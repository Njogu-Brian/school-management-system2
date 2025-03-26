@extends('layouts.app')

@section('content')
<h1>Edit Staff</h1>

<form action="{{ route('staff.update', $staff->id) }}" method="POST">
    @csrf @method('PUT')

    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" value="{{ $staff->name }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" value="{{ $staff->email }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Role</label>
        <select name="role" class="form-control" required>
            <option value="admin" {{ $staff->role == 'admin' ? 'selected' : '' }}>Admin</option>
            <option value="teacher" {{ $staff->role == 'teacher' ? 'selected' : '' }}>Teacher</option>
            <option value="driver" {{ $staff->role == 'driver' ? 'selected' : '' }}>Driver</option>
        </select>
    </div>

    <button class="btn btn-primary">Update</button>
</form>
@endsection
