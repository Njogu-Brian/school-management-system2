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
            white-space: nowrap;
            display: inline-block;
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
            background: rgba(239, 68, 68, 0.04);
        }

        .fiscal-task-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .fiscal-task-badge .fiscal-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .fiscal-task-green { background: rgba(16, 185, 129, 0.15); color: #047857; }
        .fiscal-task-green .fiscal-dot { background: #10b981; }
        .fiscal-task-yellow { background: rgba(245, 158, 11, 0.18); color: #b45309; }
        .fiscal-task-yellow .fiscal-dot { background: #f59e0b; }
        .fiscal-task-red { background: rgba(239, 68, 68, 0.15); color: #b91c1c; }
        .fiscal-task-red .fiscal-dot { background: #ef4444; }

        .fee-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .fee-entity-card {
            background: var(--fin-surface);
            border: 1px solid var(--fin-border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
        }
        .fee-entity-card.is-family {
            border-color: rgba(37, 99, 235, 0.28);
        }
        .fee-entity-card.has-highlight {
            box-shadow: inset 3px 0 0 #ef4444;
        }

        .fee-family-head {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            justify-content: space-between;
            padding: 14px 16px;
            background: linear-gradient(180deg, rgba(37, 99, 235, 0.08), rgba(37, 99, 235, 0.02));
            border-bottom: 1px solid rgba(37, 99, 235, 0.12);
        }
        .fee-family-title-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 6px;
        }
        .fee-family-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #2563eb;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 999px;
        }
        .fee-family-names {
            font-weight: 700;
            font-size: 1rem;
            color: var(--fin-text, #0f172a);
            line-height: 1.35;
            margin-bottom: 4px;
        }
        .fee-family-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 14px;
            color: var(--fin-muted);
            font-size: 0.85rem;
        }
        .fee-family-totals {
            display: grid;
            grid-template-columns: repeat(3, minmax(90px, 1fr));
            gap: 10px 14px;
            align-content: start;
            min-width: min(100%, 420px);
        }
        .fee-family-action {
            grid-column: 1 / -1;
            justify-self: start;
        }

        .fee-metric {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .fee-metric-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: var(--fin-muted);
            font-weight: 700;
        }
        .fee-metric-value {
            font-size: 1rem;
            font-weight: 800;
            line-height: 1.2;
        }
        .fee-metric-emphasis .fee-metric-value {
            font-size: 1.15rem;
        }

        .fee-children {
            display: flex;
            flex-direction: column;
        }

        .fee-child-card {
            display: grid;
            grid-template-columns: minmax(180px, 1.4fr) minmax(220px, 1.2fr) minmax(160px, 1fr) auto;
            gap: 12px 16px;
            align-items: start;
            padding: 14px 16px;
            border-top: 1px solid rgba(15, 23, 42, 0.06);
        }
        .fee-entity-card:not(.is-family) .fee-child-card {
            border-top: 0;
        }
        .fee-child-card.is-child {
            padding-left: 22px;
            background: rgba(248, 250, 252, 0.7);
        }

        .fee-child-name-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            margin-bottom: 4px;
        }
        .fee-child-name { font-size: 0.98rem; }
        .fee-child-sub {
            display: flex;
            flex-wrap: wrap;
            gap: 8px 12px;
            color: var(--fin-muted);
            font-size: 0.82rem;
        }
        .fee-adm { font-weight: 700; color: #334155; }

        .fee-child-figures {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        @media (min-width: 1200px) {
            .fee-child-figures {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .fee-promise-form {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
            margin: 0;
        }
        .fee-promise-form .form-control {
            width: auto;
            min-width: 9.5rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.8rem;
        }

        .fee-child-status {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }

        .fee-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            background: rgba(148, 163, 184, 0.16);
            color: #475569;
            white-space: nowrap;
        }
        .fee-chip-ok { background: rgba(16, 185, 129, 0.14); color: #047857; }
        .fee-chip-warn { background: rgba(245, 158, 11, 0.16); color: #b45309; }
        .fee-chip-info { background: rgba(59, 130, 246, 0.14); color: #1d4ed8; }
        .fee-chip-muted { background: rgba(239, 68, 68, 0.1); color: #b91c1c; }

        .fee-child-actions { justify-self: end; align-self: center; }

        @media (max-width: 991.98px) {
            .fee-child-card {
                grid-template-columns: 1fr;
            }
            .fee-family-totals {
                width: 100%;
                min-width: 0;
                grid-template-columns: repeat(3, 1fr);
            }
            .fee-child-actions {
                justify-self: start;
            }
            .stat-value { font-size: 1.35rem; }
        }

        @media (max-width: 575.98px) {
            .fee-balance-page { padding: 12px 0; }
            .fee-family-head, .fee-child-card { padding: 12px; }
            .fee-child-card.is-child { padding-left: 12px; }
            .fee-family-totals, .fee-child-figures {
                grid-template-columns: 1fr 1fr;
            }
            .fee-metric-emphasis {
                grid-column: 1 / -1;
            }
            .nav-tabs-finance {
                flex-wrap: nowrap;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            .nav-tabs-finance .nav-link {
                white-space: nowrap;
            }
        }
    </style>
@endpush

@section('content')
<div class="fee-balance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Fee Balance Report',
            'icon' => 'bi bi-cash-stack',
            'subtitle' => 'Term invoiced/paid · combined outstanding balance · siblings grouped',
            'actions' => '
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="feeBalanceIncludeAmounts" checked>
                        <label class="form-check-label small" for="feeBalanceIncludeAmounts">Include amounts</label>
                    </div>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#feeBalanceExcludeModal">
                        <i class="bi bi-person-x"></i> Exclude students
                    </button>
                    <span id="feeBalanceExcludeDisplay" class="badge bg-secondary d-none"><span id="feeBalanceExcludeBadge">0</span> excluded</span>
                    <input type="hidden" id="feeBalanceExcludeIds" value="">
                    <div class="btn-group">
                        <a href="#" data-format="print" data-target="_blank" class="btn btn-finance btn-finance-outline fee-export-btn"><i class="bi bi-printer"></i> Print</a>
                        <a href="#" data-format="pdf" class="btn btn-finance btn-finance-outline fee-export-btn"><i class="bi bi-file-pdf"></i> Export PDF</a>
                        <a href="#" data-format="csv" class="btn btn-finance btn-finance-outline fee-export-btn"><i class="bi bi-download"></i> Export CSV</a>
                    </div>
                </div>
                <small class="d-block mt-1 text-muted"><i class="bi bi-info-circle"></i> Staff children excluded automatically.</small>'
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
                    <small class="text-muted">Selected term (invoiced totals)</small>
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
            <div class="col-md-3">
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
            <div class="col-md-3">
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
            <div class="col-md-3">
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
            <div class="col-md-3">
                <div class="alert alert-primary border-0 shadow-sm">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-arrow-left-circle-fill fs-3 me-3"></i>
                        <div>
                            <h6 class="mb-1 fw-bold">Balance Brought Forward</h6>
                            <p class="mb-0">
                                <strong>{{ $summary['students_with_bbf'] ?? 0 }}</strong> students with BBF of 
                                <strong>Ksh {{ number_format($summary['total_bbf_balance'] ?? 0, 0) }}</strong> outstanding
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- View Tabs --}}
        <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
            <div class="card-body p-0">
                <ul class="nav nav-tabs nav-tabs-finance" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link {{ ($view ?? 'all') == 'all' ? 'active' : '' }}" href="{{ route('finance.fee-balances.index', ['view' => 'all'] + request()->except('view')) }}">
                            All <span class="badge bg-secondary">{{ $counts['all'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($view ?? 'all') == 'cleared' ? 'active' : '' }}" href="{{ route('finance.fee-balances.index', ['view' => 'cleared'] + request()->except('view')) }}">
                            Cleared <span class="badge bg-success">{{ $counts['cleared'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($view ?? 'all') == 'partial' ? 'active' : '' }}" href="{{ route('finance.fee-balances.index', ['view' => 'partial'] + request()->except('view')) }}">
                            Partial <span class="badge bg-warning">{{ $counts['partial'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($view ?? 'all') == 'unpaid-present' ? 'active' : '' }}" href="{{ route('finance.fee-balances.index', ['view' => 'unpaid-present'] + request()->except('view')) }}">
                            Unpaid - Present <span class="badge bg-danger">{{ $counts['unpaid-present'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($view ?? 'all') == 'unpaid-absent' ? 'active' : '' }}" href="{{ route('finance.fee-balances.index', ['view' => 'unpaid-absent'] + request()->except('view')) }}">
                            Unpaid - Absent <span class="badge bg-danger">{{ $counts['unpaid-absent'] ?? 0 }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link {{ ($view ?? 'all') == 'with-bbf' ? 'active' : '' }}" href="{{ route('finance.fee-balances.index', ['view' => 'with-bbf'] + request()->except('view')) }}">
                            With BBF <span class="badge bg-primary">{{ $counts['with-bbf'] ?? 0 }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Filters --}}
        <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
            <form method="GET" action="{{ route('finance.fee-balances.index') }}" class="row g-3">
                <input type="hidden" name="view" value="{{ $view ?? 'all' }}">
                <div class="col-md-3">
                    <label class="finance-form-label">Term</label>
                    <select name="term_id" class="finance-form-select">
                        @foreach($terms ?? collect() as $t)
                            <option value="{{ $t->id }}" {{ ($selectedTermId ?? null) == $t->id ? 'selected' : '' }}>
                                {{ $t->name }} ({{ optional($t->academicYear)->year ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
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
                <div class="col-md-2">
                    <label class="finance-form-label">Balance Brought Forward</label>
                    <select name="bbf_filter" class="finance-form-select">
                        <option value="">All</option>
                        <option value="has_bbf" {{ request('bbf_filter') === 'has_bbf' ? 'selected' : '' }}>Has BBF</option>
                        <option value="no_bbf" {{ request('bbf_filter') === 'no_bbf' ? 'selected' : '' }}>No BBF</option>
                        <option value="bbf_cleared" {{ request('bbf_filter') === 'bbf_cleared' ? 'selected' : '' }}>BBF Cleared</option>
                        <option value="bbf_unpaid" {{ request('bbf_filter') === 'bbf_unpaid' ? 'selected' : '' }}>BBF Unpaid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="finance-form-label">Fiscal Task</label>
                    <select name="fiscal_task_filter" class="finance-form-select">
                        <option value="">All</option>
                        <option value="green" {{ request('fiscal_task_filter') === 'green' ? 'selected' : '' }}>Green (on track)</option>
                        <option value="yellow" {{ request('fiscal_task_filter') === 'yellow' ? 'selected' : '' }}>Yellow (due soon)</option>
                        <option value="red" {{ request('fiscal_task_filter') === 'red' ? 'selected' : '' }}>Red (follow up)</option>
                        <option value="no_promise" {{ request('fiscal_task_filter') === 'no_promise' ? 'selected' : '' }}>No promise date</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-flex gap-2 w-100">
                        <button type="submit" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('finance.fee-balances.index', ['view' => $view ?? 'all']) }}" class="btn btn-finance btn-finance-outline">
                            <i class="bi bi-x-circle"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- Family / student cards --}}
        <div class="fee-list finance-animate">
            @forelse(($familyGroups ?? collect()) as $group)
                @include('finance.fee_balances.partials.family_group', ['group' => $group])
            @empty
                <div class="text-center py-5 finance-card rounded-4 border-0">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <p class="text-muted mt-2 mb-0">No students found matching your criteria.</p>
                </div>
            @endforelse
        </div>

        {{-- Legend --}}
        <div class="alert alert-info border-0 mt-4">
            <h6 class="fw-bold mb-2"><i class="bi bi-info-circle me-2"></i>How to read this report</h6>
            <div class="row">
                <div class="col-md-6">
                    <ul class="mb-0">
                        <li><strong>Term invoiced / on this term invoice:</strong> Selected term invoice totals</li>
                        <li><strong>Paid in term period:</strong> Cash received during the term dates (may clear prior dues)</li>
                        <li><strong>Outstanding / Family balance:</strong> All fees still owing (year statement)</li>
                        <li><strong>Promise:</strong> Set or update last promised date from this screen</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul class="mb-0">
                        <li><strong>Fiscal Task:</strong>
                            <span class="fiscal-task-badge fiscal-task-green"><span class="fiscal-dot"></span> On track</span>,
                            <span class="fiscal-task-badge fiscal-task-yellow"><span class="fiscal-dot"></span> Due soon</span>,
                            <span class="fiscal-task-badge fiscal-task-red"><span class="fiscal-dot"></span> Follow up</span>
                        </li>
                        <li><strong>All tab:</strong> Hides fully cleared (zero balance) students</li>
                        <li><strong>Term Start:</strong> {{ $currentTerm?->opening_date ? $currentTerm->opening_date->format('M d, Y') : 'Not set' }}</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

@include('finance.fee_balances.partials.exclude_modal', [
    'students' => $students,
    'classrooms' => $classrooms,
])

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const baseParams = @json(request()->except('exclude_ids', 'include_amounts'));
    const exportBtns = document.querySelectorAll('.fee-export-btn');
    const includeAmountsCheck = document.getElementById('feeBalanceIncludeAmounts');
    const excludeIdsInput = document.getElementById('feeBalanceExcludeIds');

    exportBtns.forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const excludeIds = (excludeIdsInput && excludeIdsInput.value) ? excludeIdsInput.value.split(',').filter(Boolean) : [];
            const includeAmounts = includeAmountsCheck ? includeAmountsCheck.checked : true;
            const params = new URLSearchParams();
            Object.keys(baseParams).forEach(function(k) {
                const v = baseParams[k];
                if (Array.isArray(v)) v.forEach(x => params.append(k + '[]', x));
                else if (v != null && v !== '') params.set(k, v);
            });
            params.set('format', btn.dataset.format);
            params.set('include_amounts', includeAmounts ? '1' : '0');
            excludeIds.forEach(id => params.append('exclude_ids[]', id));
            const url = '{{ url("/finance/fee-balances/export") }}?' + params.toString();
            if (btn.dataset.target === '_blank') window.open(url);
            else window.location = url;
        });
    });

    // Fee balance exclude modal logic
    const modal = document.getElementById('feeBalanceExcludeModal');
    if (modal) {
        const searchInput = document.getElementById('feeBalanceExcludeSearchInput');
        const classFilter = document.getElementById('feeBalanceExcludeClassFilter');
        const selectAllBtn = document.getElementById('feeBalanceExcludeSelectAll');
        const clearAllBtn = document.getElementById('feeBalanceExcludeClearAll');
        const confirmBtn = document.getElementById('feeBalanceExcludeConfirm');
        const countBadge = document.getElementById('feeBalanceExcludeCount');
        const displayBadge = document.getElementById('feeBalanceExcludeDisplay');
        const badgeNum = document.getElementById('feeBalanceExcludeBadge');
        const noResults = document.getElementById('feeBalanceExcludeNoResults');
        const allItems = document.querySelectorAll('.fee-balance-exclude-item');
        const allCheckboxes = document.querySelectorAll('.fee-balance-exclude-checkbox');

        function updateCount() {
            const checked = document.querySelectorAll('.fee-balance-exclude-checkbox:checked');
            const ids = Array.from(checked).map(cb => cb.value);
            if (countBadge) countBadge.textContent = ids.length + ' excluded';
            if (excludeIdsInput) excludeIdsInput.value = ids.join(',');
            if (displayBadge) displayBadge.classList.toggle('d-none', ids.length === 0);
            if (badgeNum) badgeNum.textContent = ids.length;
        }

        function filterList() {
            const search = (searchInput && searchInput.value) ? searchInput.value.toLowerCase().trim() : '';
            const classId = (classFilter && classFilter.value) ? classFilter.value : '';
            let visible = 0;
            allItems.forEach(function(item) {
                const matchSearch = !search || (item.dataset.studentName || '').includes(search) || (item.dataset.admission || '').includes(search) || (item.dataset.className || '').includes(search);
                const matchClass = !classId || (item.dataset.classId || '') === classId;
                if (matchSearch && matchClass) {
                    item.classList.remove('d-none');
                    visible++;
                } else {
                    item.classList.add('d-none');
                }
            });
            if (noResults) noResults.classList.toggle('d-none', visible > 0);
        }

        if (searchInput) searchInput.addEventListener('input', filterList);
        if (classFilter) classFilter.addEventListener('change', filterList);
        if (selectAllBtn) selectAllBtn.addEventListener('click', function() {
            document.querySelectorAll('.fee-balance-exclude-item:not(.d-none) .fee-balance-exclude-checkbox').forEach(cb => { cb.checked = true; });
            updateCount();
        });
        if (clearAllBtn) clearAllBtn.addEventListener('click', function() {
            allCheckboxes.forEach(cb => { cb.checked = false; });
            updateCount();
        });
        allCheckboxes.forEach(cb => cb.addEventListener('change', updateCount));
        if (confirmBtn) confirmBtn.addEventListener('click', function() {
            updateCount();
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        });
        modal.addEventListener('hidden.bs.modal', function() {
            if (searchInput) searchInput.value = '';
            if (classFilter) classFilter.value = '';
            filterList();
        });
        updateCount();
    }
});
</script>
@endpush
@endsection

