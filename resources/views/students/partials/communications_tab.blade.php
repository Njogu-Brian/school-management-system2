@php
    $commHistory = $communicationHistory ?? collect();
    $commUpcoming = $communicationUpcoming ?? [];
    $upcomingReminders = $commUpcoming['fee_reminders'] ?? collect();
    $upcomingScheduled = $commUpcoming['scheduled_fee_communications'] ?? collect();
    $commPaused = $commUpcoming['communications_paused'] ?? false;
@endphp
<div class="tab-pane fade" id="communications" role="tabpanel">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 class="mb-0">Communications</h6>
            <p class="text-muted small mb-0">Full message text for items sent and waiting to send.</p>
        </div>
        <a href="{{ route('communication.queues') }}" class="btn btn-sm btn-ghost-strong">
            <i class="bi bi-hourglass-split"></i> All queues
        </a>
    </div>

    @if($commPaused)
        <div class="alert alert-warning">
            <i class="bi bi-pause-circle"></i>
            <strong>Communications paused</strong> (insufficient SMS credits).
            Scheduled items for this student are on hold until an admin resumes sending.
            <a href="{{ route('communication.queues') }}" class="alert-link">Open queues</a>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="settings-card h-100">
                <div class="card-header">
                    <h6 class="mb-0">Upcoming / queued</h6>
                </div>
                <div class="card-body">
                    @if($upcomingReminders->isEmpty() && $upcomingScheduled->isEmpty())
                        <p class="text-muted mb-0">No pending fee reminders or scheduled fee messages for this student.</p>
                    @else
                        @if($upcomingReminders->isNotEmpty())
                            <h6 class="small text-muted text-uppercase mb-2">Fee reminders</h6>
                            @foreach($upcomingReminders as $reminder)
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
                                        <div>
                                            <span class="badge bg-{{ $reminder->status === 'paused' ? 'secondary' : 'warning' }} text-dark">{{ ucfirst($reminder->status) }}</span>
                                            <span class="ms-1 fw-semibold">{{ ucfirst($reminder->fee_reminder_type ?? 'reminder') }}</span>
                                            @if($reminder->due_date)
                                                <span class="small text-muted">· Due {{ $reminder->due_date->format('d M Y') }}</span>
                                            @endif
                                        </div>
                                        <span class="small text-muted">
                                            {{ is_array($reminder->channels) ? implode(', ', $reminder->channels) : ($reminder->channel ?? '—') }}
                                        </span>
                                    </div>
                                    <div class="small text-muted mb-1">Message preview</div>
                                    <pre class="comm-message-preview mb-0">{{ $reminder->preview_message ?? '' }}</pre>
                                </div>
                            @endforeach
                        @endif
                        @if($upcomingScheduled->isNotEmpty())
                            <h6 class="small text-muted text-uppercase mb-2">Scheduled fee communications</h6>
                            @foreach($upcomingScheduled as $sched)
                                @php $nextAt = $sched->recurrence_next_at ?? $sched->send_at; @endphp
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between gap-2 flex-wrap mb-2">
                                        <div>
                                            <span class="badge bg-{{ $sched->status === 'paused' ? 'secondary' : 'info' }}">{{ ucfirst($sched->status) }}</span>
                                            <span class="ms-1 fw-semibold">{{ $sched->template->title ?? 'Custom message' }}</span>
                                            <div class="small text-muted">
                                                {{ $sched->recurrence_description }}
                                                @if($nextAt)· Next {{ $nextAt->format('d M Y H:i') }}@endif
                                            </div>
                                        </div>
                                        <span class="small text-muted">{{ implode(', ', $sched->channels ?? []) }}</span>
                                    </div>
                                    <div class="small text-muted mb-1">Message preview (personalized for this student)</div>
                                    <pre class="comm-message-preview mb-0">{{ $sched->preview_message ?? '' }}</pre>
                                </div>
                            @endforeach
                        @endif
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="settings-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Sent successfully</h6>
                    <span class="input-chip">{{ $commHistory->count() }} shown</span>
                </div>
                <div class="card-body p-0">
                    @if($commHistory->isEmpty())
                        <p class="text-muted p-3 mb-0">No successful communication logs found for this student yet.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm table-modern mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>When</th>
                                        <th>Channel</th>
                                        <th>Title</th>
                                        <th>Message</th>
                                        <th>Contact</th>
                                        <th style="width:1%;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($commHistory as $log)
                                        @php
                                            $safeTitle = $log->title ?: \Illuminate\Support\Str::limit(strip_tags($log->message ?? ''), 40);
                                            $sentAt = ($log->sent_at ?? $log->created_at)?->format('d M Y H:i');
                                            $msgPreview = \Illuminate\Support\Str::limit(strip_tags($log->message ?? ''), 160);
                                        @endphp
                                        <tr>
                                            <td class="text-nowrap small">{{ $sentAt }}</td>
                                            <td><span class="badge bg-light text-dark">{{ strtoupper($log->channel) }}</span></td>
                                            <td class="small">{{ $safeTitle }}</td>
                                            <td class="small" style="min-width: 200px; max-width: 320px;">
                                                <span class="text-muted">{{ $msgPreview }}</span>
                                            </td>
                                            <td class="small text-muted text-nowrap">{{ $log->contact }}</td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-ghost-strong py-0"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#studentCommSent{{ $log->id }}"
                                                        title="View full message">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @foreach($commHistory as $log)
                            @php
                                $safeTitle = $log->title ?: 'Message';
                                $sentAt = ($log->sent_at ?? $log->created_at)?->format('d M Y H:i');
                            @endphp
                            <div class="modal fade" id="studentCommSent{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">{{ $safeTitle }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <dl class="row small mb-3">
                                                <dt class="col-sm-3">Sent</dt>
                                                <dd class="col-sm-9">{{ $sentAt }}</dd>
                                                <dt class="col-sm-3">Channel</dt>
                                                <dd class="col-sm-9">{{ strtoupper($log->channel) }}</dd>
                                                <dt class="col-sm-3">Contact</dt>
                                                <dd class="col-sm-9">{{ $log->contact ?? '—' }}</dd>
                                                @if($log->payment)
                                                    <dt class="col-sm-3">Receipt</dt>
                                                    <dd class="col-sm-9">{{ $log->payment->receipt_number ?? '—' }}</dd>
                                                @endif
                                            </dl>
                                            <div class="fw-semibold mb-2">Message</div>
                                            <pre class="comm-message-preview mb-0">{{ $log->message }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .comm-message-preview {
        white-space: pre-wrap;
        word-break: break-word;
        font-size: 0.8125rem;
        line-height: 1.45;
        background: var(--bs-light, #f8f9fa);
        border: 1px solid var(--bs-border-color, #dee2e6);
        border-radius: 0.375rem;
        padding: 0.75rem 1rem;
        max-height: 280px;
        overflow-y: auto;
    }
</style>
@endpush
@endonce
