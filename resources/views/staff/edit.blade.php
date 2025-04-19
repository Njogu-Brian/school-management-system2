@extends('layouts.app')

@section('content')
<h1>Edit Staff</h1>

<form action="{{ route('staff.update', $staff->id) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="mb-3">
        <label>First Name</label>
        <input type="text" name="first_name" value="{{ old('first_name', $staff->first_name) }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Middle Name</label>
        <input type="text" name="middle_name" value="{{ old('middle_name', $staff->middle_name) }}" class="form-control">
    </div>

    <div class="mb-3">
        <label>Last Name</label>
        <input type="text" name="last_name" value="{{ old('last_name', $staff->last_name) }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Email</label>
        <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control" required>
    </div>

    <div class="mb-3">
        <label>Phone Number</label>
        <input type="text" name="phone_number" value="{{ old('phone_number', $staff->phone_number) }}" class="form-control">
    </div>

    <div class="mb-3">
        <label>ID Number</label>
        <input type="text" name="id_number" value="{{ old('id_number', $staff->id_number) }}" class="form-control">
    </div>

    <div class="mb-3">
        <label>Date of Birth</label>
        <input type="date" name="date_of_birth" value="{{ old('date_of_birth', $staff->date_of_birth) }}" class="form-control">
    </div>

    <div class="mb-3">
        <label>Gender</label>
        <select name="gender" class="form-control">
            <option value="">Select Gender</option>
            <option value="male" {{ $staff->gender == 'male' ? 'selected' : '' }}>Male</option>
            <option value="female" {{ $staff->gender == 'female' ? 'selected' : '' }}>Female</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Marital Status</label>
        <select name="marital_status" class="form-control">
            <option value="">Select Status</option>
            <option value="single" {{ $staff->marital_status == 'single' ? 'selected' : '' }}>Single</option>
            <option value="married" {{ $staff->marital_status == 'married' ? 'selected' : '' }}>Married</option>
        </select>
    </div>

    <div class="mb-3">
        <label>Address</label>
        <input type="text" name="address" value="{{ old('address', $staff->address) }}" class="form-control">
    </div>

    <div class="mb-3">
        <label>Emergency Contact Name</label>
        <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name', $staff->emergency_contact_name) }}" class="form-control">
    </div>

    <div class="mb-3">
        <label>Emergency Contact Phone</label>
        <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone', $staff->emergency_contact_phone) }}" class="form-control">
    </div>

    <div class="mb-3">
        <label>Roles</label>
        <select name="roles[]" multiple class="form-control" required>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ $user->roles->contains('id', $role->id) ? 'selected' : '' }}>
                    {{ ucfirst($role->name) }}
                </option>
            @endforeach
        </select>
        <small>Hold Ctrl (Cmd on Mac) to select multiple roles</small>
    </div>

    <button type="submit" class="btn btn-primary">Update Staff</button>
</form>
@endsection
