@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Payment Plan History',
        'icon' => 'bi bi-clock-history',
        'subtitle' => $student->full_name . ' (' . ($student->admission_number ?? '—') . ')',
        'actions' => '<a href="' . route('finance.accountant-dashboard.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Dashboard</a><a href="' . route('finance.fee-payment-plans.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-calendar-check"></i> All Plans</a><a href="' . route('students.show', $student) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-person"></i> Student</a>'
    ])

    {{-- Compliance score --}}
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="finance-stat-card finance-animate">
                <div class="text-muted small">Compliance score</div>
                <div class="fw-bold fs-4 {{ $complianceScore >= 100 ? 'text-success' : ($complianceScore >= 50 ? 'text-warning' : 'text-danger') }}">
                    {{ number_format($complianceScore, 0) }}%
                </div>
                <div class="text-muted small">{{ $plans->where('is_compliant', true)->count() }} of {{ $plans->count() }} plans completed</div>
            </div>
        </div>
    </div>

    {{-- Payment plans for this student --}}
    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i> Payment Plans ({{ $plans->count() }})</h5>
        </div>
        <div class="finance-card-body p-0">
            <div class="table-responsive">
                <table class="finance-table mb-0">
                    <thead>
                        <tr>
                            <th>Term / Year</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td>
                                    {{ $plan->term->name ?? 'N/A' }} / {{ $plan->academicYear->name ?? 'N/A' }}
                                    @if($plan->start_date)
                                        <br><small class="text-muted">{{ $plan->start_date->format('M Y') }} – {{ $plan->end_date ? $plan->end_date->format('M Y') : '—' }}</small>
                                    @endif
                                </td>
                                <td>KES {{ number_format($plan->total_amount ?? 0, 2) }}</td>
                                <td>KES {{ number_format($plan->total_paid ?? 0, 2) }}</td>
                                <td>
                                    <strong class="{{ ($plan->remaining_balance ?? 0) > 0 ? 'text-danger' : 'text-success' }}">
                                        KES {{ number_format($plan->remaining_balance ?? 0, 2) }}
                                    </strong>
                                </td>
                                <td>
                                    @php $pct = $plan->percentage_paid ?? 0; @endphp
                                    <div class="progress" style="height: 1.25rem; min-width: 80px;">
                                        <div class="progress-bar bg-{{ $pct >= 100 ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}" role="progressbar" style="width: {{ min(100, $pct) }}%">
                                            {{ number_format($pct, 0) }}%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $plan->status === 'completed' ? 'success' : ($plan->status === 'active' ? 'primary' : 'secondary') }}">
                                        {{ ucfirst($plan->status ?? 'N/A') }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('finance.fee-payment-plans.show', $plan) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="bi bi-inbox fs-3"></i>
                                    <p class="mb-0 mt-2">No payment plans for this student.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
</div>
@endsection
