@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $lesson_plan->title }}</h1>
        <div class="btn-group">
            <a href="{{ route('academics.lesson-plans.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('academics.lesson-plans.edit', $lesson_plan) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Lesson Plan Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Subject:</th>
                            <td>{{ $lesson_plan->subject->name }}</td>
                        </tr>
                        <tr>
                            <th>Classroom:</th>
                            <td>{{ $lesson_plan->classroom->name }}</td>
                        </tr>
                        <tr>
                            <th>Planned Date:</th>
                            <td>{{ $lesson_plan->planned_date->format('l, d M Y') }}</td>
                        </tr>
                        @if($lesson_plan->actual_date)
                        <tr>
                            <th>Actual Date:</th>
                            <td>{{ $lesson_plan->actual_date->format('l, d M Y') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-{{ $lesson_plan->status == 'completed' ? 'success' : ($lesson_plan->status == 'in_progress' ? 'warning' : 'info') }}">
                                    {{ ucfirst($lesson_plan->status) }}
                                </span>
                            </td>
                        </tr>
                        @if($lesson_plan->substrand)
                        <tr>
                            <th>CBC Substrand:</th>
                            <td>{{ $lesson_plan->substrand->strand->name }} - {{ $lesson_plan->substrand->name }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($lesson_plan->learning_objectives)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Learning Objectives</h5>
                </div>
                <div class="card-body">
                    @if(is_array($lesson_plan->learning_objectives))
                        <ul>
                            @foreach($lesson_plan->learning_objectives as $objective)
                                <li>{{ $objective }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p>{{ $lesson_plan->learning_objectives }}</p>
                    @endif
                </div>
            </div>
            @endif

            @if($lesson_plan->learning_outcomes)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Learning Outcomes</h5>
                </div>
                <div class="card-body">
                    <p>{{ $lesson_plan->learning_outcomes }}</p>
                </div>
            </div>
            @endif

            @if($lesson_plan->introduction)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Introduction</h5>
                </div>
                <div class="card-body">
                    <p>{{ $lesson_plan->introduction }}</p>
                </div>
            </div>
            @endif

            @if($lesson_plan->lesson_development)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Lesson Development</h5>
                </div>
                <div class="card-body">
                    <p>{{ $lesson_plan->lesson_development }}</p>
                </div>
            </div>
            @endif

            @if($lesson_plan->assessment)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Assessment</h5>
                </div>
                <div class="card-body">
                    <p>{{ $lesson_plan->assessment }}</p>
                </div>
            </div>
            @endif

            @if($lesson_plan->conclusion)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Conclusion</h5>
                </div>
                <div class="card-body">
                    <p>{{ $lesson_plan->conclusion }}</p>
                </div>
            </div>
            @endif

            @if($lesson_plan->reflection)
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Reflection</h5>
                </div>
                <div class="card-body">
                    <p>{{ $lesson_plan->reflection }}</p>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h5 class="mb-0">Information</h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Created by:</strong> {{ $lesson_plan->creator->first_name ?? 'N/A' }} {{ $lesson_plan->creator->last_name ?? '' }}<br>
                        <strong>Created:</strong> {{ $lesson_plan->created_at->format('d M Y') }}<br>
                        <strong>Duration:</strong> {{ $lesson_plan->duration_minutes }} minutes
                    </small>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    @can('homework.create')
                    <a href="{{ route('academics.lesson-plans.assign-homework', $lesson_plan) }}" 
                       class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-plus-circle"></i> Assign Homework
                    </a>
                    @endcan
                    @can('lesson_plans.export_pdf')
                    <a href="{{ route('academics.lesson-plans.export-pdf', $lesson_plan) }}" 
                       class="btn btn-danger w-100 mb-2" target="_blank">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </a>
                    @endcan
                    @can('lesson_plans.export_excel')
                    <a href="{{ route('academics.lesson-plans.export-excel', $lesson_plan) }}" 
                       class="btn btn-success w-100 mb-2">
                        <i class="bi bi-file-excel"></i> Export Excel
                    </a>
                    @endcan
                </div>
            </div>

            @if($homework && $homework->count() > 0)
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Linked Homework ({{ $homework->count() }})</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled">
                        @foreach($homework as $hw)
                        <li class="mb-2">
                            <a href="{{ route('academics.homework.show', $hw) }}" class="text-decoration-none">
                                <strong>{{ $hw->title }}</strong><br>
                                <small class="text-muted">Due: {{ $hw->due_date->format('d M Y') }}</small>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection


