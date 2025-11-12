@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">{{ $portfolio_assessment->title }}</h1>
        <div class="btn-group">
            <a href="{{ route('academics.portfolio-assessments.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('academics.portfolio-assessments.edit', $portfolio_assessment) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Portfolio Assessment Details</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Student:</th>
                            <td>{{ $portfolio_assessment->student->full_name }}</td>
                        </tr>
                        <tr>
                            <th>Subject:</th>
                            <td>{{ $portfolio_assessment->subject->name }}</td>
                        </tr>
                        <tr>
                            <th>Classroom:</th>
                            <td>{{ $portfolio_assessment->classroom->name }}</td>
                        </tr>
                        <tr>
                            <th>Portfolio Type:</th>
                            <td><span class="badge bg-info">{{ ucfirst($portfolio_assessment->portfolio_type) }}</span></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <span class="badge bg-{{ $portfolio_assessment->status == 'assessed' ? 'success' : ($portfolio_assessment->status == 'published' ? 'primary' : 'warning') }}">
                                    {{ ucfirst($portfolio_assessment->status) }}
                                </span>
                            </td>
                        </tr>
                        @if($portfolio_assessment->total_score)
                        <tr>
                            <th>Total Score:</th>
                            <td><strong>{{ $portfolio_assessment->total_score }}/100</strong></td>
                        </tr>
                        @endif
                        @if($portfolio_assessment->performanceLevel)
                        <tr>
                            <th>Performance Level:</th>
                            <td>
                                <span class="badge bg-success">
                                    {{ $portfolio_assessment->performanceLevel->code }} - {{ $portfolio_assessment->performanceLevel->name }}
                                </span>
                            </td>
                        </tr>
                        @endif
                        @if($portfolio_assessment->assessment_date)
                        <tr>
                            <th>Assessment Date:</th>
                            <td>{{ $portfolio_assessment->assessment_date->format('d M Y') }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            @if($portfolio_assessment->description)
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Description</h5>
                </div>
                <div class="card-body">
                    <p>{{ $portfolio_assessment->description }}</p>
                </div>
            </div>
            @endif

            @if($portfolio_assessment->feedback)
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Feedback</h5>
                </div>
                <div class="card-body">
                    <p>{{ $portfolio_assessment->feedback }}</p>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0">Information</h5>
                </div>
                <div class="card-body">
                    <small class="text-muted">
                        <strong>Academic Year:</strong> {{ $portfolio_assessment->academicYear->year }}<br>
                        <strong>Term:</strong> {{ $portfolio_assessment->term->name }}<br>
                        @if($portfolio_assessment->assessor)
                            <strong>Assessed by:</strong> {{ $portfolio_assessment->assessor->full_name }}<br>
                        @endif
                        <strong>Created:</strong> {{ $portfolio_assessment->created_at->format('d M Y') }}
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection


