@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="mb-0">Bulk Credit / Debit Adjustments</h3>
        <a href="{{ route('finance.journals.bulk.history') }}" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-clock-history"></i> Upload History
        </a>
    </div>

    @includeIf('finance.invoices.partials.alerts')

    @if(!empty($recentImports) && $recentImports->isNotEmpty())
        <div class="card shadow-sm mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="fw-bold">Recent uploads</span>
                <a href="{{ route('finance.journals.bulk.history') }}" class="small">View all</a>
            </div>
            <div class="list-group list-group-flush">
                @foreach($recentImports as $imp)
                    <a href="{{ route('finance.journals.bulk.history.show', $imp) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span>
                            <strong>{{ $imp->batch_code }}</strong>
                            <span class="text-muted small ms-2">{{ $imp->original_filename }}</span>
                        </span>
                        <span class="small">
                            {{ $imp->approved_rows }}/{{ $imp->total_rows }} applied
                            · Cr {{ number_format((float) $imp->credit_total, 0) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <p class="mb-2">Upload an Excel/CSV using this exact header order:</p>
            <code>admission_number, votehead_name, effective_date, type, year, term, reason, amount</code>
            <p class="text-muted small mt-2 mb-0">
                Dates accept <strong>DD/MM/YYYY</strong> or <strong>YYYY-MM-DD</strong>.
                Use positive amounts only; negative values are converted to absolute values.
                Type is <strong>Cr</strong> or <strong>Dr</strong>.
                Upload builds a <strong>preview</strong> — nothing is applied until you approve.
            </p>
            <div class="mt-2">
                <a href="{{ route('finance.journals.bulk.template') }}" class="btn btn-sm btn-outline-secondary">
                    Download Template
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('finance.journals.bulk.import') }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label">Upload file (.xlsx, .xls, .csv)</label>
                    <input type="file" name="file" class="form-control" required>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary"><i class="bi bi-upload"></i> Preview Import</button>
                </div>
            </form>
        </div>
    </div>

    @if(!empty($preview) && !empty($preview['rows']))
        @php
            $pendingCount = (int) ($preview['pending'] ?? 0);
            $money = fn ($v) => $v === null ? '—' : number_format((float) $v, 2);
        @endphp
        <div class="card shadow-sm mt-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span class="fw-bold">Import Preview @if(!empty($preview['batch_code'])) · {{ $preview['batch_code'] }} @endif</span>
                <span class="text-muted small">Loaded {{ $preview['created_at'] ?? '' }}</span>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <span class="badge bg-warning text-dark">Pending: {{ $pendingCount }}</span>
                    <span class="badge bg-success ms-1">Approved: {{ $preview['approved'] ?? 0 }}</span>
                    <span class="badge bg-danger ms-1">Failed: {{ $preview['failed'] ?? 0 }}</span>
                    <span class="badge bg-secondary ms-1">Invalid: {{ $preview['invalid'] ?? 0 }}</span>
                    <span class="badge bg-dark ms-1">Total: {{ $preview['total'] ?? 0 }}</span>
                </div>

                <form id="bulk-approve-form" method="POST" action="{{ route('finance.journals.bulk.approve') }}">
                    @csrf
                    <input type="hidden" name="mode" id="approve-mode" value="selected">

                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:36px">
                                        @if($pendingCount > 0)
                                            <input type="checkbox" id="select-all-pending" title="Select all pending">
                                        @endif
                                    </th>
                                    <th>Line</th>
                                    <th>Student</th>
                                    <th>Votehead</th>
                                    <th>Period</th>
                                    <th>Type</th>
                                    <th class="text-end">Before</th>
                                    <th class="text-end">Adjustment</th>
                                    <th class="text-end">New Total</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($preview['rows'] as $r)
                                    @php
                                        $isPending = ($r['status'] ?? '') === 'pending';
                                        $adj = (float) ($r['amount'] ?? 0);
                                        $adjSigned = ($r['type'] ?? '') === 'debit' ? $adj : -$adj;
                                        $statusClass = match($r['status'] ?? '') {
                                            'pending' => 'bg-warning text-dark',
                                            'approved' => 'bg-success',
                                            'failed', 'invalid' => 'bg-danger',
                                            default => 'bg-secondary',
                                        };
                                    @endphp
                                    <tr class="{{ $isPending ? '' : 'table-secondary' }}">
                                        <td>
                                            @if($isPending)
                                                <input type="checkbox" class="row-check" name="uids[]" value="{{ $r['uid'] }}">
                                            @endif
                                        </td>
                                        <td>{{ $r['line'] }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $r['admission_number'] }}</div>
                                            <div class="small text-muted">{{ $r['student_name'] ?: '—' }}</div>
                                        </td>
                                        <td>
                                            <div>{{ $r['votehead_name'] ?: '—' }}</div>
                                            @if(!empty($r['reason']))
                                                <div class="small text-muted text-truncate" style="max-width:180px" title="{{ $r['reason'] }}">{{ $r['reason'] }}</div>
                                            @endif
                                        </td>
                                        <td class="text-nowrap">{{ $r['year'] }}/T{{ $r['term'] }}</td>
                                        <td>
                                            <span class="badge {{ ($r['type'] ?? '') === 'credit' ? 'bg-info text-dark' : (($r['type'] ?? '') === 'debit' ? 'bg-primary' : 'bg-secondary') }}">
                                                {{ $r['type_label'] ?: '—' }}
                                            </span>
                                        </td>
                                        <td class="text-end text-nowrap">{{ $money($r['before_amount'] ?? null) }}</td>
                                        <td class="text-end text-nowrap {{ $adjSigned < 0 ? 'text-success' : ($adjSigned > 0 ? 'text-danger' : '') }}">
                                            @if(($r['before_amount'] ?? null) === null)
                                                —
                                            @else
                                                {{ $adjSigned > 0 ? '+' : '' }}{{ number_format($adjSigned, 2) }}
                                            @endif
                                        </td>
                                        <td class="text-end text-nowrap fw-semibold">{{ $money($r['new_amount'] ?? null) }}</td>
                                        <td>
                                            <span class="badge {{ $statusClass }}">{{ strtoupper($r['status'] ?? '') }}</span>
                                        </td>
                                        <td class="small">{{ $r['message'] }}</td>
                                        <td class="text-nowrap">
                                            @if($isPending)
                                                <button type="submit"
                                                    class="btn btn-sm btn-outline-success approve-one-btn"
                                                    data-uid="{{ $r['uid'] }}"
                                                    title="Approve this row">
                                                    Approve
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mt-3 pt-3 border-top">
                        <div class="d-flex flex-wrap gap-2">
                            @if($pendingCount > 0)
                                <button type="submit" class="btn btn-success" id="approve-selected-btn">
                                    <i class="bi bi-check2-square"></i> Approve Selected
                                </button>
                                <button type="submit" class="btn btn-primary" id="approve-all-btn">
                                    <i class="bi bi-check2-all"></i> Approve All ({{ $pendingCount }})
                                </button>
                            @else
                                <span class="text-muted">No pending rows left to approve.</span>
                            @endif
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="submit" formaction="{{ route('finance.journals.bulk.clear') }}" formmethod="POST" class="btn btn-outline-secondary" onclick="return confirm('Clear this preview?')">
                                Clear Preview
                            </button>
                            <a class="btn btn-outline-primary" href="{{ route('finance.invoices.index') }}">Back to Invoices</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('bulk-approve-form');
    if (!form) return;

    const modeInput = document.getElementById('approve-mode');
    const selectAll = document.getElementById('select-all-pending');
    const checks = () => Array.from(form.querySelectorAll('.row-check'));

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            checks().forEach(cb => { cb.checked = selectAll.checked; });
        });
    }

    const approveSelectedBtn = document.getElementById('approve-selected-btn');
    if (approveSelectedBtn) {
        approveSelectedBtn.addEventListener('click', function () {
            modeInput.value = 'selected';
            const n = checks().filter(c => c.checked).length;
            if (n === 0) {
                event.preventDefault();
                alert('Select at least one pending row.');
                return;
            }
            if (!confirm('Approve ' + n + ' selected adjustment(s)?')) {
                event.preventDefault();
            }
        });
    }

    const approveAllBtn = document.getElementById('approve-all-btn');
    if (approveAllBtn) {
        approveAllBtn.addEventListener('click', function (event) {
            modeInput.value = 'all';
            // Uncheck so selected mode isn't confused; server uses mode=all
            checks().forEach(cb => { cb.checked = false; });
            if (!confirm('Approve ALL pending adjustments? This cannot be undone from this screen.')) {
                event.preventDefault();
            }
        });
    }

    form.querySelectorAll('.approve-one-btn').forEach(btn => {
        btn.addEventListener('click', function (event) {
            modeInput.value = 'selected';
            checks().forEach(cb => { cb.checked = false; });
            const uid = btn.getAttribute('data-uid');
            const match = checks().find(cb => cb.value === uid);
            if (match) match.checked = true;
            if (!confirm('Approve this adjustment?')) {
                event.preventDefault();
            }
        });
    });
})();
</script>
@endpush
