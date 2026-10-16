@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    <style>
        .bulk-progress-page .stat-card { background: var(--bs-tertiary-bg, #f8f9fa); border-radius: 8px; padding: 1rem 1.25rem; text-align: center; height: 100%; }
        .bulk-progress-page .stat-value { font-size: 1.75rem; font-weight: 700; margin: 0.25rem 0 0; }
        .bulk-progress-page .stat-label { color: var(--bs-secondary-color, #6c757d); font-size: 0.8rem; text-transform: uppercase; letter-spacing: .04em; }
        .bulk-progress-page .recipient-table td, .bulk-progress-page .recipient-table th { vertical-align: middle; }
        .bulk-progress-page .progress { height: 1.5rem; }
    </style>
@endpush

@section('content')
@php
    $uiStatus = $progress['status'] ?? 'processing';
    $statusClass = match ($uiStatus) {
        'completed' => 'success',
        'failed' => 'danger',
        'paused' => 'warning',
        'cancelled' => 'secondary',
        'processing', 'queued' => 'primary',
        default => 'secondary',
    };
    $percentage = (int) ($progress['percentage'] ?? 0);
@endphp
<div class="settings-page bulk-progress-page" id="bulk-progress-root"
     data-url="{{ route('communication.bulk-progress.data') }}"
     data-tracking="{{ $trackingId }}"
     data-channel="{{ $channel }}">
    <div class="settings-shell">
        @include('communication.partials.header', [
            'title' => 'Bulk ' . ucfirst($channel) . ' progress',
            'icon' => 'bi bi-send',
            'subtitle' => 'Tracking ID ' . $trackingId,
            'actions' => '<a href="' . route($backRoute) . '" class="btn btn-ghost-strong btn-sm"><i class="bi bi-arrow-left"></i> ' . e($backLabel) . '</a>'
                . '<a href="' . route('communication.pending-jobs') . '" class="btn btn-ghost-strong btn-sm">All bulk jobs</a>'
        ])

        @include('communication.partials.flash')

        <div class="settings-card mb-3">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <div>
                        <strong>Status:</strong>
                        <span class="badge bg-{{ $statusClass }} {{ $uiStatus === 'paused' ? 'text-dark' : '' }}" id="progress-status-badge">{{ ucfirst($uiStatus) }}</span>
                        @if(!empty($progress['pause_reason']))
                            <span class="text-muted small ms-1" id="progress-pause-reason">{{ str_replace('_', ' ', $progress['pause_reason']) }}</span>
                        @else
                            <span class="text-muted small ms-1" id="progress-pause-reason"></span>
                        @endif
                    </div>
                    <div class="d-flex flex-wrap gap-2" id="progress-actions">
                        @if($communicationJob)
                            <form action="{{ route('communication.pending-jobs.pause', $communicationJob) }}" method="POST" class="{{ $communicationJob->isPausable() ? '' : 'd-none' }}" id="pause-form">
                                @csrf
                                <button class="btn btn-sm btn-outline-warning" type="submit"><i class="bi bi-pause-circle"></i> Pause</button>
                            </form>
                            <form action="{{ route('communication.pending-jobs.resume-job', $communicationJob) }}" method="POST" class="{{ $communicationJob->isResumable() ? '' : 'd-none' }}" id="resume-form">
                                @csrf
                                <button class="btn btn-sm btn-outline-success" type="submit"><i class="bi bi-play-circle"></i> Resume</button>
                            </form>
                            <form action="{{ route('communication.pending-jobs.cancel', $communicationJob->id) }}" method="POST" class="{{ $communicationJob->isCancellable() ? '' : 'd-none' }}" id="stop-form"
                                  onsubmit="return confirm('Stop this job? Messages already sent are kept.');">
                                @csrf
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-stop-circle"></i> Stop</button>
                            </form>
                            <a href="{{ route('communication.pending-jobs.show', $communicationJob) }}" class="btn btn-sm btn-outline-secondary">Open job</a>
                        @endif
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6 col-md">
                        <div class="stat-card">
                            <div class="stat-label">Total</div>
                            <div class="stat-value text-primary" id="stat-total">{{ $progress['total'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="stat-card">
                            <div class="stat-label">Sent</div>
                            <div class="stat-value text-success" id="stat-sent">{{ $progress['sent'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="stat-card">
                            <div class="stat-label">Failed</div>
                            <div class="stat-value text-danger" id="stat-failed">{{ $progress['failed'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="stat-card">
                            <div class="stat-label">Pending</div>
                            <div class="stat-value" id="stat-pending">{{ $progress['pending'] ?? 0 }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-md">
                        <div class="stat-card">
                            <div class="stat-label">Skipped</div>
                            <div class="stat-value text-warning" id="stat-skipped">{{ $progress['skipped'] ?? 0 }}</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-semibold">Progress</span>
                    <span class="text-muted" id="progress-fraction">{{ $progress['processed'] ?? 0 }} / {{ $progress['total'] ?? 0 }}</span>
                </div>
                <div class="progress mb-2">
                    <div id="progress-bar" class="progress-bar {{ in_array($uiStatus, ['processing', 'queued'], true) ? 'progress-bar-striped progress-bar-animated' : '' }} bg-{{ $statusClass }}"
                         role="progressbar" style="width: {{ $percentage }}%">{{ $percentage }}%</div>
                </div>
                <div class="small text-muted" id="progress-note">
                    @if(in_array($uiStatus, ['processing', 'queued'], true))
                        Sending in the background. Recipient status refreshes automatically. Pause stops after the message currently going out.
                    @elseif($uiStatus === 'paused')
                        This job is paused. Resume continues with recipients who have not been sent yet.
                    @elseif(!empty($progress['message']))
                        {{ $progress['message'] }}
                    @endif
                </div>

                <div class="mt-3 d-flex flex-wrap gap-2 {{ ($progress['report_id'] ?? null) ? '' : 'd-none' }}" id="report-links">
                    @if($progress['report_id'] ?? null)
                        <a href="{{ route('communication.delivery-report', $progress['report_id']) }}" target="_blank" rel="noopener" class="btn btn-sm btn-settings-primary" id="report-link">
                            <i class="bi bi-box-arrow-up-right"></i> Delivery report
                        </a>
                    @else
                        <a href="#" target="_blank" rel="noopener" class="btn btn-sm btn-settings-primary d-none" id="report-link">
                            <i class="bi bi-box-arrow-up-right"></i> Delivery report
                        </a>
                    @endif
                    <a href="{{ route('communication.conversations') }}?tracking_id={{ urlencode($trackingId) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-chat-dots"></i> Conversations
                    </a>
                </div>

                @if($channel === 'whatsapp')
                    <form method="POST" action="{{ route('communication.send.whatsapp.retry') }}" class="mt-3 {{ (($progress['failed'] ?? 0) > 0 && in_array($uiStatus, ['completed', 'failed', 'paused', 'cancelled'], true)) ? '' : 'd-none' }}" id="retry-form">
                        @csrf
                        <input type="hidden" name="tracking_id" value="{{ $trackingId }}">
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="bi bi-arrow-repeat"></i> Retry failed WhatsApp messages (<span id="retry-count">{{ $progress['failed'] ?? 0 }}</span>)
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="settings-card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h6 mb-0">Recipients</h2>
                    <div class="d-flex flex-wrap gap-2">
                        <select class="form-select form-select-sm" id="recipient-status" style="width: auto;">
                            <option value="all">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="sent">Sent</option>
                            <option value="failed">Failed</option>
                            <option value="skipped">Skipped</option>
                        </select>
                        <input type="search" class="form-control form-control-sm" id="recipient-search" placeholder="Name, class, admission, contact" style="min-width: 220px;">
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-modern table-sm mb-0 recipient-table align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student / recipient</th>
                                <th>Class</th>
                                <th>Admission</th>
                                <th>Parent</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Detail</th>
                                <th>Sent at</th>
                            </tr>
                        </thead>
                        <tbody id="recipient-body">
                            @forelse($recipientRows as $row)
                                <tr>
                                    <td>{{ $row['student_name'] }}</td>
                                    <td>{{ $row['classroom'] ?: '—' }}</td>
                                    <td>{{ $row['admission_number'] ?: '—' }}</td>
                                    <td>{{ $row['parent_name'] ?: '—' }}</td>
                                    <td><code>{{ $row['contact'] ?: '—' }}</code></td>
                                    <td><span class="badge bg-secondary">{{ $row['status'] }}</span></td>
                                    <td class="small text-muted">{{ $row['error'] ?: '' }}</td>
                                    <td class="small">{{ $row['sent_at'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center text-muted p-4">Recipient list will appear here as soon as the job is registered.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="small text-muted" id="recipient-range">
                        @if(($recipientMeta['total'] ?? 0) > 0)
                            Showing {{ $recipientMeta['from'] }}–{{ $recipientMeta['to'] }} of {{ $recipientMeta['total'] }}
                        @endif
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" id="recipient-prev" disabled>Previous</button>
                        <button type="button" class="btn btn-outline-secondary" id="recipient-next" @disabled(($recipientMeta['last_page'] ?? 1) <= 1)>Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const root = document.getElementById('bulk-progress-root');
    if (!root) return;
    const endpoint = root.dataset.url;
    const trackingId = root.dataset.tracking;
    const channel = root.dataset.channel;
    const statusFilter = document.getElementById('recipient-status');
    const searchInput = document.getElementById('recipient-search');
    const tbody = document.getElementById('recipient-body');
    let page = 1;
    let lastPage = {{ (int) ($recipientMeta['last_page'] ?? 1) }};
    let timer = null;
    let searchTimer = null;

    function badgeClass(status) {
        if (status === 'sent' || status === 'completed') return 'success';
        if (status === 'failed') return 'danger';
        if (status === 'paused') return 'warning';
        if (status === 'pending' || status === 'processing' || status === 'queued') return 'primary';
        return 'secondary';
    }

    function text(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function cell(textValue) {
        const td = document.createElement('td');
        td.textContent = textValue || '—';
        return td;
    }

    function renderRows(rows) {
        tbody.replaceChildren();
        if (!rows.length) {
            const tr = document.createElement('tr');
            const td = document.createElement('td');
            td.colSpan = 8;
            td.className = 'text-center text-muted p-4';
            td.textContent = 'No recipients match this filter.';
            tr.appendChild(td);
            tbody.appendChild(tr);
            return;
        }
        rows.forEach(function (row) {
            const tr = document.createElement('tr');
            tr.appendChild(cell(row.student_name));
            tr.appendChild(cell(row.classroom));
            tr.appendChild(cell(row.admission_number));
            tr.appendChild(cell(row.parent_name));
            const contact = document.createElement('td');
            const code = document.createElement('code');
            code.textContent = row.contact || '—';
            contact.appendChild(code);
            tr.appendChild(contact);
            const statusTd = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = 'badge bg-' + badgeClass(row.status) + (row.status === 'paused' ? ' text-dark' : '');
            badge.textContent = row.status || 'pending';
            statusTd.appendChild(badge);
            tr.appendChild(statusTd);
            const detail = document.createElement('td');
            detail.className = 'small text-muted';
            detail.textContent = row.error || '';
            tr.appendChild(detail);
            const sent = document.createElement('td');
            sent.className = 'small';
            sent.textContent = row.sent_at || '—';
            tr.appendChild(sent);
            tbody.appendChild(tr);
        });
    }

    function apply(data) {
        const progress = data.progress || {};
        const job = data.job;
        const ui = progress.status || 'processing';
        text('stat-total', progress.total ?? 0);
        text('stat-sent', progress.sent ?? 0);
        text('stat-failed', progress.failed ?? 0);
        text('stat-pending', progress.pending ?? 0);
        text('stat-skipped', progress.skipped ?? 0);
        text('progress-fraction', (progress.processed ?? 0) + ' / ' + (progress.total ?? 0));
        const badge = document.getElementById('progress-status-badge');
        badge.className = 'badge bg-' + badgeClass(ui) + (ui === 'paused' ? ' text-dark' : '');
        badge.textContent = ui.charAt(0).toUpperCase() + ui.slice(1);
        const reason = document.getElementById('progress-pause-reason');
        reason.textContent = job && job.pause_reason ? String(job.pause_reason).replaceAll('_', ' ') : '';
        const bar = document.getElementById('progress-bar');
        const pct = progress.percentage || 0;
        bar.style.width = pct + '%';
        bar.textContent = pct + '%';
        bar.className = 'progress-bar bg-' + badgeClass(ui) + (ui === 'processing' || ui === 'queued' ? ' progress-bar-striped progress-bar-animated' : '');
        const note = document.getElementById('progress-note');
        if (ui === 'processing' || ui === 'queued') {
            note.textContent = 'Sending in the background. Recipient status refreshes automatically. Pause stops after the message currently going out.';
        } else if (ui === 'paused') {
            note.textContent = 'This job is paused. Resume continues with recipients who have not been sent yet.';
        } else if (progress.message) {
            note.textContent = progress.message;
        } else if (ui === 'completed') {
            note.textContent = 'Send finished.';
        } else if (ui === 'cancelled') {
            note.textContent = 'Stopped. Messages already sent were kept.';
        } else {
            note.textContent = '';
        }
        if (job) {
            document.getElementById('pause-form')?.classList.toggle('d-none', !job.pausable);
            document.getElementById('resume-form')?.classList.toggle('d-none', !job.resumable);
            document.getElementById('stop-form')?.classList.toggle('d-none', !job.cancellable);
        }
        const retry = document.getElementById('retry-form');
        if (retry) {
            const showRetry = (progress.failed || 0) > 0 && ['completed', 'failed', 'paused', 'cancelled'].indexOf(ui) !== -1;
            retry.classList.toggle('d-none', !showRetry);
            text('retry-count', progress.failed ?? 0);
        }
        if (progress.report_id) {
            const link = document.getElementById('report-link');
            const wrap = document.getElementById('report-links');
            if (link) {
                link.href = '{{ url('/communication/delivery-report') }}/' + encodeURIComponent(progress.report_id);
                link.classList.remove('d-none');
            }
            wrap?.classList.remove('d-none');
        }
        const recipients = data.recipients || {};
        renderRows(recipients.data || []);
        lastPage = recipients.last_page || 1;
        page = recipients.current_page || page;
        const range = document.getElementById('recipient-range');
        range.textContent = recipients.total ? ('Showing ' + recipients.from + '–' + recipients.to + ' of ' + recipients.total) : '';
        document.getElementById('recipient-prev').disabled = page <= 1;
        document.getElementById('recipient-next').disabled = page >= lastPage;
        schedule(ui);
    }

    function schedule(ui) {
        if (timer) clearTimeout(timer);
        if (['completed', 'failed', 'cancelled'].indexOf(ui) !== -1) return;
        timer = setTimeout(load, ui === 'paused' ? 8000 : 4000);
    }

    function load() {
        const params = new URLSearchParams({
            tracking_id: trackingId,
            channel: channel,
            page: String(page),
            status: statusFilter.value || 'all',
            q: searchInput.value || ''
        });
        fetch(endpoint + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(function (response) { return response.json(); })
            .then(apply)
            .catch(function () { schedule('processing'); });
    }

    statusFilter.addEventListener('change', function () { page = 1; load(); });
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { page = 1; load(); }, 300);
    });
    document.getElementById('recipient-prev').addEventListener('click', function () {
        if (page > 1) { page -= 1; load(); }
    });
    document.getElementById('recipient-next').addEventListener('click', function () {
        if (page < lastPage) { page += 1; load(); }
    });

    schedule('{{ $uiStatus }}');
})();
</script>
@endpush
