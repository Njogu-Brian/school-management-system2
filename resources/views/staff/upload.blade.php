@extends('layouts.app')

@section('content')
<div class="container">
    <h3 class="mb-4">Bulk Upload Staff</h3>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('errors'))
        <div class="alert alert-danger">
            <strong>Some rows were skipped:</strong>
            <ul class="mb-0">
                @foreach(session('errors') as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p>
        <a href="{{ asset('templates/staff_upload_template.xlsx') }}" class="btn btn-outline-secondary mb-3">
            Download Sample Excel Template
        </a>
    </p>

    <form action="{{ route('staff.upload.handle') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
            <label>Select Excel/CSV File</label>
            <input type="file" name="file" class="form-control" required>
        </div>
        <button class="btn btn-primary">Upload</button>
    </form>
</div>
@endsection
