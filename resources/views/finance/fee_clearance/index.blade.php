@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
            <div class="crumb">Finance / Reports / Fee Clearance</div>
            <h2 class="mb-1">Fee Clearance Status</h2>
            <div class="text-muted">
                @if($term)
                    Current term: <strong>{{ $term->name }}</strong>
                    @if($term->academicYear)
                        ({{ $term->academicYear->year }})
                    @endif
                @else
                    <strong>No current term set</strong> — set a term under Settings → Academic.
                @endif
            </div>
        </div>

        <form method="POST" action="{{ route('finance.fee-clearance.recompute') }}" class="d-inline">
            @csrf
            <input type="hidden" name="term_id" value="{{ $term?->id }}">
            <button type="submit" class="btn btn-outline-primary" onclick="return confirm('Recompute clearance snapshots now?');">
                <i class="bi bi-arrow-clockwise"></i> Recompute Now
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if($term && ($term->fee_clearance_day1_date || $term->fee_clearance_strict_from_date || $term->opening_date))
        @php
            $day1 = ($term->fee_clearance_day1_date ?: $term->opening_date);
            $strictFrom = ($term->fee_clearance_strict_from_date ?: ($day1 ? $day1->copy()->addDay() : null));
        @endphp
        <div class="alert alert-info">
            <div class="fw-semibold mb-1">Enforcement</div>
            <div class="small">
                Day 1: <strong>{{ $day1 ? $day1->format('M d, Y') : '—' }}</strong>.
                Strict enforcement from: <strong>{{ $strictFrom ? $strictFrom->format('M d, Y') : '—' }}</strong>.
            </div>
        </div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Total</div>
                    <div class="fs-3 fw-bold">{{ number_format($counts['total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Cleared</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($counts['cleared']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">Pending</div>
                    <div class="fs-3 fw-bold text-danger">{{ number_format($counts['pending']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="text-muted small">% Cleared</div>
                    <div class="fs-3 fw-bold">
                        {{ $counts['total'] > 0 ? number_format($counts['cleared'] * 100 / $counts['total'], 1) : '0' }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">Term</label>
                    <select name="term_id" class="form-select">
                        @foreach($terms as $t)
                            <option value="{{ $t->id }}" {{ (int)($filters['term_id'] ?? 0) === (int)$t->id ? 'selected' : '' }}>
                                {{ $t->name }} @if($t->academicYear) ({{ $t->academicYear->year }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Class</label>
                    <select name="classroom_id" class="form-select">
                        <option value="">All classes</option>
                        @foreach($classrooms as $c)
                            <option value="{{ $c->id }}" {{ (int)($filters['classroom_id'] ?? 0) === (int)$c->id ? 'selected' : '' }}>
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="cleared" {{ ($filters['status'] ?? '') === 'cleared' ? 'selected' : '' }}>Cleared</option>
                        <option value="pending" {{ ($filters['status'] ?? '') === 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Reason</label>
                    <select name="reason_code" class="form-select">
                        <option value="">Any</option>
                        @foreach(['fully_paid' => 'Fully paid', 'above_threshold' => 'Above threshold', 'payment_plan' => 'On payment plan', 'below_threshold' => 'Below threshold', 'deadline_passed' => 'Deadline passed', 'no_threshold' => 'No threshold', 'no_fees' => 'No fees'] as $k => $label)
                            <option value="{{ $k }}" {{ ($filters['reason_code'] ?? '') === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Name or adm #">
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-funnel"></i> Apply
                    </button>
                    <a href="{{ route('finance.fee-clearance.index') }}" class="btn btn-ghost">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <div class="fw-semibold">Students</div>
            <div class="text-muted small">
                @if($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    Showing {{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }} of {{ $rows->total() }}
                @endif
            </div>
        </div>
        <div class="card-body p-0">
            @if(!$term)
                <div class="p-4 text-muted">No term selected.</div>
            @elseif($rows->isEmpty())
                <div class="p-4 text-muted">No students match the selected filters.</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
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
                                            <small class="text-muted d-block">{{ $st->stream->name }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->status === 'cleared')
                                            <span class="badge bg-success">Cleared</span>
                                        @else
                                            <span class="badge bg-danger">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-muted small">{{ str_replace('_', ' ', (string) $row->reason_code) }}</span>
                                    </td>
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
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $row->final_clearance_deadline ? \Carbon\Carbon::parse($row->final_clearance_deadline)->format('M d, Y') : '—' }}
                                    </td>
                                    <td class="text-end text-muted small">
                                        {{ $row->computed_at ? $row->computed_at->diffForHumans() : '—' }}
                                    </td>
                                    <td>
                                        <a href="{{ route('finance.student-statements.show', $st) }}" class="btn btn-sm btn-outline-secondary" title="View statement">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if($rows instanceof \Illuminate\Pagination\LengthAwarePaginator && $rows->hasPages())
            <div class="card-footer bg-white">
                {{ $rows->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
