@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Add New Staff</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

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
            <div class="col-md-4 mb-3">
                <label>First Name <span class="text-danger">*</span></label>
                <input type="text" name="first_name" value="{{ old('first_name') }}" class="form-control" required>
            </div>
            <div class="col-md-4 mb-3">
                <label>Middle Name</label>
                <input type="text" name="middle_name" value="{{ old('middle_name') }}" class="form-control">
            </div>
            <div class="col-md-4 mb-3">
                <label>Last Name <span class="text-danger">*</span></label>
                <input type="text" name="last_name" value="{{ old('last_name') }}" class="form-control" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Email <span class="text-danger">*</span></label>
                <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
            </div>
            <div class="col-md-6 mb-3">
                <label>Phone Number <span class="text-danger">*</span></label>
                <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="form-control" placeholder="+2547XXXXXXXX" required>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label>ID Number</label>
                <input type="text" name="id_number" value="{{ old('id_number') }}" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label>Date of Birth</label>
                <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}" class="form-control">
            </div>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label>Gender</label>
                <select name="gender" class="form-control">
                    <option value="">-- Select --</option>
                    <option value="male" {{ old('gender')=='male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender')=='female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ old('gender')=='other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>Marital Status</label>
                <select name="marital_status" class="form-control">
                    <option value="">-- Select --</option>
                    <option value="single" {{ old('marital_status')=='single' ? 'selected' : '' }}>Single</option>
                    <option value="married" {{ old('marital_status')=='married' ? 'selected' : '' }}>Married</option>
                    <option value="divorced" {{ old('marital_status')=='divorced' ? 'selected' : '' }}>Divorced</option>
                    <option value="widowed" {{ old('marital_status')=='widowed' ? 'selected' : '' }}>Widowed</option>
                </select>
            </div>
            <div class="col-md-4 mb-3">
                <label>Address</label>
                <input type="text" name="address" value="{{ old('address') }}" class="form-control">
            </div>
        </div>

        <h5 class="mt-4">Emergency Contact</h5>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label>Contact Name</label>
                <input type="text" name="emergency_contact_name" value="{{ old('emergency_contact_name') }}" class="form-control">
            </div>
            <div class="col-md-6 mb-3">
                <label>Contact Phone</label>
                <input type="text" name="emergency_contact_phone" value="{{ old('emergency_contact_phone') }}" class="form-control">
            </div>
        </div>

        <button class="btn btn-primary">Save Staff</button>
    </form>
</div>
@endsection
