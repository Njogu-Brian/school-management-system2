@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Submit Homework</h1>
        <a href="{{ route('academics.homework-diary.show', $homework_diary) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Homework Details</h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-3">Homework:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->homework->title ?? '' }}</dd>

                        <dt class="col-sm-3">Subject:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->homework->subject->name ?? '' }}</dd>

                        <dt class="col-sm-3">Due Date:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->homework->due_date ? $homework_diary->homework->due_date->format('d M Y') : 'N/A' }}</dd>

                        @if($homework_diary->homework->instructions)
                        <dt class="col-sm-3">Instructions:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->homework->instructions }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Submit Homework</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('academics.homework-diary.submit.store', $homework_diary) }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label for="student_notes" class="form-label">Student Notes</label>
                            <textarea name="student_notes" id="student_notes" class="form-control @error('student_notes') is-invalid @enderror" rows="5">{{ old('student_notes', $homework_diary->student_notes) }}</textarea>
                            @error('student_notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Add any notes or comments about your homework submission</small>
                        </div>

                        <div class="mb-3">
                            <label for="attachments" class="form-label">Attachments</label>
                            <input type="file" name="attachments[]" id="attachments" class="form-control @error('attachments.*') is-invalid @enderror" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png">
                            @error('attachments.*')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">You can upload multiple files (PDF, Word, Images). Max file size: 10MB per file</small>
                        </div>

                        @if($homework_diary->attachments && count($homework_diary->attachments) > 0)
                        <div class="mb-3">
                            <label class="form-label">Existing Attachments</label>
                            <ul class="list-group">
                                @foreach($homework_diary->attachments as $attachment)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ asset('storage/' . $attachment) }}" target="_blank">
                                        {{ basename($attachment) }}
                                    </a>
                                    <small class="text-muted">Existing</small>
                                </li>
                                @endforeach
                            </ul>
                            <small class="text-muted">New files will be added to existing attachments</small>
                        </div>
                        @endif

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('academics.homework-diary.show', $homework_diary) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Submit Homework
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Submission Info</h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Status:</strong> 
                        <span class="badge bg-{{ $homework_diary->status == 'submitted' ? 'info' : 'warning' }}">
                            {{ ucfirst($homework_diary->status) }}
                        </span><br><br>
                        
                        @if($homework_diary->submitted_at)
                        <strong>Last Submitted:</strong> {{ $homework_diary->submitted_at->format('d M Y H:i') }}<br><br>
                        @endif

                        <strong>Due Date:</strong> {{ $homework_diary->homework->due_date ? $homework_diary->homework->due_date->format('d M Y') : 'N/A' }}<br>
                        
                        @if($homework_diary->homework->due_date)
                            @if($homework_diary->homework->due_date->isPast())
                            <span class="badge bg-danger mt-2">Overdue</span>
                            @elseif($homework_diary->homework->due_date->isToday())
                            <span class="badge bg-warning mt-2">Due Today</span>
                            @else
                            <span class="badge bg-success mt-2">{{ $homework_diary->homework->due_date->diffForHumans() }}</span>
                            @endif
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

