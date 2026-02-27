@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Communication / SMS DLR Report</div>
                <h1>HostPinnacle DLR Reconciliation</h1>
                <p class="text-muted mb-0">Updated {{ $updated }} log(s) from DLR. {{ count($inDlrDelivered) }} delivered, {{ count($inDlrFailed) }} failed at HP, {{ count($notInDlr) }} not in DLR (never reached HostPinnacle).</p>
            </div>
            <a href="{{ route('communication.sms-dlr') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Upload another DLR
            </a>
        </div>

        @if(count($inDlrFailed) > 0)
        <div class="settings-card mt-3">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0"><i class="bi bi-x-circle"></i> Failed at HostPinnacle ({{ count($inDlrFailed) }})</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Contact</th><th>Internal status</th><th>HP status</th><th>Cause</th><th>Sent at</th></tr>
                        </thead>
                        <tbody>
                            @foreach($inDlrFailed as $r)
                            <tr>
                                <td>{{ $r['contact'] }}</td>
                                <td><span class="badge bg-secondary">{{ $r['internal_status'] }}</span></td>
                                <td><span class="badge bg-danger">{{ $r['hp_status'] }}</span></td>
                                <td>{{ $r['hp_cause'] ?? '-' }}</td>
                                <td>{{ $r['sent_at'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if(count($notInDlr) > 0)
        <div class="settings-card mt-3">
            <div class="card-header bg-warning">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Not in DLR – did not reach HostPinnacle ({{ count($notInDlr) }})</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr><th>Contact</th><th>Status</th><th>Note</th><th>Sent at</th></tr>
                        </thead>
                        <tbody>
                            @foreach($notInDlr as $r)
                            <tr>
                                <td>{{ $r['contact'] }}</td>
                                <td><span class="badge bg-secondary">{{ $r['internal_status'] }}</span></td>
                                <td class="text-muted small">{{ $r['reason'] ?? '-' }}</td>
                                <td>{{ $r['sent_at'] }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif

        @if(count($inDlrDelivered) > 0)
        <div class="settings-card mt-3">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle"></i> Delivered at HostPinnacle ({{ count($inDlrDelivered) }})</h5>
            </div>
            <div class="card-body p-0">
                <details>
                    <summary class="p-3">Click to expand</summary>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>Contact</th><th>HP status</th><th>Sent at</th></tr></thead>
                            <tbody>
                                @foreach($inDlrDelivered as $r)
                                <tr><td>{{ $r['contact'] }}</td><td><span class="badge bg-success">{{ $r['hp_status'] }}</span></td><td>{{ $r['sent_at'] }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
