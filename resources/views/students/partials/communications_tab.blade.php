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
            <p class="text-muted small mb-0">Messages successfully sent to this student's contacts, and items waiting to send.</p>
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
                            <h6 class="small text-muted text-uppercase">Fee reminders</h6>
                            <ul class="list-group list-group-flush mb-3">
                                @foreach($upcomingReminders as $reminder)
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between gap-2">
                                            <div>
                                                <span class="badge bg-{{ $reminder->status === 'paused' ? 'secondary' : 'warning' }} text-dark">{{ ucfirst($reminder->status) }}</span>
                                                <span class="ms-1">{{ ucfirst($reminder->fee_reminder_type ?? 'reminder') }}</span>
                                                @if($reminder->due_date)
                                                    <div class="small text-muted">Due {{ $reminder->due_date->format('d M Y') }}</div>
                                                @endif
                                            </div>
                                            <div class="small text-muted text-end">
                                                {{ is_array($reminder->channels) ? implode(', ', $reminder->channels) : ($reminder->channel ?? '—') }}
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        @if($upcomingScheduled->isNotEmpty())
                            <h6 class="small text-muted text-uppercase">Scheduled fee communications</h6>
                            <ul class="list-group list-group-flush">
                                @foreach($upcomingScheduled as $sched)
                                    @php
                                        $nextAt = $sched->recurrence_next_at ?? $sched->send_at;
                                    @endphp
                                    <li class="list-group-item px-0">
                                        <div class="d-flex justify-content-between gap-2">
                                            <div>
                                                <span class="badge bg-{{ $sched->status === 'paused' ? 'secondary' : 'info' }}">{{ ucfirst($sched->status) }}</span>
                                                {{ $sched->template->title ?? 'Custom message' }}
                                                <div class="small text-muted">
                                                    {{ $sched->recurrence_description }}
                                                    @if($nextAt)
                                                        · Next {{ $nextAt->format('d M Y H:i') }}
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="small text-muted text-end">
                                                {{ implode(', ', $sched->channels ?? []) }}
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
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
                            <table class="table table-sm table-modern mb-0">
                                <thead>
                                    <tr>
                                        <th>When</th>
                                        <th>Channel</th>
                                        <th>Title</th>
                                        <th>Contact</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($commHistory as $log)
                                        <tr>
                                            <td class="text-nowrap small">
                                                {{ ($log->sent_at ?? $log->created_at)?->format('d M Y H:i') }}
                                            </td>
                                            <td><span class="badge bg-light text-dark">{{ strtoupper($log->channel) }}</span></td>
                                            <td class="small">{{ $log->title ?: \Illuminate\Support\Str::limit(strip_tags($log->message), 40) }}</td>
                                            <td class="small text-muted">{{ $log->contact }}</td>
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
</div>
