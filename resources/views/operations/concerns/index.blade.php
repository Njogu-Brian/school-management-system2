@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0">Student concerns</h1>
            <p class="text-muted mb-0">Parent-raised issues and staff follow-up</p>
        </div>
        <a href="{{ route('operations.concerns.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Raise concern
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form method="GET" class="row g-2 mb-3">
        <div class="col-md-3">
            <input type="search" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search student…">
        </div>
        <div class="col-md-3">
            <select name="category" class="form-select">
                <option value="">All categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" @selected(request('category') === $cat)>{{ ucfirst($cat) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach(['open','in_progress','resolved','closed'] as $st)
                    <option value="{{ $st }}" @selected(request('status') === $st)>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <button class="btn btn-outline-primary w-100">Filter</button>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Staff</th>
                        <th>Raised</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($concerns as $concern)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $concern->student?->full_name }}</div>
                                <div class="small text-muted">{{ $concern->student?->admission_number }} · {{ $concern->student?->classroom?->name }}</div>
                            </td>
                            <td><span class="badge bg-secondary">{{ ucfirst($concern->category) }}</span></td>
                            <td>{{ ucfirst(str_replace('_',' ',$concern->status)) }}</td>
                            <td class="small">{{ $concern->concernedStaff->pluck('first_name')->join(', ') ?: '—' }}</td>
                            <td class="small">{{ $concern->created_at?->format('M d, Y') }}</td>
                            <td><a href="{{ route('operations.concerns.show', $concern->id) }}">View</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted py-4">No concerns yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-body">{{ $concerns->links() }}</div>
    </div>
</div>
@endsection
