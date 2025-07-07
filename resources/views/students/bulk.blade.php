@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Bulk Upload Students</h1>

    {{-- Alerts --}}
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Step 1: Download Template --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Step 1: Download Template</div>
        <div class="card-body">
            <p>Download the Excel template for uploading student data. Ensure the format is not altered.</p>
            <a href="{{ route('students.bulk.template') }}" class="btn btn-outline-success">
                <i class="bi bi-download"></i> Download Template
            </a>
        </div>
    </div>

    {{-- Step 2: Upload Excel File --}}
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Step 2: Upload & Preview</div>
        <div class="card-body">
            <form action="{{ route('students.bulk.parse') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-3">
                    <label for="upload_type" class="form-label">Select Upload Type</label>
                    <select name="upload_type" class="form-select" required>
                        <option value="">-- Select Type --</option>
                        <option value="new">New Students</option>
                        <option value="existing">Existing Students</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="upload_file">Upload Filled Excel File</label>
                    <input type="file" name="upload_file" class="form-control" required accept=".xlsx, .xls">
                </div>

                <button type="submit" class="btn btn-outline-primary">
                    <i class="bi bi-search"></i> Preview Data
                </button>
            </form>
        </div>
    </div>

    {{-- Step 3 will show preview in parse blade --}}
</div>
@endsection
