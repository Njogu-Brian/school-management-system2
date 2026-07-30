{{-- resources/views/communication/pending-jobs.blade.php --}}
@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Bulk jobs',
            'icon' => 'bi bi-list-task',
            'subtitle' => 'Upcoming, running, paused, and completed SMS / email / WhatsApp jobs — including fee schedules',
            'actions' => ''
        ])

        @include('communication.partials.flash')

        @if(!empty($showResume))
            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <strong>Paused SMS work ready to resume.</strong>
                    Top up SMS credits first, then resume to retry paused finance/scheduled messages.
                    @if(($pausedSmsCount ?? 0) > 0)
                        <strong>{{ $pausedSmsCount }}</strong> individual SMS message(s) will be retried.
                    @endif
                    Attendance SMS that failed for credits are not retried.
                </div>
                <form method="POST" action="{{ route('communication.resume') }}">
                    @csrf
                    <button type="submit" class="btn btn-warning btn-sm">
                        <i class="bi bi-play-circle"></i> Resume all
                    </button>
                </form>
            </div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-body">
                <form method="GET" action="{{ route('communication.pending-jobs') }}" class="d-flex gap-2 align-items-center flex-wrap">
                    <label class="fw-semibold mb-0">Status</label>
                    <select name="status" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="all" @selected($statusFilter === 'all')>All</option>
                        <option value="pending" @selected($statusFilter === 'pending')>Pending / scheduled</option>
                        <option value="running" @selected($statusFilter === 'running')>Running</option>
                        <option value="paused" @selected($statusFilter === 'paused')>Paused</option>
                        <option value="completed" @selected($statusFilter === 'completed')>Completed</option>
                        <option value="cancelled" @selected($statusFilter === 'cancelled')>Cancelled</option>
                        <option value="failed" @selected($statusFilter === 'failed')>Failed</option>
                    </select>
                    <label class="fw-semibold mb-0">Channel</label>
                    <select name="channel" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="all" @selected(($channelFilter ?? 'all') === 'all')>All</option>
                        <option value="sms" @selected(($channelFilter ?? '') === 'sms')>SMS</option>
                        <option value="email" @selected(($channelFilter ?? '') === 'email')>Email</option>
                        <option value="whatsapp" @selected(($channelFilter ?? '') === 'whatsapp')>WhatsApp</option>
                    </select>
                    <label class="fw-semibold mb-0">Source</label>
                    <select name="source" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
                        <option value="all" @selected(($sourceFilter ?? 'all') === 'all')>All</option>
                        <option value="manual_bulk" @selected(($sourceFilter ?? '') === 'manual_bulk')>Manual bulk</option>
                        <option value="scheduled_comm" @selected(($sourceFilter ?? '') === 'scheduled_comm')>Scheduled</option>
                        <option value="scheduled_fee" @selected(($sourceFilter ?? '') === 'scheduled_fee')>Scheduled fee</option>
                        <option value="fee_reminder" @selected(($sourceFilter ?? '') === 'fee_reminder')>Fee reminder</option>
                        <option value="payment" @selected(($sourceFilter ?? '') === 'payment')>Payment</option>
                    </select>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Job</th>
                                <th>Status</th>
                                <th>Channel</th>
                                <th>Source</th>
                                <th>Progress</th>
                                <th>When</th>
                                <th>By</th>
                                <th style="width:1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($jobs as $job)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $job->title ?: 'Untitled' }}</div>
                                        <small class="text-muted">#{{ $job->id }} · {{ \Illuminate\Support\Str::limit($job->tracking_id, 28) }}</small>
                                        @if($job->message)
                                            <div class="text-muted small">{{ \Illuminate\Support\Str::limit(strip_tags($job->message), 60) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $job->statusBadgeClass() }}">{{ ucfirst($job->status) }}</span>
                                        @if($job->pause_reason)
                                            <div class="small text-muted">{{ str_replace('_', ' ', $job->pause_reason) }}</div>
                                        @endif
                                    </td>
                                    <td><span class="text-uppercase small">{{ $job->channel }}</span></td>
                                    <td><span class="small">{{ str_replace('_', ' ', $job->source) }}</span></td>
                                    <td>
                                        <div class="small">
                                            {{ $job->recipient_count }} recipients
                                            @if($job->sent_count || $job->failed_count || $job->skipped_count)
                                                <br>Sent {{ $job->sent_count }} · Failed {{ $job->failed_count }} · Skipped {{ $job->skipped_count }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="small">
                                        @if($job->scheduled_at)
                                            <div>Sched: {{ $job->scheduled_at->format('d M Y H:i') }}</div>
                                        @endif
                                        <div>{{ $job->created_at?->format('d M Y H:i') }}</div>
                                    </td>
                                    <td class="small">{{ $job->createdBy->name ?? '—' }}</td>
                                    <td class="text-nowrap">
                                        <a href="{{ route('communication.pending-jobs.show', $job) }}" class="btn btn-sm btn-outline-secondary">Recipients</a>
                                        @if($job->isPausable())
                                            <form action="{{ route('communication.pending-jobs.pause', $job) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-warning" type="submit">Pause</button>
                                            </form>
                                        @endif
                                        @if($job->isResumable())
                                            <form action="{{ route('communication.pending-jobs.resume-job', $job) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-success" type="submit">Resume</button>
                                            </form>
                                        @endif
                                        @if($job->isCancellable())
                                            <form action="{{ route('communication.pending-jobs.cancel', $job->id) }}" method="POST" class="d-inline"
                                                  onsubmit="return confirm('Cancel remaining recipients for this job?');">
                                                @csrf
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Stop</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted p-4">No communication jobs found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($jobs->hasPages())
                <div class="card-footer">{{ $jobs->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
