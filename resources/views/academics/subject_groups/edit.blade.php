@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Edit Subject Group</h1>
        <p class="text-muted mb-0">Update group name and description.</p>
      </div>
      <a href="{{ route('academics.subject_groups.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <form method="POST" action="{{ route('academics.subject_groups.update', $subject_group->id) }}" class="settings-card">
      @csrf
      @method('PUT')
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Name <span class="text-danger">*</span></label>
          <input type="text" name="name" value="{{ $subject_group->name }}" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3">{{ $subject_group->description }}</textarea>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ route('academics.subject_groups.index') }}" class="btn btn-ghost-strong">Cancel</a>
        <button class="btn btn-settings-primary">Update</button>
      </div>
    </form>
  </div>
</div>
@endsection
