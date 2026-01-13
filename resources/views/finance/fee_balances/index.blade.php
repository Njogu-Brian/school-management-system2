@extends('layouts.app')

@push('styles')
    @include('finance.partials.styles')
    <style>
        .fee-balance-page {
            background: var(--fin-bg);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .stat-card {
            background: var(--fin-surface);
            border: 1px solid var(--fin-border);
            border-radius: 14px;
            padding: 20px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 12px -1px rgba(0, 0, 0, 0.1);
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--fin-muted);
            font-weight: 600;
        }
        
        .badge-in-school {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-not-reported {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-has-plan {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .progress-thin {
            height: 6px;
            border-radius: 10px;
            background: rgba(0,0,0,0.05);
        }
        
        .progress-thin .progress-bar {
            border-radius: 10px;
        }
        
        .table-actions .btn {
            padding: 4px 10px;
            font-size: 0.8rem;
        }
        
        .highlight-row {
            background: rgba(239, 68, 68, 0.05) !important;
        }
    </style>
@endpush

@section('content')
<div class="fee-balance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Fee Balance Report',
            'icon' => 'bi bi-cash-stack',
            'subtitle' => 'Track student fee balances and attendance status',
            'actions' => '<a href="' . route('finance.fee-balances.export', request()->all()) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-download"></i> Export Report</a>'
        ])

        {{-- Summary Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="stat-card border-primary">
                    <div class="stat-value text-primary">{{ $summary['total_students'] }}</div>
                    <div class="stat-label">Total Students</div>
                    <small class="text-success">
                        <i class="bi bi-check-circle"></i> {{ $summary['students_in_school'] }} in school
                    </small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-info">
                    <div class="stat-value text-info">Ksh {{ number_format($summary['total_invoiced'], 0) }}</div>
                    <div class="stat-label">Total Invoiced</div>
                    <small class="text-muted">Current term</small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-success">
                    <div class="stat-value text-success">Ksh {{ number_format($summary['total_paid'], 0) }}</div>
                    <div class="stat-label">Total Collected</div>
                    <small class="text-muted">
                        {{ $summary['total_invoiced'] > 0 ? round(($summary['total_paid'] / $summary['total_invoiced']) * 100, 1) : 0 }}% collection rate
                    </small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card border-danger">
                    <div class="stat-value text-danger">Ksh {{ number_format($summary['total_balance'], 0) }}</div>
                    <div class="stat-label">Outstanding Balance</div>
                    <small class="text-warning">
                        <i class="bi bi-exclamation-triangle"></i> {{ $summary['students_with_balance'] }} students with balance
                    </small>
                </div>
            </div>
        </div>

        {{-- Key Insights --}}
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="alert alert-warning border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-exclamation-triangle-fill fs-3 me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Students in School with Balance</h6>
                            <p class="mb-0">
                                <strong>{{ $summary['in_school_with_balance'] }}</strong> students are attending with outstanding balance of 
                                <strong>Ksh {{ number_format($summary['in_school_balance_amount'], 2) }}</strong>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-info border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-calendar-check-fill fs-3 me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Payment Plans</h6>
                            <p class="mb-0">
                                <strong>{{ $summary['students_with_plans'] }}</strong> students have active payment plans
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert alert-success border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-check-circle-fill fs-3 me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Cleared Accounts</h6>
                            <p class="mb-0">
                                <strong>{{ $summary['students_cleared'] }}</strong> students have cleared their fees
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="finance-filter-card finance-animate">
            <form method="GET" action="{{ route('finance.fee-balances.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="finance-form-label">Classroom</label>
                    <select name="classroom_id" class="finance-form-select">
                        <option value="">All Classrooms</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="finance-form-label">Balance Status</label>
                    <select name="balance_status" class="finance-form-select">
                        <option value="">All</option>
                        <option value="with_balance" {{ request('balance_status') === 'with_balance' ? 'selected' : '' }}>With Balance</option>
                        <option value="cleared" {{ request('balance_status') === 'cleared' ? 'selected' : '' }}>Cleared</option>
                        <option value="overpaid" {{ request('balance_status') === 'overpaid' ? 'selected' : '' }}>Overpaid</option>
                        <option value="not_invoiced" {{ request('balance_status') === 'not_invoiced' ? 'selected' : '' }}>Not Invoiced</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="finance-form-label">Attendance</label>
                    <select name="attendance_filter" class="finance-form-select">
                        <option value="">All</option>
                        <option value="in_school" {{ request('attendance_filter') === 'in_school' ? 'selected' : '' }}>In School</option>
                        <option value="not_reported" {{ request('attendance_filter') === 'not_reported' ? 'selected' : '' }}>Not Reported</option>
                        <option value="poor_attendance" {{ request('attendance_filter') === 'poor_attendance' ? 'selected' : '' }}>Poor Attendance (&lt;75%)</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="finance-form-label">Payment Plan</label>
                    <select name="payment_plan_filter" class="finance-form-select">
                        <option value="">All</option>
                        <option value="has_plan" {{ request('payment_plan_filter') === 'has_plan' ? 'selected' : '' }}>Has Plan</option>
                        <option value="no_plan" {{ request('payment_plan_filter') === 'no_plan' ? 'selected' : '' }}>No Plan</option>
                        <option value="plan_overdue" {{ request('payment_plan_filter') === 'plan_overdue' ? 'selected' : '' }}>Plan Overdue</option>
                        <option value="plan_on_track" {{ request('payment_plan_filter') === 'plan_on_track' ? 'selected' : '' }}>Plan On Track</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="finance-form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('finance.fee-balances.index') }}" class="btn btn-finance btn-finance-outline">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Students Table --}}
        <div class="finance-table-wrapper finance-animate">
            <div class="table-responsive">
                <table class="finance-table">
                    <thead>
                        <tr>
                            <th>Adm No</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th class="text-end">Invoiced</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Attendance</th>
                            <th class="text-center">In School</th>
                            <th class="text-center">Payment Plan</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($students as $student)
                            <tr class="{{ $student['is_in_school'] && $student['balance'] > 1000 ? 'highlight-row' : '' }}">
                                <td><strong>{{ $student['admission_number'] }}</strong></td>
                                <td>
                                    <div>
                                        <strong>{{ $student['full_name'] }}</strong>
                                        <br><small class="text-muted"><i class="bi bi-telephone"></i> {{ $student['parent_phone'] }}</small>
                                    </div>
                                </td>
                                <td>
                                    {{ $student['classroom'] }}
                                    @if($student['stream'])
                                        <br><small class="text-muted">{{ $student['stream'] }}</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <strong>Ksh {{ number_format($student['total_invoiced'], 2) }}</strong>
                                </td>
                                <td class="text-end text-success">
                                    <strong>Ksh {{ number_format($student['total_paid'], 2) }}</strong>
                                </td>
                                <td class="text-end">
                                    <strong class="{{ $student['balance'] > 0 ? 'text-danger' : 'text-success' }}">
                                        Ksh {{ number_format($student['balance'], 2) }}
                                    </strong>
                                    @if($student['balance'] > 0 && $student['total_invoiced'] > 0)
                                        <br><small class="text-muted">{{ $student['balance_percentage'] }}% owing</small>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @php
                                        $statusColors = [
                                            'paid' => 'success',
                                            'partial' => 'warning',
                                            'unpaid' => 'danger',
                                            'not_invoiced' => 'secondary'
                                        ];
                                        $statusColor = $statusColors[$student['payment_status']] ?? 'secondary';
                                    @endphp
                                    <span class="finance-badge badge-{{ $statusColor }}">
                                        {{ ucfirst(str_replace('_', ' ', $student['payment_status'])) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="mb-1">
                                        <strong>{{ $student['attendance_rate'] }}%</strong>
                                    </div>
                                    <div class="progress-thin">
                                        <div class="progress-bar {{ $student['attendance_rate'] >= 75 ? 'bg-success' : ($student['attendance_rate'] >= 50 ? 'bg-warning' : 'bg-danger') }}" 
                                             style="width: {{ $student['attendance_rate'] }}%"></div>
                                    </div>
                                    <small class="text-muted">{{ $student['days_present'] }}/{{ $student['attendance_days'] }} days</small>
                                </td>
                                <td class="text-center">
                                    @if($student['is_in_school'])
                                        <span class="badge-in-school">
                                            <i class="bi bi-check-circle"></i> In School
                                        </span>
                                    @else
                                        <span class="badge-not-reported">
                                            <i class="bi bi-x-circle"></i> Not Reported
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($student['has_payment_plan'])
                                        <span class="badge-has-plan">
                                            <i class="bi bi-calendar-check"></i> Has Plan
                                        </span>
                                        <br><small class="text-muted">{{ $student['payment_plan_progress'] }}% paid</small>
                                        @if($student['next_installment_date'])
                                            <br><small class="text-muted">Next: {{ $student['next_installment_date']->format('M d') }}</small>
                                        @endif
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center table-actions">
                                    <div class="btn-group btn-group-sm">
                                        @if($student['invoice_id'])
                                            <a href="{{ route('finance.invoices.show', $student['invoice_id']) }}" 
                                               class="btn btn-outline-primary" title="View Invoice">
                                                <i class="bi bi-file-text"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('finance.student-statements.show', $student['id']) }}" 
                                           class="btn btn-outline-info" title="View Statement">
                                            <i class="bi bi-receipt"></i>
                                        </a>
                                        @if($student['balance'] > 0 && !$student['has_payment_plan'])
                                            <a href="{{ route('finance.fee-payment-plans.create') }}?student_id={{ $student['id'] }}" 
                                               class="btn btn-outline-success" title="Create Payment Plan">
                                                <i class="bi bi-calendar-plus"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center py-5">
                                    <i class="bi bi-inbox fs-1 text-muted"></i>
                                    <p class="text-muted mt-2">No students found matching your criteria.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Legend --}}
        <div class="alert alert-info border-0 mt-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2"></i>Report Information</h6>
            <div class="row">
                <div class="col-md-6">
                    <ul class="mb-0">
                        <li><strong>In School:</strong> Student has been marked present at least once since term started</li>
                        <li><strong>Attendance Rate:</strong> Percentage of days present out of total marked days</li>
                        <li><strong>Highlighted rows:</strong> Students in school with balance > Ksh 1,000</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="mb-0">
                        <li><strong>Payment Plan:</strong> Student has an active installment payment plan</li>
                        <li><strong>Plan Progress:</strong> Percentage of installments paid</li>
                        <li><strong>Term Start:</strong> {{ $currentTerm ? $currentTerm->opening_date->format('M d, Y') : 'Not set' }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

