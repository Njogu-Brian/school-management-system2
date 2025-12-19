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
        <h1 class="mb-1">Submit Homework</h1>
        <p class="text-muted mb-0">Upload work and notes for review.</p>
      </div>
      <a href="{{ route('academics.homework-diary.show', $homework_diary) }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i><h5 class="mb-0">Homework Details</h5></div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-3">Homework</dt><dd class="col-sm-9">{{ $homework_diary->homework->title ?? '' }}</dd>
              <dt class="col-sm-3">Subject</dt><dd class="col-sm-9">{{ $homework_diary->homework->subject->name ?? '' }}</dd>
              <dt class="col-sm-3">Due Date</dt><dd class="col-sm-9">{{ $homework_diary->homework->due_date ? $homework_diary->homework->due_date->format('d M Y') : 'N/A' }}</dd>
              @if($homework_diary->homework->instructions)
                <dt class="col-sm-3">Instructions</dt><dd class="col-sm-9">{{ $homework_diary->homework->instructions }}</dd>
              @endif
            </dl>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-upload"></i><h5 class="mb-0">Submit Homework</h5></div>
          <div class="card-body">
            <form action="{{ route('academics.homework-diary.submit.store', $homework_diary) }}" method="POST" enctype="multipart/form-data">
              @csrf
              <div class="mb-3">
                <label for="student_notes" class="form-label">Student Notes</label>
                <textarea name="student_notes" id="student_notes" class="form-control @error('student_notes') is-invalid @enderror" rows="5">{{ old('student_notes', $homework_diary->student_notes) }}</textarea>
                @error('student_notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Add notes or comments about your submission</small>
              </div>

              <div class="mb-3">
                <label for="attachments" class="form-label">Attachments</label>
                <input type="file" name="attachments[]" id="attachments" class="form-control @error('attachments.*') is-invalid @enderror" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                @error('attachments.*')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="text-muted">Upload multiple files (PDF, Word, Images). Max 10MB each</small>
              </div>

              @if($homework_diary->attachments && count($homework_diary->attachments) > 0)
              <div class="mb-3">
                <label class="form-label">Existing Attachments</label>
                <ul class="list-group list-group-modern">
                  @foreach($homework_diary->attachments as $attachment)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <a href="{{ asset('storage/' . $attachment) }}" target="_blank">{{ basename($attachment) }}</a>
                      <small class="text-muted">Existing</small>
                    </li>
                  @endforeach
                </ul>
                <small class="text-muted">New files will be added to existing attachments</small>
              </div>
              @endif

              <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('academics.homework-diary.show', $homework_diary) }}" class="btn btn-ghost-strong">Cancel</a>
                <button type="submit" class="btn btn-settings-primary"><i class="bi bi-check-circle"></i> Submit Homework</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><h5 class="mb-0">Submission Info</h5></div>
          <div class="card-body">
            <small class="text-muted d-block mb-1"><strong>Status:</strong> <span class="pill-badge pill-{{ $homework_diary->status == 'submitted' ? 'info' : 'warning' }}">{{ ucfirst($homework_diary->status) }}</span></small>
            @if($homework_diary->submitted_at)
              <small class="text-muted d-block mb-1"><strong>Last Submitted:</strong> {{ $homework_diary->submitted_at->format('d M Y H:i') }}</small>
            @endif
            <small class="text-muted d-block mb-1"><strong>Due Date:</strong> {{ $homework_diary->homework->due_date ? $homework_diary->homework->due_date->format('d M Y') : 'N/A' }}</small>
            @if($homework_diary->homework->due_date)
              @if($homework_diary->homework->due_date->isPast())
                <span class="pill-badge pill-danger mt-2">Overdue</span>
              @elseif($homework_diary->homework->due_date->isToday())
                <span class="pill-badge pill-warning mt-2">Due Today</span>
              @else
                <span class="pill-badge pill-success mt-2">{{ $homework_diary->homework->due_date->diffForHumans() }}</span>
              @endif
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
