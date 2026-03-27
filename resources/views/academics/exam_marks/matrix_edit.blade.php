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
        <h1 class="mb-1">Enter Marks — Matrix</h1>
        <p class="text-muted mb-0">
          Type: {{ $examType->name }} · Class: {{ $classroom->name }}@if($stream) · Stream: {{ $stream->name }}@endif
        </p>
      </div>
      <a href="{{ route('academics.exam-marks.bulk.form') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Change Selection</a>
    </div>

    @includeIf('partials.alerts')

    @if($exams->isEmpty())
      <div class="alert alert-warning">No open/marking exams found for this exam type and class context.</div>
    @elseif($students->isEmpty())
      <div class="alert alert-warning">No active learners found for the selected class/stream context.</div>
    @endif

    <form method="post" action="{{ route('academics.exam-marks.matrix.store') }}" class="settings-card">
      @csrf
      <input type="hidden" name="exam_type_id" value="{{ $examType->id }}">
      <input type="hidden" name="classroom_id" value="{{ $classroom->id }}">
      @if($stream)
        <input type="hidden" name="stream_id" value="{{ $stream->id }}">
      @endif

      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="min-width:220px;">Learner</th>
                @foreach($exams as $exam)
                  <th style="min-width:220px;">
                    <div class="fw-semibold">{{ $exam->name }}</div>
                    <div class="small text-muted">{{ $exam->subject?->name ?? 'Subject' }}</div>
                    <div class="small text-muted">Max {{ (float)($exam->examType?->default_max_mark ?? $exam->max_marks ?? 100) }}</div>
                  </th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @forelse($students as $s)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $s->full_name }}</div>
                    <div class="small text-muted">Adm: {{ $s->admission_number ?? '—' }}</div>
                  </td>
                  @foreach($exams as $exam)
                    @php
                      $mark = $existing[$s->id.'-'.$exam->id] ?? null;
                    @endphp
                    <td>
                      <input
                        type="number"
                        step="0.01"
                        class="form-control form-control-sm mb-1"
                        name="rows[{{ $s->id }}][{{ $exam->id }}][score]"
                        value="{{ old("rows.$s->id.$exam->id.score", $mark?->score_raw) }}"
                        placeholder="Score">
                      <input
                        type="text"
                        class="form-control form-control-sm"
                        name="rows[{{ $s->id }}][{{ $exam->id }}][subject_remark]"
                        value="{{ old("rows.$s->id.$exam->id.subject_remark", $mark?->subject_remark) }}"
                        maxlength="500"
                        placeholder="Remark (optional)">
                    </td>
                  @endforeach
                </tr>
              @empty
                <tr>
                  <td colspan="{{ 1 + $exams->count() }}" class="text-center text-muted py-4">No learners found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="small text-muted">Only open/marking exams are loaded. Access is filtered by your class/subject permissions.</div>
        <button class="btn btn-settings-primary" @disabled($students->isEmpty() || $exams->isEmpty())>
          <i class="bi bi-save2 me-1"></i>Save Matrix
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
