@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Communication / Delivery Report</div>
                <h1>Delivery Report</h1>
                <p class="text-muted mb-0">
                    {{ ucfirst($report['channel']) }} – 
                    {{ $report['summary']['sent'] ?? 0 }} sent, 
                    {{ $report['summary']['failed'] ?? 0 }} failed, 
                    {{ $report['summary']['skipped'] ?? 0 }} skipped
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('communication.logs') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-clock-history"></i> View Logs
                </a>
                <button type="button" class="btn btn-settings-primary" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print
                </button>
            </div>
        </div>

        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-people"></i> Recipients</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>{{ $report['channel'] === 'email' ? 'Email' : 'Phone' }}</th>
                                <th>Status</th>
                                <th>Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($report['recipients'] as $i => $row)
                                <tr class="{{ ($row['status'] ?? '') === 'failed' ? 'table-danger' : (($row['status'] ?? '') === 'skipped' ? 'table-warning' : '') }}">
                                    <td>{{ $i + 1 }}</td>
                                    <td>{{ $row['name'] ?? '-' }}</td>
                                    <td>{{ $row['contact'] ?? '-' }}</td>
                                    <td>
                                        @php
                                            $status = $row['status'] ?? 'unknown';
                                            $badges = [
                                                'sent' => 'success',
                                                'failed' => 'danger',
                                                'skipped' => 'warning',
                                            ];
                                            $badge = $badges[$status] ?? 'secondary';
                                        @endphp
                                        <span class="badge bg-{{ $badge }}">{{ ucfirst($status) }}</span>
                                    </td>
                                    <td class="small text-muted">{{ $row['reason'] ?? '' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if(empty($report['recipients']))
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2">No recipients in this report.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
