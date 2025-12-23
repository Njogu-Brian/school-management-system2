{{-- resources/views/dashboard/student.blade.php --}}
@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page">
  <div class="dashboard-shell">
    <div class="dash-hero mb-3">
      <span class="crumb">Dashboard</span>
      <h2 class="mb-1">Student Dashboard</h2>
      <p class="mb-0">Stay on top of your attendance, exams and updates.</p>
    </div>

    <div class="row g-3">
      <div class="col-lg-8">
        @include('dashboard.partials.attendance_line', ['attendance' => $charts['attendance']])
        @include('dashboard.partials.exam_subject_avgs', ['exam' => $charts['exam'] ?? []])
      </div>
      <div class="col-lg-4">
        @include('dashboard.partials.announcements', ['announcements' => $announcements])
        @include('dashboard.partials.upcoming', ['upcoming' => $upcoming])
      </div>
    </div>
  </div>
</div>
@endsection
