@extends('layouts.app')

@section('content')
<h1 class="mb-4">Manage Staff</h1>

<div class="d-flex justify-content-between align-items-center mb-3">
    <a href="{{ route('staff.create') }}" class="btn btn-success">Add New Staff</a>

    <div>
        <a href="{{ asset('templates/staff_upload_template_enhanced.xlsx') }}" class="btn btn-outline-secondary me-2">
            Download Template
        </a>
        <a href="{{ route('staff.upload.form') }}" class="btn btn-primary">
            Bulk Upload Staff
        </a>
    </div>
</div>

{{-- keep rest same --}}
@endsection
