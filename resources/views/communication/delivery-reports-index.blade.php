@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Communication / Delivery Reports</div>
                <h1>Delivery Reports</h1>
                <p class="text-muted mb-0">
                    Recent bulk send reports (SMS, Email, WhatsApp). Reports expire after 2 hours.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('communication.logs') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-clock-history"></i> Communication Logs
                </a>
                <a href="{{ route('communication.send.sms') }}" class="btn btn-settings-primary">
                    <i class="bi bi-chat"></i> Send SMS
                </a>
            </div>
        </div>

        <div class="settings-card mt-3">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Reports</h5>
            </div>
            <div class="card-body p-0">
                @if(empty($recent))
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1"></i>
                        <p class="mt-2 mb-0">No recent delivery reports.</p>
                        <p class="small">Reports appear here after you send SMS, Email, or bulk WhatsApp messages.</p>
                        <p class="small mt-2">
                            <strong>Note:</strong> Bulk SMS to many recipients may time out (504) before completing. A "View full report" link appears in the success message after each send. We're moving bulk SMS to background jobs to fix timeouts.
                        </p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date / Time</th>
                                    <th>Channel</th>
                                    <th>Summary</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recent as $r)
                                    <tr>
                                        <td class="small">{{ \Carbon\Carbon::parse($r['created_at'] ?? now())->format('M d, Y H:i') }}</td>
                                        <td><span class="badge bg-secondary">{{ ucfirst($r['channel'] ?? '—') }}</span></td>
                                        <td>
                                            @php $s = $r['summary'] ?? []; @endphp
                                            <span class="text-success">{{ $s['sent'] ?? 0 }} sent</span>
                                            @if(($s['failed'] ?? 0) > 0)
                                                <span class="text-danger ms-1">{{ $s['failed'] }} failed</span>
                                            @endif
                                            @if(($s['skipped'] ?? 0) > 0)
                                                <span class="text-warning ms-1">{{ $s['skipped'] }} skipped</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('communication.delivery-report', $r['id']) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-box-arrow-up-right"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
