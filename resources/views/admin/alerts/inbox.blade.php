@extends('layouts.app')

@section('content')
<div class="container-fluid py-3" style="max-width: 920px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-1">Alerts</h1>
            <p class="text-muted mb-0">{{ count($alerts) }} waiting. Open one to go to the page it refers to, or mark it done.</p>
        </div>
        <a href="{{ route('activity-logs.index', ['alert_audit' => 1]) }}" class="btn btn-outline-secondary btn-sm">Audit trail</a>
    </div>

    @if($alerts === [])
        <div class="alert alert-light border">No pending alerts.</div>
    @else
        <div class="list-group">
            @foreach($alerts as $alert)
                <div class="list-group-item">
                    <div class="d-flex justify-content-between gap-3">
                        <div>
                            <div class="fw-semibold">{{ $alert['title'] }}</div>
                            <div class="text-muted small mt-1">{{ $alert['body'] }}</div>
                            <div class="text-muted small mt-1">{{ $alert['created_at'] }}</div>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <a class="btn btn-sm btn-outline-primary" href="{{ $alert['deep_link'] ?: route('system-logs.index') }}">Open</a>
                            <form method="POST" action="{{ route('admin.alerts.acknowledge', $alert['id']) }}">
                                @csrf
                                <button class="btn btn-sm btn-success">Done</button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
