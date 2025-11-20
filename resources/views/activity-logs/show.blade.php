@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('activity-logs.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to logs
        </a>
        <span class="badge bg-light text-dark">
            Logged {{ $log->created_at->diffForHumans() }}
        </span>
    </div>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-uppercase text-muted">Summary</h2>
                    <dl class="row mb-0">
                        <dt class="col-5">Action</dt>
                        <dd class="col-7"><span class="badge bg-secondary text-uppercase">{{ $log->action }}</span></dd>

                        <dt class="col-5">User</dt>
                        <dd class="col-7">{{ $log->user->name ?? 'System' }}</dd>

                        <dt class="col-5">Model</dt>
                        <dd class="col-7">
                            {{ $log->model_type ? class_basename($log->model_type) : '—' }}
                            @if($log->model_id)
                                <span class="text-muted">#{{ $log->model_id }}</span>
                            @endif
                        </dd>

                        <dt class="col-5">Route</dt>
                        <dd class="col-7">{{ $log->route ?? '—' }}</dd>

                        <dt class="col-5">Method</dt>
                        <dd class="col-7">{{ $log->method ?? '—' }}</dd>

                        <dt class="col-5">IP Address</dt>
                        <dd class="col-7">{{ $log->ip_address ?? '—' }}</dd>

                        <dt class="col-5">User Agent</dt>
                        <dd class="col-7 text-break">{{ $log->user_agent ?? '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted">Description</h3>
                    <p class="mb-0">{{ $log->description ?? 'No description provided.' }}</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="h6 text-uppercase text-muted">Old Values</h3>
                            @if($log->old_values)
                                <pre class="small bg-light p-3 rounded">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <p class="text-muted mb-0">—</p>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h3 class="h6 text-uppercase text-muted">New Values</h3>
                            @if($log->new_values)
                                <pre class="small bg-light p-3 rounded">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                            @else
                                <p class="text-muted mb-0">—</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

