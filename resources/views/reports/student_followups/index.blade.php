@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Student Follow-Ups</h3>
            <div class="text-muted">Weekly student concerns and actions</div>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="date" name="week_ending" value="{{ request('week_ending') }}" class="form-control" />
                <button class="btn btn-outline-primary">Filter</button>
            </form>
            <a href="{{ route('reports.student-followups.create') }}" class="btn btn-primary">New Follow-Up</a>
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
                            <th>Week Ending</th>
                            <th>Student</th>
                            <th>Class</th>
                            <th>Academic</th>
                            <th>Behavior</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>{{ $report->week_ending?->format('Y-m-d') }}</td>
                                <td>{{ $report->student?->full_name ?? $report->student?->name }}</td>
                                <td>{{ $report->classroom?->name }}</td>
                                <td>{{ $report->academic_concern === null ? '—' : ($report->academic_concern ? 'Yes' : 'No') }}</td>
                                <td>{{ $report->behavior_concern === null ? '—' : ($report->behavior_concern ? 'Yes' : 'No') }}</td>
                                <td>{{ $report->progress_status ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">No follow-ups found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
