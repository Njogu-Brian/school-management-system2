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
        <h1 class="mb-1">Homework Diary Entry</h1>
        <p class="text-muted mb-0">Submission details, feedback, and actions.</p>
      </div>
      <a href="{{ route('academics.homework-diary.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i><h5 class="mb-0">Homework Details</h5></div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-3">Student</dt><dd class="col-sm-9">{{ $homework_diary->student->full_name ?? '' }}</dd>
              <dt class="col-sm-3">Homework</dt><dd class="col-sm-9">{{ $homework_diary->homework->title ?? '' }}</dd>
              <dt class="col-sm-3">Subject</dt><dd class="col-sm-9">{{ $homework_diary->homework->subject->name ?? '' }}</dd>
              <dt class="col-sm-3">Due Date</dt><dd class="col-sm-9">{{ $homework_diary->homework->due_date ? $homework_diary->homework->due_date->format('d M Y') : 'N/A' }}</dd>
              <dt class="col-sm-3">Status</dt><dd class="col-sm-9"><span class="pill-badge pill-{{ $homework_diary->status == 'marked' ? 'success' : ($homework_diary->status == 'submitted' ? 'info' : 'warning') }}">{{ ucfirst($homework_diary->status) }}</span></dd>
              @if($homework_diary->score !== null && $homework_diary->max_score !== null && $homework_diary->max_score > 0)
                <dt class="col-sm-3">Score</dt><dd class="col-sm-9"><strong>{{ $homework_diary->score }}/{{ $homework_diary->max_score }}</strong> ({{ number_format($homework_diary->percentage, 1) }}%)</dd>
              @endif
              @if($homework_diary->submitted_at)
                <dt class="col-sm-3">Submitted</dt><dd class="col-sm-9">{{ $homework_diary->submitted_at->format('d M Y H:i') }}</dd>
              @endif
              @if($homework_diary->completed_at)
                <dt class="col-sm-3">Completed</dt><dd class="col-sm-9">{{ $homework_diary->completed_at->format('d M Y H:i') }}</dd>
              @endif
            </dl>

            @if($homework_diary->homework->instructions)
              <div class="mt-3"><h6>Instructions</h6><p class="mb-0">{{ $homework_diary->homework->instructions }}</p></div>
            @endif
            @if($homework_diary->student_notes)
              <div class="mt-3"><h6>Student Notes</h6><p class="mb-0">{{ $homework_diary->student_notes }}</p></div>
            @endif
            @if($homework_diary->teacher_feedback)
              <div class="mt-3"><h6>Teacher Feedback</h6><p class="mb-0">{{ $homework_diary->teacher_feedback }}</p></div>
            @endif
            @if($homework_diary->attachments && count($homework_diary->attachments) > 0)
              <div class="mt-3">
                <h6>Attachments</h6>
                <ul class="list-group list-group-modern">
                  @foreach($homework_diary->attachments as $attachment)
                    <li class="list-group-item"><a href="{{ asset('storage/' . $attachment) }}" target="_blank">{{ basename($attachment) }}</a></li>
                  @endforeach
                </ul>
              </div>
            @endif
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-lightning-charge"></i><h5 class="mb-0">Actions</h5></div>
          <div class="card-body d-grid gap-2">
            @can('homework.submit')
              @if(Auth::user()->hasRole('Student') && $homework_diary->status !== 'marked')
                <a href="{{ route('academics.homework-diary.submit', $homework_diary) }}" class="btn btn-settings-primary"><i class="bi bi-check-circle"></i> Submit Homework</a>
              @endif
            @endcan
            @can('homework.mark')
              @if(Auth::user()->hasAnyRole(['Teacher', 'Admin', 'Super Admin']) && $homework_diary->status === 'submitted')
                <a href="{{ route('academics.homework-diary.mark', $homework_diary) }}" class="btn btn-ghost-strong text-success"><i class="bi bi-pencil"></i> Mark Homework</a>
              @endif
            @endcan
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
