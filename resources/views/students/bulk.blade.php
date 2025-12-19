@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Bulk Upload' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Bulk Upload Students</h1>
        <p class="text-muted mb-0">Download the template, then upload to preview and import.</p>
      </div>
      <a href="{{ route('students.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @include('students.partials.alerts')

    <div class="row g-3">
      <div class="col-lg-6">
        <div class="settings-card h-100">
          <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-download me-1"></i>
            <span>Step 1: Download Template</span>
          </div>
          <div class="card-body">
            <p class="mb-3">Use our Excel template to prepare your students list. Do not change headers.</p>
            <a href="{{ route('students.bulk.template') }}" class="btn btn-settings-primary">
              <i class="bi bi-file-earmark-excel"></i> Download Template
            </a>
          </div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="settings-card h-100">
          <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-upload me-1"></i>
            <span>Step 2: Upload & Preview</span>
          </div>
          <div class="card-body">
            <form action="{{ route('students.bulk.parse') }}" method="POST" enctype="multipart/form-data" class="vstack gap-3">
              @csrf
              <div>
                <label class="form-label">Upload Type</label>
                <select name="upload_type" class="form-select" required>
                  <option value="">— Select —</option>
                  <option value="new">New Students</option>
                  <option value="existing">Update Existing</option>
                </select>
              </div>
              <div>
                <label class="form-label">Excel File</label>
                <input type="file" name="upload_file" class="form-control" accept=".xlsx,.xls" required>
              </div>
              <button type="submit" class="btn btn-settings-primary"><i class="bi bi-search"></i> Preview Data</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
