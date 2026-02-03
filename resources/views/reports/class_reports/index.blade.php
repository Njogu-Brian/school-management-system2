@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Class Reports</h3>
            <div class="text-muted">Weekly class status reports</div>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="date" name="week_ending" value="{{ request('week_ending') }}" class="form-control" />
                <button class="btn btn-outline-primary">Filter</button>
            </form>
            <a href="{{ route('reports.class-reports.create') }}" class="btn btn-primary">New Report</a>
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
                            <th>Class</th>
                            <th>Teacher</th>
                            <th>Discipline</th>
                            <th>Homework</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>{{ $report->week_ending?->format('Y-m-d') }}</td>
                                <td>{{ $report->classroom?->name }}</td>
                                <td>{{ $report->classTeacher?->full_name ?? '—' }}</td>
                                <td>{{ $report->discipline_level ?? '—' }}</td>
                                <td>{{ $report->homework_completion ?? '—' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($report->notes, 40) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">No reports found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
