@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">Edit Exam</h3>
    <a href="{{ route('academics.exams.index') }}" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  @includeIf('partials.alerts')

  <form method="post" action="{{ route('academics.exams.update', $exam->id) }}" class="card shadow-sm">
    @csrf @method('PUT')
    <div class="card-body">
      @include('academics.exams.partials.form', ['mode' => 'edit'])
    </div>
    <div class="card-footer d-flex justify-content-between">
      <div class="text-muted small">
        Created: {{ $exam->created_at?->format('d M Y H:i') }} |
        Updated: {{ $exam->updated_at?->format('d M Y H:i') }}
      </div>
      <div>
        <button class="btn btn-primary"><i class="bi bi-save2 me-1"></i>Save Changes</button>
      </div>
    </div>
  </form>
</div>
@endsection
