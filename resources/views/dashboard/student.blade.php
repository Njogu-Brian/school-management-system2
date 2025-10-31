{{-- resources/views/dashboard/student.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-xxl">
  <h2 class="mb-3">Student Dashboard</h2>

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
@endsection
