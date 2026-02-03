@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-1">Operations & Facilities</h3>
            <div class="text-muted">Weekly facilities status</div>
        </div>
        <div class="d-flex gap-2">
            <form method="GET" class="d-flex gap-2">
                <input type="date" name="week_ending" value="{{ request('week_ending') }}" class="form-control" />
                <button class="btn btn-outline-primary">Filter</button>
            </form>
            <a href="{{ route('reports.operations-facilities.create') }}" class="btn btn-primary">New Report</a>
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
                            <th>Campus</th>
                            <th>Area</th>
                            <th>Status</th>
                            <th>Issue</th>
                            <th>Resolved</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $report)
                            <tr>
                                <td>{{ $report->week_ending?->format('Y-m-d') }}</td>
                                <td>{{ $report->campus ?? '—' }}</td>
                                <td>{{ $report->area }}</td>
                                <td>{{ $report->status ?? '—' }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($report->issue_noted, 40) }}</td>
                                <td>{{ $report->resolved === null ? '—' : ($report->resolved ? 'Yes' : 'No') }}</td>
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
