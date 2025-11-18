@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Homework Diary Entry</h1>
        <a href="{{ route('academics.homework-diary.index') }}" class="btn btn-secondary">
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

                        <dt class="col-sm-3">Status:</dt>
                        <dd class="col-sm-9">
                            <span class="badge bg-{{ $homework_diary->status == 'marked' ? 'success' : ($homework_diary->status == 'submitted' ? 'info' : 'warning') }}">
                                {{ ucfirst($homework_diary->status) }}
                            </span>
                        </dd>

                        @if($homework_diary->score !== null && $homework_diary->max_score !== null && $homework_diary->max_score > 0)
                        <dt class="col-sm-3">Score:</dt>
                        <dd class="col-sm-9">
                            <strong>{{ $homework_diary->score }}/{{ $homework_diary->max_score }}</strong> ({{ number_format($homework_diary->percentage, 1) }}%)
                        </dd>
                        @endif

                        @if($homework_diary->submitted_at)
                        <dt class="col-sm-3">Submitted:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->submitted_at->format('d M Y H:i') }}</dd>
                        @endif

                        @if($homework_diary->completed_at)
                        <dt class="col-sm-3">Completed:</dt>
                        <dd class="col-sm-9">{{ $homework_diary->completed_at->format('d M Y H:i') }}</dd>
                        @endif
                    </dl>

                    @if($homework_diary->homework->instructions)
                    <div class="mt-3">
                        <h6>Instructions:</h6>
                        <p>{{ $homework_diary->homework->instructions }}</p>
                    </div>
                    @endif

                    @if($homework_diary->student_notes)
                    <div class="mt-3">
                        <h6>Student Notes:</h6>
                        <p>{{ $homework_diary->student_notes }}</p>
                    </div>
                    @endif

                    @if($homework_diary->teacher_feedback)
                    <div class="mt-3">
                        <h6>Teacher Feedback:</h6>
                        <p>{{ $homework_diary->teacher_feedback }}</p>
                    </div>
                    @endif

                    @if($homework_diary->attachments && count($homework_diary->attachments) > 0)
                    <div class="mt-3">
                        <h6>Attachments:</h6>
                        <ul>
                            @foreach($homework_diary->attachments as $attachment)
                            <li>
                                <a href="{{ asset('storage/' . $attachment) }}" target="_blank">
                                    {{ basename($attachment) }}
                                </a>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Actions</h5>
                </div>
                <div class="card-body">
                    @can('homework.submit')
                    @if(Auth::user()->hasRole('Student') && $homework_diary->status !== 'marked')
                    <a href="{{ route('academics.homework-diary.submit', $homework_diary) }}" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-check-circle"></i> Submit Homework
                    </a>
                    @endif
                    @endcan

                    @can('homework.mark')
                    @if(Auth::user()->hasAnyRole(['Teacher', 'Admin', 'Super Admin']) && $homework_diary->status === 'submitted')
                    <a href="{{ route('academics.homework-diary.mark', $homework_diary) }}" class="btn btn-success w-100 mb-2">
                        <i class="bi bi-pencil"></i> Mark Homework
                    </a>
                    @endif
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

