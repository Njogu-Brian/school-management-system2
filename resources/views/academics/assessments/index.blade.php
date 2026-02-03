@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Assessments</h3>
            <div class="text-muted">Recent assessment entries</div>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="date" name="week_ending" value="{{ request('week_ending') }}" class="form-control" />
                <button class="btn btn-outline-primary">Filter</button>
            </form>
            <a href="{{ route('academics.assessments.create') }}" class="btn btn-primary">New Assessment</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Week Ending</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Student</th>
                            <th>Score</th>
                            <th>%</th>
                            <th>Teacher</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assessments as $assessment)
                            <tr>
                                <td>{{ optional($assessment->assessment_date)->format('Y-m-d') }}</td>
                                <td>{{ optional($assessment->week_ending)->format('Y-m-d') }}</td>
                                <td>{{ $assessment->classroom?->name }}</td>
                                <td>{{ $assessment->subject?->name }}</td>
                                <td>{{ $assessment->student?->full_name ?? $assessment->student?->name }}</td>
                                <td>{{ $assessment->score }} / {{ $assessment->out_of }}</td>
                                <td>{{ $assessment->score_percent }}</td>
                                <td>{{ $assessment->staff?->full_name ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted p-4">No assessments found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
