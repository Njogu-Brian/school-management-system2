@extends('layouts.app')

@section('content')
<div class="finance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Payment thresholds',
            'icon' => 'bi bi-sliders',
            'subtitle' => 'Minimum fee % and clearance deadlines per term and student category (fee clearance report)',
            'actions' => '<a href="' . route('finance.payment-thresholds.create', array_filter(['term_id' => $filterTermId])) . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Add threshold</a>'
        ])

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

        <div class="finance-card finance-filter-card finance-animate mb-3">
            <div class="finance-card-header">
                <i class="bi bi-funnel me-2"></i> Filter
            </div>
            <div class="finance-card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-6">
                        <label class="finance-form-label">Term</label>
                        <select name="term_id" class="finance-form-select">
                            <option value="">All terms</option>
                            @foreach($terms as $t)
                                <option value="{{ $t->id }}" {{ (int) ($filterTermId ?? 0) === (int) $t->id ? 'selected' : '' }}>
                                    {{ $t->name }} @if($t->academicYear) ({{ $t->academicYear->year }}) @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 d-flex gap-2 flex-wrap">
                        <button type="submit" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-funnel"></i> Apply
                        </button>
                        <a href="{{ route('finance.payment-thresholds.index') }}" class="btn btn-finance btn-finance-outline">Reset</a>
                        <a href="{{ route('finance.fee-clearance.index') }}" class="btn btn-finance btn-finance-outline">
                            <i class="bi bi-shield-check"></i> Fee clearance report
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="finance-table-wrapper finance-animate">
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>Term</th>
                            <th>Category</th>
                            <th class="text-end">Min %</th>
                            <th>Deadline rule</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($thresholds as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row->term?->name ?? '—' }}</strong>
                                    @if($row->term?->academicYear)
                                        <span class="text-muted small d-block">{{ $row->term->academicYear->year }}</span>
                                    @endif
                                </td>
                                <td>{{ $row->studentCategory?->name ?? '—' }}</td>
                                <td class="text-end">{{ number_format((float) $row->minimum_percentage, 2) }}%</td>
                                <td>
                                    <span class="small">Day {{ $row->final_deadline_day }}, +{{ $row->final_deadline_month_offset }} mo from opening</span>
                                </td>
                                <td>
                                    @if($row->is_active)
                                        <span class="finance-badge badge-paid">Active</span>
                                    @else
                                        <span class="finance-badge badge-unpaid">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="finance-action-buttons d-inline-flex gap-1 flex-wrap justify-content-end">
                                        <a href="{{ route('finance.payment-thresholds.edit', $row) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('finance.payment-thresholds.destroy', $row) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this threshold?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="finance-empty-state py-4">
                                        <div class="finance-empty-state-icon"><i class="bi bi-sliders"></i></div>
                                        <h4>No payment thresholds</h4>
                                        <p class="text-muted mb-3">Without thresholds, fee clearance treats students as <strong>cleared</strong> (reason: no threshold). Add one per term and category.</p>
                                        <a href="{{ route('finance.payment-thresholds.create', array_filter(['term_id' => $filterTermId])) }}" class="btn btn-finance btn-finance-primary">
                                            <i class="bi bi-plus-circle"></i> Add threshold
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($thresholds->hasPages())
                <div class="p-3 border-top" style="border-color: var(--fin-border, #e5e7eb) !important;">
                    {{ $thresholds->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
