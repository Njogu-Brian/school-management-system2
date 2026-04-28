@extends('layouts.app')

@section('content')
<div class="finance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Fee Clearance Status',
            'icon' => 'bi bi-shield-check',
            'subtitle' => $term
                ? 'Term: ' . $term->name . ($term->academicYear ? ' (' . $term->academicYear->year . ')' : '')
                : 'Set a current term under Settings → Academic.',
        ])

        <div class="d-flex flex-wrap gap-2 justify-content-end mb-3 finance-animate">
            <a href="{{ route('finance.payment-thresholds.index', array_filter(['term_id' => $term?->id])) }}" class="btn btn-finance btn-finance-outline">
                <i class="bi bi-sliders"></i> Payment thresholds
            </a>
            @if($term)
                <a
                    href="{{ route('finance.fee-clearance.export-pdf', request()->only(['term_id','classroom_id','status','reason_code','search'])) }}"
                    class="btn btn-finance btn-finance-outline"
                    title="Export fee clearance status as PDF"
                >
                    <i class="bi bi-file-pdf"></i> Export PDF (by class)
                </a>
            @endif
            <form method="POST" action="{{ route('finance.fee-clearance.recompute') }}" class="d-inline" onsubmit="return confirm('Recompute clearance snapshots now?');">
                @csrf
                <input type="hidden" name="term_id" value="{{ $term?->id }}">
                <button type="submit" class="btn btn-finance btn-finance-primary">
                    <i class="bi bi-arrow-clockwise"></i> Recompute now
                </button>
            </form>
        </div>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($term && ($paymentThresholdsCount ?? 0) === 0)
            <div class="alert alert-warning finance-animate d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div>
                    <strong>No active payment thresholds</strong> for this term. Students are marked <em>cleared</em> with reason “no threshold” until you add rules.
                </div>
                <a href="{{ route('finance.payment-thresholds.create', ['term_id' => $term->id]) }}" class="btn btn-finance btn-finance-primary btn-sm flex-shrink-0">
                    <i class="bi bi-plus-circle"></i> Add threshold
                </a>
            </div>
        @endif

        @if($term && ($term->fee_clearance_day1_date || $term->fee_clearance_strict_from_date || $term->opening_date))
            @php
                $day1 = ($term->fee_clearance_day1_date ?: $term->opening_date);
                $strictFrom = ($term->fee_clearance_strict_from_date ?: ($day1 ? $day1->copy()->addDay() : null));
            @endphp
            <div class="alert alert-info finance-animate">
                <div class="fw-semibold mb-1">Enforcement</div>
                <div class="small">
                    Day 1: <strong>{{ $day1 ? $day1->format('M d, Y') : '—' }}</strong>.
                    Strict enforcement from: <strong>{{ $strictFrom ? $strictFrom->format('M d, Y') : '—' }}</strong>.
                </div>
            </div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="finance-stat-card finance-animate">
                    <div class="small finance-muted">Total</div>
                    <div class="fs-3 fw-bold">{{ number_format($counts['total']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="finance-stat-card finance-animate">
                    <div class="small finance-muted">Cleared</div>
                    <div class="fs-3 fw-bold" style="color: #166534;">{{ number_format($counts['cleared']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="finance-stat-card finance-animate">
                    <div class="small finance-muted">Pending</div>
                    <div class="fs-3 fw-bold" style="color: #b91c1c;">{{ number_format($counts['pending']) }}</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="finance-stat-card finance-animate">
                    <div class="small finance-muted">% Cleared</div>
                    <div class="fs-3 fw-bold">
                        {{ $counts['total'] > 0 ? number_format($counts['cleared'] * 100 / $counts['total'], 1) : '0' }}%
                    </div>
                </div>
            </div>
        </div>

        <div class="finance-card finance-filter-card finance-animate mb-3">
            <div class="finance-card-header">
                <i class="bi bi-funnel me-2"></i> Filters
            </div>
            <div class="finance-card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="finance-form-label">Term</label>
                        <select name="term_id" class="finance-form-select">
                            @foreach($terms as $t)
                                <option value="{{ $t->id }}" {{ (int)($filters['term_id'] ?? 0) === (int)$t->id ? 'selected' : '' }}>
                                    {{ $t->name }} @if($t->academicYear) ({{ $t->academicYear->year }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="finance-form-label">Class</label>
                        <select name="classroom_id" class="finance-form-select">
                            <option value="">All classes</option>
                            @foreach($classrooms as $c)
                                <option value="{{ $c->id }}" {{ (int)($filters['classroom_id'] ?? 0) === (int)$c->id ? 'selected' : '' }}>
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="finance-form-label">Status</label>
                        <select name="status" class="finance-form-select">
                            <option value="">All</option>
                            <option value="cleared" {{ ($filters['status'] ?? '') === 'cleared' ? 'selected' : '' }}>Cleared</option>
                            <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="finance-form-label">Reason</label>
                        <select name="reason_code" class="finance-form-select">
                            <option value="">Any</option>
                            @foreach(['fully_paid' => 'Fully paid', 'above_threshold' => 'Above threshold', 'payment_plan' => 'On payment plan', 'below_threshold' => 'Below threshold', 'deadline_passed' => 'Deadline passed', 'no_threshold' => 'No threshold', 'no_fees' => 'No fees'] as $k => $label)
                                <option value="{{ $k }}" {{ ($filters['reason_code'] ?? '') === $k ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="finance-form-label">Search</label>
                        <input type="text" name="search" class="finance-form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Name or adm #">
                    </div>
                    <div class="col-12 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-funnel"></i> Apply
                        </button>
                        <a href="{{ route('finance.fee-clearance.index') }}" class="btn btn-finance btn-finance-outline">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="finance-table-wrapper finance-animate">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom" style="border-color: var(--fin-border, #e5e7eb) !important;">
                <span class="fw-semibold">Students</span>
                <span class="small finance-muted">
                    @if($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        Showing {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }}
                    @endif
                </span>
            </div>
            @if(!$term)
                <div class="p-4 finance-muted">No term selected.</div>
            @elseif($rows->isEmpty())
                <div class="p-4 finance-muted">No students match the selected filters.</div>
            @else
                <div class="table-responsive">
                    <table class="finance-table mb-0">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Adm #</th>
                                <th>Class</th>
                                <th>Status</th>
                                <th>Reason</th>
                                <th class="text-end">Paid %</th>
                                <th class="text-end">Balance</th>
                                <th>Plan</th>
                                <th>Deadline</th>
                                <th class="text-end">Updated</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                @php
                                    $st = $row->student;
                                    if (!$st) continue;
                                    $meta = $row->meta ?? [];
                                @endphp
                                <tr>
                                    <td class="fw-semibold">{{ $st->full_name ?? ($st->first_name.' '.$st->last_name) }}</td>
                                    <td>{{ $st->admission_number }}</td>
                                    <td>
                                        {{ $st->classroom?->name }}
                                        @if($st->stream)
                                            <small class="d-block finance-muted">{{ $st->stream->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->status === 'cleared')
                                            <span class="badge bg-success">Cleared</span>
                                        @else
                                            <span class="badge bg-danger">Pending</span>
                                        @endif
                                    </td>
                                    <td><span class="small finance-muted">{{ str_replace('_', ' ', (string) $row->reason_code) }}</span></td>
                                    <td class="text-end">
                                        {{ $row->percentage_paid !== null ? number_format((float)$row->percentage_paid, 1).'%' : '—' }}
                                    </td>
                                    <td class="text-end">
                                        {{ isset($meta['balance']) ? number_format((float)$meta['balance'], 2) : '—' }}
                                    </td>
                                    <td>
                                        @if($row->has_valid_payment_plan && $row->paymentPlan)
                                            <a href="{{ route('finance.fee-payment-plans.show', $row->paymentPlan) }}" class="badge bg-info text-decoration-none">
                                                Plan #{{ $row->paymentPlan->id }}
                                            </a>
                                        @else
                                            <span class="finance-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ ($d = $row->displayFinalClearanceDeadline()) ? $d->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="text-end small finance-muted">
                                        {{ $row->computed_at ? $row->computed_at->diffForHumans() : '—' }}
                                    </td>
                                    <td>
                                        <a href="{{ route('finance.student-statements.show', $st) }}" class="btn btn-sm btn-finance btn-finance-outline py-0 px-2" title="View statement">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @if($rows instanceof \Illuminate\Pagination\LengthAwarePaginator && $rows->hasPages())
                <div class="p-3 border-top" style="border-color: var(--fin-border, #e5e7eb) !important;">
                    {{ $rows->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
