@extends('layouts.app')

@section('content')
<h1>Import Drop-Off Points</h1>

<div class="card p-3 mb-3">
    <p class="mb-1">Upload a CSV/XLSX file with the following columns:</p>
    <ul class="mb-2">
        <li><code>name</code> (required)</li>
        <li><code>route_id</code> or <code>route_name</code> (one is required)</li>
        <li><code>vehicle_ids</code> (optional, comma-separated IDs)</li>
        <li><code>vehicle_regs</code> (optional, comma-separated registration numbers)</li>
    </ul>
    <a href="{{ route('transport.dropoffpoints.template') }}" class="btn btn-outline-secondary btn-sm">
        Download CSV Template
    </a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Import failed:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('transport.dropoffpoints.import') }}" method="POST" enctype="multipart/form-data" class="card p-3">
    @csrf
    <div class="mb-3">
        <label class="form-label">File</label>
        <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.txt" required>
        @error('file') <div class="text-danger small">{{ $message }}</div> @enderror
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Import</button>
        <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-light">Cancel</a>
    </div>
</form>
@endsection
