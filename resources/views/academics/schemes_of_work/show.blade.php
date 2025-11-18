@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $schemes_of_work->title }}</h1>
        <div class="btn-group">
            @can('schemes_of_work.export_pdf')
            <a href="{{ route('academics.schemes-of-work.export-pdf', $schemes_of_work) }}" class="btn btn-danger" target="_blank">
                <i class="bi bi-file-pdf"></i> Export PDF
            </a>
            @endcan
            @can('schemes_of_work.export_excel')
            <a href="{{ route('academics.schemes-of-work.export-excel', $schemes_of_work) }}" class="btn btn-success">
                <i class="bi bi-file-excel"></i> Export Excel
            </a>
            @endcan
            @can('schemes_of_work.edit')
            <a href="{{ route('academics.schemes-of-work.edit', $schemes_of_work) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            @endcan
            <a href="{{ route('academics.schemes-of-work.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Scheme Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Subject:</th>
                            <td>{{ $schemes_of_work->subject->name }}</td>
                        </tr>
                        <tr>
                            <th>Classroom:</th>
                            <td>{{ $schemes_of_work->classroom->name }}</td>
                        </tr>
                        <tr>
                            <th>Academic Year:</th>
                            <td>{{ $schemes_of_work->academicYear->year }}</td>
                        </tr>
                        <tr>
                            <th>Term:</th>
                            <td>{{ $schemes_of_work->term->name }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-{{ $schemes_of_work->status == 'active' ? 'success' : ($schemes_of_work->status == 'completed' ? 'info' : 'warning') }}">
                                    {{ ucfirst($schemes_of_work->status) }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>Progress:</th>
                            <td>
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar" role="progressbar" style="width: {{ $schemes_of_work->progress_percentage }}%">
                                        {{ $schemes_of_work->progress_percentage }}%
                                    </div>
                                </div>
                                <small class="text-muted">{{ $schemes_of_work->lessons_completed }} of {{ $schemes_of_work->total_lessons }} lessons completed</small>
                            </td>
                        </tr>
                        @if($schemes_of_work->description)
                        <tr>
                            <th>Description:</th>
                            <td>{{ $schemes_of_work->description }}</td>
                        </tr>
                        @endif
                        @if($schemes_of_work->general_remarks)
                        <tr>
                            <th>Remarks:</th>
                            <td>{{ $schemes_of_work->general_remarks }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($schemes_of_work->lessonPlans->count() > 0)
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Lesson Plans ({{ $schemes_of_work->lessonPlans->count() }})</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Planned Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($schemes_of_work->lessonPlans as $plan)
                                <tr>
                                    <td>{{ $plan->title }}</td>
                                    <td>{{ $plan->planned_date->format('d M Y') }}</td>
                                    <td>
                                        <span class="badge bg-{{ $plan->status == 'completed' ? 'success' : 'info' }}">
                                            {{ ucfirst($plan->status) }}
                                        </span>
                                    </td>
                                    <td>
                                        <a href="{{ route('academics.lesson-plans.show', $plan) }}" class="btn btn-sm btn-outline-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    @if(!$schemes_of_work->isApproved() && Auth::user()->hasAnyRole(['Admin', 'Super Admin']))
                    <form action="{{ route('academics.schemes-of-work.approve', $schemes_of_work) }}" method="POST" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-check-circle"></i> Approve Scheme
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('academics.lesson-plans.create', ['scheme_of_work_id' => $schemes_of_work->id]) }}" class="btn btn-primary w-100 mb-2">
                        <i class="bi bi-plus-circle"></i> Add Lesson Plan
                    </a>

                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>Created by:</strong> {{ $schemes_of_work->creator->full_name ?? 'N/A' }}<br>
                            <strong>Created:</strong> {{ $schemes_of_work->created_at->format('d M Y') }}
                            @if($schemes_of_work->isApproved())
                                <br><strong>Approved by:</strong> {{ $schemes_of_work->approver->full_name ?? 'N/A' }}
                                <br><strong>Approved:</strong> {{ $schemes_of_work->approved_at->format('d M Y') }}
                            @endif
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

