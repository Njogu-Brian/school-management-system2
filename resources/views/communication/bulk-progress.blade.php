@extends('layouts.app')

@push('styles')
<style>
.progress-container { max-width: 800px; margin: 0 auto; }
.progress-card { background: white; border-radius: 8px; padding: 2rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.stat-card { background: #f8f9fa; border-radius: 6px; padding: 1.5rem; text-align: center; }
.stat-value { font-size: 2rem; font-weight: bold; margin: 0.5rem 0; }
.stat-label { color: #6c757d; font-size: 0.875rem; }
.auto-refresh { color: #6c757d; font-size: 0.875rem; }
</style>
@endpush

@section('content')
<div class="progress-container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1">Bulk {{ ucfirst($channel) }} Send Progress</h1>
            <p class="text-muted mb-0">Tracking ID: <code>{{ $trackingId }}</code></p>
        </div>
        <a href="{{ route($backRoute) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> {{ $backLabel }}
        </a>
    </div>

    <div class="progress-card">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Total</div>
                    <div class="stat-value text-primary">{{ $progress['total'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Sent</div>
                    <div class="stat-value text-success">{{ $progress['sent'] ?? 0 }}</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-label">Failed</div>
                    <div class="stat-value text-danger">{{ $progress['failed'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex justify-content-between mb-2">
                <span class="fw-semibold">Progress</span>
                <span class="text-muted">{{ $progress['processed'] ?? 0 }} / {{ $progress['total'] ?? 0 }}</span>
            </div>
            @php
                $percentage = ($progress['total'] ?? 0) > 0
                    ? round((($progress['processed'] ?? 0) / ($progress['total'] ?? 1)) * 100)
                    : 0;
            @endphp
            <div class="progress" style="height: 30px;">
                <div class="progress-bar progress-bar-striped progress-bar-animated
                    @if(($progress['status'] ?? '') === 'completed') bg-success
                    @elseif(($progress['status'] ?? '') === 'failed') bg-danger
                    @else bg-primary @endif"
                    role="progressbar" style="width: {{ $percentage }}%">{{ $percentage }}%</div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center">
            <strong>Status:</strong>
            <span class="badge bg-@if(($progress['status'] ?? '') === 'completed')success
                @elseif(($progress['status'] ?? '') === 'failed')danger
                @elseif(($progress['status'] ?? '') === 'processing')primary
                @else secondary @endif">
                {{ ucfirst($progress['status'] ?? 'processing') }}
            </span>
            @if(($progress['status'] ?? '') === 'processing')
            <span class="auto-refresh"><i class="bi bi-arrow-clockwise"></i> Auto-refreshing every 5 seconds</span>
            @endif
        </div>

        @if(($progress['status'] ?? '') === 'completed')
        <div class="mt-4">
            @if($progress['report_id'] ?? null)
            <a href="{{ route('communication.delivery-report', $progress['report_id']) }}" target="_blank" rel="noopener" class="btn btn-settings-primary">
                <i class="bi bi-box-arrow-up-right"></i> View delivery report (recipients & status)
            </a>
            @endif
            <a href="{{ route('communication.conversations') }}?tracking_id={{ urlencode($trackingId) }}" class="btn btn-outline-primary ms-2">
                <i class="bi bi-chat-dots"></i> View in Conversations
            </a>
        </div>
        @endif

        @if(($progress['status'] ?? '') === 'processing')
        <div class="mt-4 text-center">
            <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Processing...</span></div>
            <p class="mt-2 text-muted">Processing in background. This page will auto-refresh.</p>
        </div>
        @endif
    </div>
</div>

@if(($progress['status'] ?? '') === 'processing')
<script>setTimeout(function(){ window.location.reload(); }, 5000);</script>
@endif
@endsection
