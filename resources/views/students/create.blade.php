@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['Create' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Student Admission</h1>
        <p class="text-muted mb-0">Add a new learner with profile and placement details.</p>
      </div>
      <a href="{{ url()->previous() ?: route('students.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    @include('students.partials.alerts')

    <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data" class="settings-card">
      @include('students.partials.form', [
        'mode' => 'create',
        'countryCodes' => $countryCodes ?? [],
        // controller should pass these:
        // 'classrooms'=>$classrooms, 'streams'=>$streams, 'categories'=>$categories, 'transportRoutes'=>$routes
      ])
    </form>
  </div>
</div>
@endsection
