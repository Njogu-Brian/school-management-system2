@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">Bulk Upload Staff</h1>
                <p class="text-muted mb-0">Import staff records from Excel/CSV.</p>
            </div>
            <a href="{{ route('staff.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Staff
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('errors'))
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach(session('errors') as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bi bi-upload"></i> Upload File</h5>
                    <p class="text-muted small mb-0">Excel/CSV with staff data.</p>
                </div>
                <a href="{{ route('staff.template') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-download"></i> Download Template
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.upload.parse') }}" method="POST" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-12">
                        <label class="form-label">Upload Excel File *</label>
                        <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted">Accepted: XLSX, XLS, CSV</small>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('staff.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-upload"></i> Upload
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
