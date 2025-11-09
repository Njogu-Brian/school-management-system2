@extends('layouts.app')

@section('content')
<div class="container">
  @include('students.partials.breadcrumbs', ['trail' => ['Bulk Upload' => null]])

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Bulk Upload Students</h1>
    <a href="{{ route('students.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <div class="row g-3">
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header bg-primary text-white"><i class="bi bi-download me-2"></i>Step 1: Download Template</div>
        <div class="card-body">
          <p>Use our Excel template to prepare your students list. Do not change headers.</p>
          <a href="{{ route('students.bulk.template') }}" class="btn btn-outline-success">
            <i class="bi bi-file-earmark-excel"></i> Download Template
          </a>
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card h-100">
        <div class="card-header bg-primary text-white"><i class="bi bi-upload me-2"></i>Step 2: Upload & Preview</div>
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
            <button type="submit" class="btn btn-outline-primary"><i class="bi bi-search"></i> Preview Data</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
