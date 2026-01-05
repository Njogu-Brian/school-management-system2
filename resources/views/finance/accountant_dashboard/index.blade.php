@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Accountant Dashboard',
        'icon' => 'bi bi-graph-up-arrow',
        'subtitle' => 'Payment plan monitoring and financial oversight',
        'actions' => '<a href="' . route('finance.accountant-dashboard.settings') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-gear"></i> Settings</a><a href="' . route('finance.fee-payment-plans.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-calendar-check"></i> Fee Payment Plans</a>'
    ])

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Statistics Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="finance-stat-card finance-animate">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small">Overdue Plans</div>
                        <div class="fw-bold fs-4 text-danger">{{ number_format($stats['total_overdue_plans']) }}</div>
                    </div>
                    <div class="text-danger">
                        <i class="bi bi-exclamation-triangle-fill fs-3"></i>
                    </div>
                </div>
                <div class="text-muted small">KES {{ number_format($stats['total_overdue_amount'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card finance-animate">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small">Upcoming Installments</div>
                        <div class="fw-bold fs-4 text-warning">{{ number_format($stats['upcoming_installments_count']) }}</div>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-calendar-event fs-3"></i>
                    </div>
                </div>
                <div class="text-muted small">KES {{ number_format($stats['upcoming_installments_amount'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card finance-animate">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small">High-Risk Plans</div>
                        <div class="fw-bold fs-4 text-danger">{{ number_format($stats['high_risk_plans_count']) }}</div>
                    </div>
                    <div class="text-danger">
                        <i class="bi bi-shield-exclamation fs-3"></i>
                    </div>
                </div>
                <div class="text-muted small">KES {{ number_format($stats['high_risk_amount'], 2) }}</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card finance-animate">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small">Days Ahead</div>
                        <div class="fw-bold fs-4">{{ $daysAhead ?? 14 }}</div>
                    </div>
                    <div class="text-muted">
                        <i class="bi bi-clock-history fs-3"></i>
                    </div>
                </div>
                <div class="text-muted small">Upcoming view window</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="finance-filter-card finance-animate mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="finance-form-label">Days Ahead</label>
                <input type="number" 
                       class="finance-form-control" 
                       name="days_ahead" 
                       value="{{ request('days_ahead', $daysAhead ?? 14) }}"
                       min="1"
                       max="90">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                    <i class="bi bi-search"></i> Apply Filter
                </button>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <a href="{{ route('finance.accountant-dashboard.index') }}" class="btn btn-finance btn-finance-outline w-100">
                    <i class="bi bi-x-circle"></i> Reset
                </a>
            </div>
        </form>
    </div>

    {{-- Overdue Payment Plans --}}
    <div class="finance-card finance-animate mb-4">
        <div class="finance-card-header">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle text-danger me-2"></i> Overdue Payment Plans ({{ $overduePlans->count() }})</h5>
        </div>
        <div class="finance-card-body">
            <div class="table-responsive">
                <table class="finance-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Overdue Installments</th>
                        <th>Deadline</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($overduePlans as $plan)
                        <tr>
                            <td>
                                <strong>{{ $plan->student->first_name }} {{ $plan->student->last_name }}</strong><br>
                                <small class="text-muted">{{ $plan->student->admission_number }}</small>
                            </td>
                            <td>{{ $plan->student->classroom->name ?? 'N/A' }}</td>
                            <td>KES {{ number_format($plan->total_amount, 2) }}</td>
                            <td>KES {{ number_format($plan->total_paid, 2) }}</td>
                            <td><strong class="text-danger">KES {{ number_format($plan->remaining_balance, 2) }}</strong></td>
                            <td>
                                <span class="badge bg-danger">{{ $plan->overdue_installments_count }}</span>
                            </td>
                            <td>
                                @if($plan->final_clearance_deadline)
                                    {{ \Carbon\Carbon::parse($plan->final_clearance_deadline)->format('M d, Y') }}
                                @else
                                    N/A
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('finance.fee-payment-plans.show', $plan) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="{{ route('finance.accountant-dashboard.student-history', $plan->student) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-clock-history"></i> History
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-check-circle fs-3"></i>
                                    <p class="mb-0 mt-2">No overdue payment plans</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- Upcoming Installments --}}
    <div class="finance-card finance-animate mb-4">
        <div class="finance-card-header">
            <h5 class="mb-0"><i class="bi bi-calendar-event text-warning me-2"></i> Upcoming Installments ({{ $upcomingInstallments->count() }})</h5>
        </div>
        <div class="finance-card-body">
            <div class="table-responsive">
                <table class="finance-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Due Date</th>
                        <th>Amount</th>
                        <th>Outstanding</th>
                        <th>Days Until Due</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($upcomingInstallments as $installment)
                        <tr>
                            <td>
                                <strong>{{ $installment->paymentPlan->student->first_name }} {{ $installment->paymentPlan->student->last_name }}</strong><br>
                                <small class="text-muted">{{ $installment->paymentPlan->student->admission_number }}</small>
                            </td>
                            <td>{{ $installment->paymentPlan->student->classroom->name ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($installment->due_date)->format('M d, Y') }}</td>
                            <td>KES {{ number_format($installment->amount, 2) }}</td>
                            <td><strong class="text-warning">KES {{ number_format($installment->outstanding, 2) }}</strong></td>
                            <td>
                                <span class="badge bg-{{ $installment->days_until_due <= 3 ? 'danger' : ($installment->days_until_due <= 7 ? 'warning' : 'info') }}">
                                    {{ $installment->days_until_due }} days
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $installment->status == 'paid' ? 'success' : ($installment->status == 'partial' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($installment->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('finance.fee-payment-plans.show', $installment->paymentPlan) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> View Plan
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-calendar-check fs-3"></i>
                                    <p class="mb-0 mt-2">No upcoming installments</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- High-Risk Plans --}}
    @if($highRiskPlans->count() > 0)
    <div class="finance-card finance-animate mb-4">
        <div class="finance-card-header">
            <h5 class="mb-0"><i class="bi bi-shield-exclamation text-danger me-2"></i> High-Risk Plans ({{ $highRiskPlans->count() }})</h5>
        </div>
        <div class="finance-card-body">
            <div class="table-responsive">
                <table class="finance-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Class</th>
                        <th>Total Amount</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>% Paid</th>
                        <th>Days Until Term End</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($highRiskPlans as $plan)
                        <tr>
                            <td>
                                <strong>{{ $plan->student->first_name }} {{ $plan->student->last_name }}</strong><br>
                                <small class="text-muted">{{ $plan->student->admission_number }}</small>
                            </td>
                            <td>{{ $plan->student->classroom->name ?? 'N/A' }}</td>
                            <td>KES {{ number_format($plan->total_amount, 2) }}</td>
                            <td>KES {{ number_format($plan->total_paid, 2) }}</td>
                            <td><strong class="text-danger">KES {{ number_format($plan->remaining_balance, 2) }}</strong></td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <div class="progress-bar bg-{{ $plan->percentage_paid >= 70 ? 'success' : ($plan->percentage_paid >= 50 ? 'warning' : 'danger') }}" 
                                         role="progressbar" 
                                         style="width: {{ $plan->percentage_paid }}%">
                                        {{ number_format($plan->percentage_paid, 1) }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-danger">{{ $plan->days_until_term_end }} days</span>
                            </td>
                            <td>
                                <a href="{{ route('finance.fee-payment-plans.show', $plan) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                                <a href="{{ route('finance.accountant-dashboard.student-history', $plan->student) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-clock-history"></i> History
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
    @endif
  </div>
</div>
@endsection

