@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Update Import' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Update Existing Students</h1>
        <p class="text-muted mb-0">Import updates for existing students using their admission numbers.</p>
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
            <p class="mb-3">Download the Excel template with existing student data. Fill in the fields you want to update and keep the <strong>admission_number</strong> column.</p>
            <div class="alert alert-info mb-3">
              <i class="bi bi-info-circle me-2"></i>
              <strong>Note:</strong> The template includes current data for the first 100 students. You can add more rows with admission numbers of other students.
            </div>
            <a href="{{ route('students.update-import.template') }}" class="btn btn-settings-primary">
              <i class="bi bi-file-earmark-excel"></i> Download Update Template
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
            <form action="{{ route('students.update-import.preview') }}" method="POST" enctype="multipart/form-data" class="vstack gap-3">
              @csrf
              <div>
                <label class="form-label">Excel File</label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                <small class="text-muted">Upload your filled template file</small>
              </div>
              <button type="submit" class="btn btn-settings-primary">
                <i class="bi bi-search"></i> Preview Changes
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="settings-card mt-3">
      <div class="card-header">
        <i class="bi bi-info-circle me-2"></i>
        <span>Instructions</span>
      </div>
      <div class="card-body">
        <ol class="mb-0">
          <li><strong>Download the template</strong> - It contains current data for existing students</li>
          <li><strong>Fill in updates</strong> - Only fill the columns you want to update. Leave others blank to keep current values.</li>
          <li><strong>Keep admission_number</strong> - This is required to identify which student to update</li>
          <li><strong>Add new rows</strong> - You can add rows for other students by entering their admission number</li>
          <li><strong>Preview before import</strong> - Always preview your changes before final import</li>
        </ol>
      </div>
    </div>
  </div>
</div>
@endsection
