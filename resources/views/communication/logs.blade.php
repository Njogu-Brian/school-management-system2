{{-- resources/views/communication/logs.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('communication.partials.header', [
        'title' => 'Email & SMS Logs',
        'icon' => 'bi bi-journal-text',
        'subtitle' => 'View all communication logs and delivery status',
        'actions' => ''
    ])

    <div class="comm-card comm-animate">
        <div class="comm-card-body">
            <div class="table-responsive">
                <table class="table comm-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Target</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Sent At</th>
                            <th>Message</th>
                            <th style="width:1%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            @php
                                // Prefer provider_status (from delivery reports), fallback to app status
                                $status = strtolower($log->provider_status ?: $log->status);
                                $cls = match($status) {
                                    'delivered','sent','success' => 'bg-success',
                                    'pending','queued'           => 'bg-warning text-dark',
                                    'blacklisted','failed','undelivered','rejected' => 'bg-danger',
                                    default => 'bg-secondary'
                                };

                                // Title fallback if missing
                                $safeTitle = $log->title ?: \Illuminate\Support\Str::limit(strip_tags($log->message), 40, '…');

                                // Human time
                                $sentAt = $log->sent_at
                                    ? \Illuminate\Support\Carbon::parse($log->sent_at)->format('Y-m-d H:i')
                                    : '-';
                            @endphp

                            <tr>
                                <td class="text-nowrap"><strong>{{ $safeTitle }}</strong></td>
                                <td><span class="badge bg-secondary">{{ ucfirst($log->recipient_type ?? '-') }}</span></td>
                                <td><span class="badge bg-info">{{ strtoupper($log->channel) }}</span></td>
                                <td><span class="badge {{ $cls }}">{{ ucfirst($status) }}</span></td>
                                <td class="text-nowrap"><small>{{ $sentAt }}</small></td>
                                <td class="text-truncate" style="max-width: 380px;">
                                    <small class="text-muted">{{ \Illuminate\Support\Str::limit(strip_tags($log->message), 90, '…') }}</small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-comm-outline"
                                            data-bs-toggle="modal"
                                            data-bs-target="#msg{{ $log->id }}">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>

                    <!-- Details Modal -->
                    <div class="modal fade" id="msg{{ $log->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Message Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <dl class="row">
                                        <dt class="col-sm-3">Title</dt>
                                        <dd class="col-sm-9">{{ $safeTitle }}</dd>

                                        <dt class="col-sm-3">Recipient</dt>
                                        <dd class="col-sm-9">{{ $log->contact ?? '-' }}</dd>

                                        <dt class="col-sm-3">Target</dt>
                                        <dd class="col-sm-9">{{ ucfirst($log->recipient_type ?? '-') }}</dd>

                                        <dt class="col-sm-3">Channel</dt>
                                        <dd class="col-sm-9">{{ strtoupper($log->channel) }}</dd>

                                        <dt class="col-sm-3">Status</dt>
                                        <dd class="col-sm-9">
                                            {{ ucfirst($status) }}
                                            @if(!empty($log->provider_status))
                                                <small class="text-muted">({{ $log->provider_status }})</small>
                                            @endif>
                                        </dd>

                                        <dt class="col-sm-3">Sent At</dt>
                                        <dd class="col-sm-9">{{ $sentAt }}</dd>

                                        @if(!empty($log->delivered_at))
                                            <dt class="col-sm-3">Delivered At</dt>
                                            <dd class="col-sm-9">
                                                {{ \Illuminate\Support\Carbon::parse($log->delivered_at)->format('Y-m-d H:i') }}
                                            </dd>
                                        @endif

                                        @if(!empty($log->provider_id))
                                            <dt class="col-sm-3">Provider Msg ID</dt>
                                            <dd class="col-sm-9">{{ $log->provider_id }}</dd>
                                        @endif
                                    </dl>

                                    <hr>
                                    <div class="fw-semibold mb-2">Message</div>
                                    <pre class="mb-0" style="white-space:pre-wrap">{{ $log->message }}</pre>

                                    @if(!empty($log->response))
                                        <hr>
                                        <div class="fw-semibold mb-2">Provider Response (raw)</div>
                                        <pre class="mb-0">
{{ is_string($log->response) ? $log->response : json_encode($log->response, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) }}
                                        </pre>
                                    @endif
                                </div>
                                <div class="modal-footer">
                                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                    <p class="mt-3 mb-0">No communications found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($logs->hasPages())
            <div class="mt-3">
                {{ $logs->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
