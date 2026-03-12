@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Conversations & Campaigns',
            'icon' => 'bi bi-chat-square-text',
            'subtitle' => 'Track bulk SMS, WhatsApp, and email campaigns. See exactly which messages were sent, failed, or skipped.',
            'actions' => ''
        ])

        {{-- Filters --}}
        <form method="GET" action="{{ route('communication.conversations') }}" class="mb-4">
            <div class="row g-2">
                <div class="col-md-2">
                    <select name="channel" class="form-select form-select-sm">
                        <option value="">All channels</option>
                        <option value="sms" {{ ($channelFilter ?? '') === 'sms' ? 'selected' : '' }}>SMS</option>
                        <option value="email" {{ ($channelFilter ?? '') === 'email' ? 'selected' : '' }}>Email</option>
                        <option value="whatsapp" {{ ($channelFilter ?? '') === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="text" name="tracking_id" class="form-control form-control-sm" placeholder="Tracking ID" value="{{ $trackingIdFilter ?? '' }}">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom ?? '' }}" placeholder="From">
                </div>
                <div class="col-md-2">
                    <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo ?? '' }}" placeholder="To">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-settings-primary btn-sm"><i class="bi bi-search"></i> Filter</button>
                    <a href="{{ route('communication.conversations') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </div>
        </form>

        <div class="settings-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tracking ID</th>
                                <th>Channel</th>
                                <th>Title</th>
                                <th>Total</th>
                                <th>Sent</th>
                                <th>Failed</th>
                                <th>Date</th>
                                <th style="width:1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($campaigns as $c)
                                @php
                                    $sent = (int) ($c->sent_count ?? 0);
                                    $failed = (int) ($c->failed_count ?? 0);
                                    $total = (int) ($c->total ?? 0);
                                    $firstSent = $c->first_sent_at ? \Carbon\Carbon::parse($c->first_sent_at)->format('M d, Y H:i') : '-';
                                @endphp
                                <tr>
                                    <td><code class="small">{{ Str::limit($c->tracking_id, 28) }}</code></td>
                                    <td><span class="pill-badge">{{ strtoupper($c->channel) }}</span></td>
                                    <td><strong>{{ Str::limit($c->title ?? 'Untitled', 40) }}</strong></td>
                                    <td>{{ $total }}</td>
                                    <td><span class="text-success">{{ $sent }}</span></td>
                                    <td><span class="text-danger">{{ $failed }}</span></td>
                                    <td><small>{{ $firstSent }}</small></td>
                                    <td>
                                        <a href="{{ route('communication.conversations', array_merge(request()->query(), ['view' => $c->tracking_id])) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-eye"></i> View details
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
                                        <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="mt-3 mb-0">No bulk campaigns found. Campaigns appear here after sending to 10+ recipients.</p>
                                        <p class="mb-0 small">Send SMS, Email, or WhatsApp to a large group to see tracking here.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($campaigns->hasPages())
                <div class="p-3">
                    {{ $campaigns->links() }}
                </div>
                @endif
            </div>
        </div>

        {{-- Detail view: list of recipients for selected campaign --}}
        @if($selectedTrackingId && $campaignLogs->isNotEmpty())
        <div class="settings-card mt-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i> Recipients for {{ $selectedTrackingId }}</h5>
                <a href="{{ route('communication.conversations', request()->except('view')) }}" class="btn btn-sm btn-outline-secondary">Close</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Message preview</th>
                                <th>Sent at</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($campaignLogs as $log)
                                @php
                                    $statusCls = match(strtolower($log->status ?? '')) {
                                        'sent' => 'bg-success-subtle text-success',
                                        'failed' => 'bg-danger-subtle text-danger',
                                        default => 'bg-secondary-subtle text-secondary'
                                    };
                                @endphp
                                <tr>
                                    <td>{{ $log->contact }}</td>
                                    <td><span class="pill-badge {{ $statusCls }}">{{ ucfirst($log->status ?? '-') }}</span></td>
                                    <td><small class="text-muted">{{ Str::limit(strip_tags($log->message ?? '-'), 60) }}</small></td>
                                    <td><small>{{ $log->sent_at?->format('M d, H:i') ?? $log->created_at?->format('M d, H:i') ?? '-' }}</small></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
