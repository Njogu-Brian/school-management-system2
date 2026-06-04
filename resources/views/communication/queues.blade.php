@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Communication Queues',
            'icon' => 'bi bi-hourglass-split',
            'subtitle' => 'Upcoming sends, paused items, and active queue jobs',
            'actions' => '<a href="' . route('communication.pending-jobs') . '" class="btn btn-ghost-strong btn-sm"><i class="bi bi-list-task"></i> Bulk jobs</a>'
        ])

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif

        @if($showResume ?? $paused)
            <div class="settings-card mb-3 border-warning">
                <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <div>
                        <h5 class="text-warning mb-1"><i class="bi bi-pause-circle"></i>
                            @if($paused)
                                Communications paused
                            @else
                                Paused SMS work ready to resume
                            @endif
                        </h5>
                        <p class="mb-0 text-muted">
                            @if($paused)
                                Insufficient SMS credits were detected. Scheduled sends and bulk jobs are on hold (not cancelled).
                            @else
                                Top up SMS credits first, then resume to retry paused messages and continue bulk sends.
                            @endif
                            @if(($pausedSmsCount ?? 0) > 0)
                                <strong>{{ $pausedSmsCount }}</strong> individual SMS message(s) will be retried.
                            @endif
                            @if(($pausedBulkSmsCount ?? 0) > 0)
                                <strong>{{ $pausedBulkSmsCount }}</strong> bulk SMS job(s) will continue.
                            @endif
                            @if(($pausedReminderCount ?? 0) > 0)
                                <strong>{{ $pausedReminderCount }}</strong> fee reminder(s) are paused.
                            @endif
                            @if($pauseMeta && isset($pauseMeta['paused_at']))
                                Paused {{ \Carbon\Carbon::parse($pauseMeta['paused_at'])->diffForHumans() }}.
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('communication.resume') }}" onsubmit="return confirm('Resume paused communications? Ensure SMS credits are topped up first.');">
                        @csrf
                        <button type="submit" class="btn btn-warning">
                            <i class="bi bi-play-circle"></i> Resume all
                        </button>
                    </form>
                </div>
            </div>
        @else
            <div class="alert alert-info mb-3">
                <i class="bi bi-info-circle"></i>
                <strong>No pause active.</strong>
                Yellow <strong>Pending</strong> fee reminders below are normal — they will send automatically when due (daily scheduler).
                Resume appears here only after low SMS credits pause the system.
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Scheduled fee communications</h5>
                        <span class="input-chip">{{ $scheduledFee->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        @forelse($scheduledFee as $item)
                            <div class="border-bottom px-3 py-2">
                                <span class="badge bg-{{ $item->status === 'paused' ? 'secondary' : ($item->status === 'active' ? 'info' : 'warning') }}">{{ ucfirst($item->status) }}</span>
                                <strong class="ms-1">{{ $item->template->title ?? 'Custom' }}</strong>
                                <div class="small text-muted">
                                    Target: {{ $item->target }} · {{ $item->recurrence_description }}
                                    @php $next = $item->recurrence_next_at ?? $item->send_at; @endphp
                                    @if($next) · Next {{ $next->format('d M Y H:i') }} @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted p-3 mb-0">None scheduled.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Fee reminders (pending)</h5>
                        <span class="input-chip">{{ $feeReminders->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        @forelse($feeReminders as $reminder)
                            <div class="border-bottom px-3 py-2">
                                <span class="badge bg-{{ $reminder->status === 'paused' ? 'secondary' : 'warning' }} text-dark">{{ ucfirst($reminder->status) }}</span>
                                <strong class="ms-1">{{ $reminder->student->full_name ?? 'Student #'.$reminder->student_id }}</strong>
                                <div class="small text-muted">
                                    {{ ucfirst($reminder->fee_reminder_type ?? 'reminder') }}
                                    @if($reminder->due_date) · Due {{ $reminder->due_date->format('d M Y') }} @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-muted p-3 mb-0">None pending.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Scheduled SMS / email / WhatsApp</h5>
                        <span class="input-chip">{{ $scheduledComms->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        @forelse($scheduledComms as $item)
                            <div class="border-bottom px-3 py-2">
                                <span class="badge bg-{{ $item->status === 'paused' ? 'secondary' : 'warning' }} text-dark">{{ ucfirst($item->status) }}</span>
                                {{ $item->template->title ?? $item->type }}
                                <div class="small text-muted">
                                    Send {{ $item->send_at?->format('d M Y H:i') ?? '—' }} · {{ $item->target }}
                                </div>
                            </div>
                        @empty
                            <p class="text-muted p-3 mb-0">None scheduled.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Laravel queue (recent)</h5>
                        <span class="input-chip">{{ $queueJobs->count() }}</span>
                    </div>
                    <div class="card-body p-0">
                        @forelse($queueJobs as $job)
                            <div class="border-bottom px-3 py-2 small">
                                Job #{{ $job->id }} · queue {{ $job->queue }}
                                · available {{ $job->available_at ? \Carbon\Carbon::createFromTimestamp($job->available_at)->format('d M H:i') : '—' }}
                            </div>
                        @empty
                            <p class="text-muted p-3 mb-0">No jobs waiting in the default queue.</p>
                        @endforelse
                        <div class="p-3">
                            <a href="{{ route('communication.pending-jobs') }}">View bulk communication jobs →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
