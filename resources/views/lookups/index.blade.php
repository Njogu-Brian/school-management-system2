@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Lookup Management</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row">
        <!-- Roles -->
        <div class="col-md-6 mb-4">
            <h4>Staff Roles</h4>
            <form method="POST" action="{{ route('lookups.roles.store') }}">
                @csrf
                <div class="input-group mb-2">
                    <input type="text" name="name" class="form-control" placeholder="New Role" required>
                    <button class="btn btn-primary">Add</button>
                </div>
            </form>
            <ul class="list-group">
                @foreach($roles as $role)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $role->name }}
                        <form method="POST" action="{{ route('lookups.roles.delete',$role->id) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Departments -->
        <div class="col-md-6 mb-4">
            <h4>Departments</h4>
            <form method="POST" action="{{ route('lookups.departments.store') }}">
                @csrf
                <div class="input-group mb-2">
                    <input type="text" name="name" class="form-control" placeholder="New Department" required>
                    <button class="btn btn-primary">Add</button>
                </div>
            </form>
            <ul class="list-group">
                @foreach($departments as $dept)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $dept->name }}
                        <form method="POST" action="{{ route('lookups.departments.delete',$dept->id) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="row">
        <!-- Job Titles -->
        <div class="col-md-6 mb-4">
            <h4>Job Titles</h4>
            <form method="POST" action="{{ route('lookups.jobtitles.store') }}">
                @csrf
                <div class="mb-2">
                    <select name="department_id" class="form-control" required>
                        <option value="">-- Select Department --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="input-group mb-2">
                    <input type="text" name="name" class="form-control" placeholder="Job Title" required>
                    <button class="btn btn-primary">Add</button>
                </div>
            </form>
            <ul class="list-group">
                @foreach($jobTitles as $job)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $job->name }} <span class="text-muted">({{ $job->department->name }})</span>
                        <form method="POST" action="{{ route('lookups.jobtitles.delete',$job->id) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Custom Fields -->
        <div class="col-md-6 mb-4">
            <h4>Custom Fields</h4>
            <form method="POST" action="{{ route('lookups.customfields.store') }}">
                @csrf
                <div class="mb-2">
                    <input type="text" name="label" class="form-control" placeholder="Field Label" required>
                </div>
                <div class="mb-2">
                    <input type="text" name="field_key" class="form-control" placeholder="field_key" required>
                </div>
                <div class="mb-2">
                    <select name="field_type" class="form-control" required>
                        <option value="text">Text</option>
                        <option value="number">Number</option>
                        <option value="email">Email</option>
                        <option value="date">Date</option>
                        <option value="file">File</option>
                    </select>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="required" class="form-check-input" id="required">
                    <label for="required" class="form-check-label">Required</label>
                </div>
                <button class="btn btn-primary">Add</button>
            </form>
            <ul class="list-group">
                @foreach($customFields as $field)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $field->label }} <small class="text-muted">({{ $field->field_type }})</small>
                        <form method="POST" action="{{ route('lookups.customfields.delete',$field->id) }}">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger">Delete</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection
