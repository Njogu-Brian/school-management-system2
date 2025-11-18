@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Mark Homework</h1>
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
                        <dt class="col-sm-3">Student:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->student->first_name ?? '' }} {{ $homework_diary->student->last_name ?? '' }}</dd>

                        <dt class="col-sm-3">Homework:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->homework->title ?? '' }}</dd>

                        <dt class="col-sm-3">Subject:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->homework->subject->name ?? '' }}</dd>

                        <dt class="col-sm-3">Due Date:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->homework->due_date ? $homework_diary->homework->due_date->format('d M Y') : 'N/A' }}</dd>

                        <dt class="col-sm-3">Submitted:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->submitted_at ? $homework_diary->submitted_at->format('d M Y H:i') : 'Not submitted' }}</dd>

                        @if($homework_diary->homework->instructions)
                        <dt class="col-sm-3">Instructions:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->homework->instructions }}</dd>
                        @endif

                        @if($homework_diary->student_notes)
                        <dt class="col-sm-3">Student Notes:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->student_notes }}</dd>
                        @endif
                    </dl>

                    @if($homework_diary->attachments && count($homework_diary->attachments) > 0)
                    <div class="mt-3">
                        <h6>Student Attachments:</h6>
                        <ul class="list-group">
                            @foreach($homework_diary->attachments as $attachment)
                            <li class="list-group-item">
                                <a href="{{ asset('storage/' . $attachment) }}" target="_blank">
                                    <i class="bi bi-file-earmark"></i> {{ basename($attachment) }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Mark Homework</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('academics.homework-diary.mark.store', $homework_diary) }}" method="POST">
                        @csrf

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="score" class="form-label">Score</label>
                                <input type="number" name="score" id="score" 
                                       class="form-control @error('score') is-invalid @enderror" 
                                       value="{{ old('score', $homework_diary->score) }}" 
                                       min="0" max="{{ $homework_diary->homework->max_score ?? 100 }}" 
                                       step="0.01">
                                @error('score')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Enter the score achieved by the student</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Max Score</label>
                                <input type="text" class="form-control" 
                                       value="{{ $homework_diary->homework->max_score ?? 100 }}" 
                                       disabled>
                                <small class="text-muted">Maximum possible score</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="teacher_feedback" class="form-label">Teacher Feedback</label>
                            <textarea name="teacher_feedback" id="teacher_feedback" 
                                      class="form-control @error('teacher_feedback') is-invalid @enderror" 
                                      rows="6" placeholder="Provide constructive feedback to the student...">{{ old('teacher_feedback', $homework_diary->teacher_feedback) }}</textarea>
                            @error('teacher_feedback')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Provide detailed feedback on the student's work</small>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Note:</strong> Once marked, the homework status will be set to "Marked" and the student will be able to view the feedback.
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('academics.homework-diary.show', $homework_diary) }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-circle"></i> Mark Homework
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Submission Info</h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Status:</strong> 
                        <span class="badge bg-info">{{ ucfirst($homework_diary->status) }}</span><br><br>
                        
                        <strong>Submitted:</strong> {{ $homework_diary->submitted_at ? $homework_diary->submitted_at->format('d M Y H:i') : 'Not submitted' }}<br><br>
                        
                        <strong>Due Date:</strong> {{ $homework_diary->homework->due_date ? $homework_diary->homework->due_date->format('d M Y') : 'N/A' }}<br>
                    </small>
                </div>
            </div>

            @if($homework_diary->score !== null && $homework_diary->max_score !== null)
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Current Score</h5>
                </div>
                <div class="card-body text-center">
                    <h2>{{ $homework_diary->score }}/{{ $homework_diary->max_score }}</h2>
                    <p class="mb-0">{{ number_format(($homework_diary->score / $homework_diary->max_score) * 100, 1) }}%</p>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

