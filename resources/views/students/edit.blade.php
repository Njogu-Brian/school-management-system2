@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    @include('students.partials.form_styles')
@endpush

@section('content')
<div class="settings-page ds-pilot student-form-page">
  <div class="settings-shell">
    <x-nav.breadcrumb :items="[
      'Home' => Route::has('dashboard') ? route('dashboard') : (Route::has('home') ? route('home') : url('/')),
      'Students' => route('students.index'),
      ($student->full_name ?? 'Student') => route('students.show', $student->id),
      'Edit' => null,
    ]" class="mb-3" />

    <x-page-header eyebrow="Students" title="Edit student" description="Update learner, parent, class, and contact details. Same form as admissions." class="mb-3">
      <x-slot:actions>
        <a href="{{ route('students.show', $student->id) }}" class="btn btn-ghost-strong">
          <i class="bi bi-arrow-left" aria-hidden="true"></i> Back
        </a>
      </x-slot:actions>
    </x-page-header>

    <x-feedback.flash />
    @include('students.partials.alerts')

    <div id="studentFormErrorBanner" class="student-form-error-banner @if(!$errors->any()) d-none @endif" role="alert" aria-live="assertive">
      <div class="student-form-error-banner__icon"><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i></div>
      <div class="flex-grow-1">
        <strong>Please fix the highlighted fields</strong>
        <ul class="mb-0 mt-1" id="studentFormErrorList">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>

    <form action="{{ route('students.update', $student->id) }}" method="POST" enctype="multipart/form-data" class="settings-card student-admission-form" id="studentAdmissionForm" novalidate>
      @include('students.partials.form', [
        'mode' => 'edit',
        'student' => $student,
        'familyMembers' => $familyMembers ?? [],
        'countryCodes' => $countryCodes ?? [],
      ])
    </form>
  </div>
</div>
@endsection
