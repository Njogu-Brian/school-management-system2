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
        <h1 class="mb-1">Edit Exam</h1>
        <p class="text-muted mb-0">Update exam details, dates, status, and publishing.</p>
      </div>
      <a href="{{ route('academics.exams.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @includeIf('partials.alerts')

    <form method="post" action="{{ route('academics.exams.update', $exam->id) }}" class="settings-card">
      @csrf @method('PUT')
      <div class="card-body">
        @include('academics.exams.partials.form', ['mode' => 'edit'])
      </div>
      <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
        <div class="text-muted small">Created: {{ $exam->created_at?->format('d M Y H:i') }} | Updated: {{ $exam->updated_at?->format('d M Y H:i') }}</div>
        <div>
          <button class="btn btn-settings-primary"><i class="bi bi-save2 me-1"></i>Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection
