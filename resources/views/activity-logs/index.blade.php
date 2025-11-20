@extends('layouts.app')
@php use Illuminate\Support\Str; @endphp

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Activity Logs</h1>
            <p class="text-muted mb-0">Audit trail of recent actions across the system.</p>
        </div>
        <span class="badge bg-light text-dark">
            <i class="bi bi-list-ul me-1"></i>{{ number_format($logs->total()) }} entries
        </span>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Action</label>
                    <select name="action" class="form-select">
                        <option value="">Any action</option>
                        @foreach($actions as $action)
                            <option value="{{ $action }}" @selected(request('action') == $action)>{{ ucfirst($action) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Model Type</label>
                    <input type="text" name="model_type" class="form-control" placeholder="App\Models\User"
                           value="{{ request('model_type') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                </div>
                <div class="col-md-3 text-md-end">
                    <button class="btn btn-primary me-2" type="submit">
                        <i class="bi bi-funnel"></i> Filter
                    </button>
                    <a href="{{ route('activity-logs.index') }}" class="btn btn-light">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Model</th>
                        <th>Description</th>
                        <th class="text-end">Details</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $log->created_at->format('d M Y') }}</div>
                                <small class="text-muted">{{ $log->created_at->format('H:i') }}</small>
                            </td>
                            <td>{{ $log->user->name ?? 'System' }}</td>
                            <td><span class="badge bg-secondary text-uppercase">{{ $log->action }}</span></td>
                            <td class="small">
                                {{ class_basename($log->model_type) ?? 'N/A' }}
                                @if($log->model_id)
                                    <div class="text-muted">#{{ $log->model_id }}</div>
                                @endif
                            </td>
                            <td class="text-muted">
                                {{ $log->description ? Str::limit($log->description, 60) : '—' }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('activity-logs.show', $log) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No activity logs found for the selected filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

