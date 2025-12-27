@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Add Category</h1>
        <p class="text-muted mb-0">Create a grouping to power fee structures and billing.</p>
      </div>
      <a href="{{ route('student-categories.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <div class="settings-card">
      <form action="{{ route('student-categories.store') }}" method="POST" class="row g-3">
        @csrf
        <div class="col-12">
          <label class="form-label">Category Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="col-12">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3" placeholder="Optional notes (e.g., staff children, boarding)"></textarea>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="{{ route('student-categories.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <button type="submit" class="btn btn-settings-primary"><i class="bi bi-check-circle"></i> Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
