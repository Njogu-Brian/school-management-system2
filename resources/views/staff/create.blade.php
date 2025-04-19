@extends('layouts.app')

@section('content')
    <h1 class="mb-4">Add New Staff</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <!-- @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
    @endif -->

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('staff.store') }}" method="POST">
        @csrf

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Email (for login)</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="col-md-6 mb-3">
                <label>Assign Roles</label>
                <select name="roles[]" class="form-control" multiple required>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ ucfirst($role->name) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>First Name</label>
                <input type="text" name="first_name" class="form-control" required>
            </div>

            <div class="col-md-4 mb-3">
                <label>Middle Name</label>
                <input type="text" name="middle_name" class="form-control">
            </div>

            <div class="col-md-4 mb-3">
                <label>Last Name</label>
                <input type="text" name="last_name" class="form-control" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label>ID Number</label>
                <input type="text" name="id_number" class="form-control">
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="">-- Select --</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="col-md-3 mb-3">
                <label>Marital Status</label>
                <select name="marital_status" class="form-control">
                    <option value="">-- Select --</option>
                    <option value="single">Single</option>
                    <option value="married">Married</option>
                    <option value="divorced">Divorced</option>
                    <option value="widowed">Widowed</option>
                </select>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Current Location</label>
                <input type="text" name="address" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label>Emergency Contact Name</label>
                <input type="text" name="emergency_contact_name" class="form-control">
            </div>

            <div class="col-md-6 mb-3">
                <label>Emergency Contact Phone</label>
                <input type="text" name="emergency_contact_phone" class="form-control">
            </div>
        </div>

        <button class="btn btn-primary">Save Staff</button>
    </form>
@endsection
