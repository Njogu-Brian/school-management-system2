@extends('layouts.app')

@push('styles')
<style>
.progress-container {
    max-width: 800px;
    margin: 0 auto;
}
.progress-card {
    background: white;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stat-card {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 1.5rem;
    text-align: center;
}
.stat-value {
    font-size: 2rem;
    font-weight: bold;
    margin: 0.5rem 0;
}
.stat-label {
    color: #6c757d;
    font-size: 0.875rem;
}
.progress-bar-container {
    margin: 2rem 0;
}
.auto-refresh {
    color: #6c757d;
    font-size: 0.875rem;
}
</style>
@endpush

@section('content')
<div class="progress-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">WhatsApp Bulk Send Progress</h1>
            <p class="text-muted mb-0">Tracking ID: <code>{{ $trackingId }}</code></p>
        </div>
        <a href="{{ route('communication.send.whatsapp') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Back to Send WhatsApp
        </a>
    </div>

    <div class="progress-card">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Total</div>
                    <div class="stat-value text-primary">{{ $progress['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Sent</div>
                    <div class="stat-value text-success">{{ $progress['sent'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Failed</div>
                    <div class="stat-value text-danger">{{ $progress['failed'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-label">Skipped</div>
                    <div class="stat-value text-warning">{{ $progress['skipped'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="progress-bar-container">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-semibold">Progress</span>
                <span class="text-muted">{{ $progress['processed'] ?? 0 }} / {{ $progress['total'] ?? 0 }}</span>
            </div>
            <div class="progress" style="height: 30px;">
                @php
                    $percentage = ($progress['total'] ?? 0) > 0 
                        ? round((($progress['processed'] ?? 0) / ($progress['total'] ?? 1)) * 100) 
                        : 0;
                @endphp
                <div class="progress-bar progress-bar-striped progress-bar-animated 
                    @if(($progress['status'] ?? '') === 'completed') bg-success
                    @elseif(($progress['status'] ?? '') === 'failed') bg-danger
                    @else bg-primary @endif" 
                    role="progressbar" 
                    style="width: {{ $percentage }}%"
                    aria-valuenow="{{ $percentage }}" 
                    aria-valuemin="0" 
                    aria-valuemax="100">
                    {{ $percentage }}%
                </div>
            </div>
        </div>

        <div class="mt-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>Status:</strong> 
                    <span class="badge bg-@if(($progress['status'] ?? '') === 'completed')success
                        @elseif(($progress['status'] ?? '') === 'failed')danger
                        @elseif(($progress['status'] ?? '') === 'processing')primary
                        @else secondary @endif">
                        {{ ucfirst($progress['status'] ?? 'processing') }}
                    </span>
                </div>
                <div class="auto-refresh">
                    <i class="bi bi-arrow-clockwise"></i> Auto-refreshing every 5 seconds
                </div>
            </div>
        </div>

        @if(($progress['status'] ?? '') === 'completed' && ($progress['failed'] ?? 0) > 0)
        <div class="mt-4">
            <div class="alert alert-warning">
                <strong>Some messages failed to send.</strong> You can retry failed messages.
            </div>
            <form method="POST" action="{{ route('communication.send.whatsapp.retry') }}">
                @csrf
                <input type="hidden" name="tracking_id" value="{{ $trackingId }}">
                <button type="submit" class="btn btn-warning">
                    <i class="bi bi-arrow-repeat"></i> Retry Failed Messages ({{ $progress['failed'] ?? 0 }})
                </button>
            </form>
        </div>
        @endif

        @if(($progress['status'] ?? '') === 'processing')
        <div class="mt-4 text-center">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Processing...</span>
            </div>
            <p class="mt-2 text-muted">Processing messages in background. This page will auto-refresh.</p>
        </div>
        @endif
    </div>
</div>

<script>
// Auto-refresh every 5 seconds if still processing
@if(($progress['status'] ?? '') === 'processing')
setTimeout(function() {
    window.location.reload();
}, 5000);
@endif
</script>
@endsection
