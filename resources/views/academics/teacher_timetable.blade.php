@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Timetable</div>
        <h1 class="mb-1">Teacher Timetable Management</h1>
        <p class="text-muted mb-0">Manage teacher timetables and assignments.</p>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <p class="mb-3">Manage teacher timetables here. Assign classes and subjects to teachers.</p>
        <a href="#" class="btn btn-settings-primary">Add Teacher Timetable</a>
      </div>
    </div>
  </div>
</div>
@endsection
