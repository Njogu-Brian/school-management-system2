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
        <h1 class="mb-1">Assign Class Teacher</h1>
        <p class="text-muted mb-0">Select a class and assign its class teacher.</p>
      </div>
      <a href="{{ url()->previous() }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <form action="#" method="POST" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Class</label>
          <select class="form-select" name="class_id">
            <option value="1">Class A</option>
            <option value="2">Class B</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Teacher</label>
          <select class="form-select" name="teacher_id">
            <option value="1">Mr. John Doe</option>
            <option value="2">Ms. Jane Smith</option>
          </select>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <a href="{{ url()->previous() }}" class="btn btn-ghost-strong">Cancel</a>
        <button type="submit" class="btn btn-settings-primary">Assign Teacher</button>
      </div>
    </form>
  </div>
</div>
@endsection
