@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">New Exam</h1>
        <p class="text-muted mb-0">Set exam details, dates, and publishing options.</p>
      </div>
      <a href="{{ route('academics.exams.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @includeIf('partials.alerts')

    <form method="post" action="{{ route('academics.exams.store') }}" class="settings-card">
      @csrf
      <div class="card-body">
        @include('academics.exams.partials.form', ['mode' => 'create'])
      </div>
      <div class="card-footer d-flex justify-content-end">
        <button class="btn btn-settings-primary"><i class="bi bi-save2 me-1"></i>Create Exam</button>
      </div>
    </form>
  </div>
</div>
@endsection
