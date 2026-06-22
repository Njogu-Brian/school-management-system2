@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Settings / Logs</div>
                <h1>System Logs</h1>
                <p>Application errors and events for accountability and debugging.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('system-logs.download') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-download"></i> Download
                </a>
                <form action="{{ route('system-logs.clear') }}" method="POST" class="d-inline" onsubmit="return confirm('Clear all logs? This cannot be undone.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-trash"></i> Clear
                    </button>
                </form>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show mt-3">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show mt-3">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(isset($error))
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> {{ $error }}
            </div>
        @endif

        @if(!empty($logFiles ?? []))
            <div class="alert alert-secondary py-2 small mb-0 mt-3">
                <i class="bi bi-file-earmark-text"></i>
                Reading: {{ implode(', ', $logFiles) }}.
                @php $cfgLevel = $configuredLogLevel ?? config('logging.level', 'debug'); @endphp
                Server writes <strong>{{ $cfgLevel }}</strong> level and above to disk (<code>LOG_LEVEL</code> in <code>.env</code>).
                Use <code>info</code> to capture routine events; use <code>warning</code> on small disks.
            </div>
        @endif

        <div class="settings-card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('system-logs.index') }}" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label">Log Level</label>
                        <select name="level" class="form-select">
                            @foreach($levels as $value => $label)
                                <option value="{{ $value }}" {{ ($currentLevel ?? 'all') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select">
                            @foreach(($categories ?? []) as $value => $label)
                                <option value="{{ $value }}" {{ ($currentCategory ?? 'all') == $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ $currentDate ?? '' }}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" name="search" class="form-control" value="{{ $currentSearch ?? '' }}" placeholder="Search messages and stack traces">
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-settings-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('system-logs.index') }}" class="btn btn-ghost-strong">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0"><i class="bi bi-file-text"></i> Log Entries</h5>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    @if(!$logs->isEmpty())
                        <button type="button" class="btn btn-sm btn-ghost-strong" id="copySelectedLogs" disabled title="Copy selected entries">
                            <i class="bi bi-clipboard"></i> Copy selected
                        </button>
                        <button type="button" class="btn btn-sm btn-ghost-strong" id="copyAllLogsOnPage" title="Copy all entries on this page">
                            <i class="bi bi-clipboard-check"></i> Copy page
                        </button>
                    @endif
                    @if(isset($total))
                        <span class="input-chip">{{ $total }} entries</span>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                @if($logs->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No log entries found matching your filters.</p>
                        @if(!empty($logFiles ?? []))
                            <p class="text-muted small">Log files are present but empty or filtered out. Try date <strong>{{ date('Y-m-d') }}</strong>, level <strong>All</strong>, or lower <code>LOG_LEVEL</code> temporarily for more detail.</p>
                        @endif
                    </div>
                @else
                    <div class="table-responsive" style="max-height: 70vh; overflow-y: auto;">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th style="width: 36px;">
                                        <input type="checkbox" class="form-check-input" id="selectAllLogs" title="Select all on this page" aria-label="Select all log entries">
                                    </th>
                                    <th style="width: 180px;">Timestamp</th>
                                    <th style="width: 100px;">Level</th>
                                    <th style="width: 140px;">Category</th>
                                    <th>Message</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logs as $log)
                                    <tr class="log-row" data-level="{{ strtolower($log['level']) }}" data-log-index="{{ $loop->index }}">
                                        <td>
                                            <input type="checkbox" class="form-check-input log-select" value="{{ $loop->index }}" aria-label="Select log entry">
                                        </td>
                                        <td class="text-muted small">
                                            {{ $log['timestamp'] }}
                                        </td>
                                        <td>
                                            @php
                                                $levelColors = [
                                                    'error' => 'danger',
                                                    'critical' => 'danger',
                                                    'alert' => 'warning',
                                                    'emergency' => 'danger',
                                                    'warning' => 'warning',
                                                    'info' => 'info',
                                                    'debug' => 'secondary',
                                                ];
                                                $color = $levelColors[strtolower($log['level'])] ?? 'secondary';
                                            @endphp
                                            <span class="badge bg-{{ $color }}">{{ $log['level'] }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $catLabels = $categories ?? [];
                                                $cat = $log['category'] ?? 'system';
                                                $catLabel = $catLabels[$cat] ?? ucfirst(str_replace('_', ' ', $cat));
                                            @endphp
                                            <span class="badge bg-light text-dark border" title="{{ $catLabel }}">{{ $cat }}</span>
                                        </td>
                                        <td>
                                            <div class="log-message" style="max-width: 500px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                {{ $log['message'] }}
                                            </div>
                                            @if(!empty($log['stack']))
                                                <small class="text-muted d-block mt-1">
                                                    <i class="bi bi-code-square"></i> Stack trace available
                                                </small>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-ghost-strong"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#logDetailModal{{ $loop->index }}"
                                                        title="View details">
                                                    <i class="bi bi-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-ghost-strong"
                                                        onclick="copyLogDetails({{ $loop->index }})"
                                                        title="Copy entry">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Log Detail Modal -->
                                    <div class="modal fade" id="logDetailModal{{ $loop->index }}" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header bg-dark text-white">
                                                    <h5 class="modal-title">
                                                        <i class="bi bi-bug"></i> Log Entry Details
                                                    </h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <strong>Timestamp:</strong>
                                                        <div class="text-muted">{{ $log['timestamp'] }}</div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>Environment:</strong>
                                                        <span class="badge bg-secondary">{{ $log['environment'] }}</span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>Level:</strong>
                                                        <span class="badge bg-{{ $color }}">{{ $log['level'] }}</span>
                                                    </div>
                                                    <div class="mb-3">
                                                        <strong>Message:</strong>
                                                        <div class="alert alert-{{ $color === 'danger' ? 'danger' : ($color === 'warning' ? 'warning' : 'info') }} mb-0">
                                                            <pre class="mb-0" style="white-space: pre-wrap; word-wrap: break-word;">{{ $log['message'] }}</pre>
                                                        </div>
                                                    </div>
                                                    @if(!empty($log['stack']))
                                                        <div>
                                                            <strong>Stack Trace:</strong>
                                                            <pre class="bg-dark text-light p-3 rounded" style="max-height: 400px; overflow-y: auto; font-size: 0.85rem; white-space: pre-wrap; word-wrap: break-word;">{{ $log['stack'] }}</pre>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    <button type="button" class="btn btn-settings-primary" onclick="copyLogDetails({{ $loop->index }})">
                                                        <i class="bi bi-clipboard"></i> Copy
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if(isset($totalPages) && $totalPages > 1)
                        <div class="card-footer">
                            <nav aria-label="Log pagination">
                                <ul class="pagination pagination-sm mb-0 justify-content-center">
                                    @if($currentPage > 1)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ route('system-logs.index', array_merge(request()->query(), ['page' => $currentPage - 1])) }}">
                                                Previous
                                            </a>
                                        </li>
                                    @endif

                                    @for($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++)
                                        <li class="page-item {{ $i == $currentPage ? 'active' : '' }}">
                                            <a class="page-link" href="{{ route('system-logs.index', array_merge(request()->query(), ['page' => $i])) }}">
                                                {{ $i }}
                                            </a>
                                        </li>
                                    @endfor

                                    @if($currentPage < $totalPages)
                                        <li class="page-item">
                                            <a class="page-link" href="{{ route('system-logs.index', array_merge(request()->query(), ['page' => $currentPage + 1])) }}">
                                                Next
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </nav>
                            <div class="text-center text-muted small mt-2">
                                Showing page {{ $currentPage }} of {{ $totalPages }} ({{ $total }} total entries)
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
@if(!$logs->isEmpty())
<script type="application/json" id="system-logs-data">@json($logs->values())</script>
@endif
<script>
    const systemLogsData = (() => {
        const el = document.getElementById('system-logs-data');
        if (!el) return [];
        try {
            return JSON.parse(el.textContent || '[]');
        } catch (e) {
            console.error('Failed to parse log data', e);
            return [];
        }
    })();

    function formatLogEntry(log) {
        if (!log) return '';
        const lines = [
            `[${log.timestamp || 'unknown'}] ${log.level || 'LOG'} (${log.category || 'system'})`,
            `Environment: ${log.environment || 'n/a'}`,
            '',
            log.message || '',
        ];
        if (log.stack) {
            lines.push('', 'Stack trace:', log.stack);
        }
        return lines.join('\n');
    }

    function copyTextToClipboard(text) {
        return navigator.clipboard.writeText(text).then(() => {
            showCopyToast('Copied to clipboard');
        }).catch(err => {
            console.error('Failed to copy:', err);
            alert('Could not copy to clipboard. Try selecting the text manually.');
        });
    }

    function showCopyToast(message) {
        const existing = document.getElementById('log-copy-toast');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.id = 'log-copy-toast';
        toast.className = 'position-fixed bottom-0 end-0 m-3 alert alert-success py-2 px-3 shadow-sm';
        toast.style.zIndex = '2000';
        toast.innerHTML = '<i class="bi bi-check-circle"></i> ' + message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2200);
    }

    function copyLogDetails(index) {
        const log = systemLogsData[index];
        if (!log) return;
        copyTextToClipboard(formatLogEntry(log));
    }

    function copyLogsByIndices(indices) {
        const text = indices
            .map(i => formatLogEntry(systemLogsData[i]))
            .filter(Boolean)
            .join('\n\n---\n\n');
        if (!text) return;
        copyTextToClipboard(text);
    }

    function getSelectedLogIndices() {
        return Array.from(document.querySelectorAll('.log-select:checked'))
            .map(cb => parseInt(cb.value, 10))
            .filter(n => !Number.isNaN(n));
    }

    function updateCopySelectedState() {
        const btn = document.getElementById('copySelectedLogs');
        if (!btn) return;
        const count = getSelectedLogIndices().length;
        btn.disabled = count === 0;
        btn.innerHTML = count > 0
            ? `<i class="bi bi-clipboard"></i> Copy selected (${count})`
            : '<i class="bi bi-clipboard"></i> Copy selected';
    }

    document.getElementById('copySelectedLogs')?.addEventListener('click', () => {
        copyLogsByIndices(getSelectedLogIndices());
    });

    document.getElementById('copyAllLogsOnPage')?.addEventListener('click', () => {
        const indices = systemLogsData.map((_, i) => i);
        copyLogsByIndices(indices);
    });

    document.getElementById('selectAllLogs')?.addEventListener('change', (e) => {
        const checked = e.target.checked;
        document.querySelectorAll('.log-select').forEach(cb => { cb.checked = checked; });
        updateCopySelectedState();
    });

    document.querySelectorAll('.log-select').forEach(cb => {
        cb.addEventListener('change', () => {
            const all = document.querySelectorAll('.log-select');
            const selectAll = document.getElementById('selectAllLogs');
            if (selectAll) {
                selectAll.checked = all.length > 0 && Array.from(all).every(c => c.checked);
                selectAll.indeterminate = !selectAll.checked && getSelectedLogIndices().length > 0;
            }
            updateCopySelectedState();
        });
    });

    // Highlight error rows
    document.querySelectorAll('.log-row[data-level="error"], .log-row[data-level="critical"], .log-row[data-level="emergency"]').forEach(row => {
        row.classList.add('table-danger');
    });
</script>
@endpush
@endsection

