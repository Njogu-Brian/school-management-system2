@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Daily Swimming Attendance Report',
        'icon' => 'bi bi-water',
        'subtitle' => 'View swimming attendance by date and classroom',
        'actions' => '<a href="' . route('swimming.attendance.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Mark Attendance</a>'
    ])

    @include('finance.invoices.partials.alerts')

    @if(auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
    <!-- Bulk Retry Payments -->
    @php
        $unpaidWithOptionalFees = $attendance->flatten()
            ->where('payment_status', 'unpaid')
            ->where('termly_fee_covered', true)
            ->where('session_cost', '>', 0);
    @endphp
    @if($unpaidWithOptionalFees->isNotEmpty())
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-body">
            <form method="POST" action="{{ route('swimming.attendance.bulk-retry-payments') }}" onsubmit="return confirm('Process payments for {{ $unpaidWithOptionalFees->count() }} unpaid attendance record(s) with optional fees? This will debit wallets for students who have sufficient balance.');">
                @csrf
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Unpaid Attendance with Optional Fees</h6>
                        <p class="text-muted small mb-0">
                            {{ $unpaidWithOptionalFees->count() }} record(s) found. 
                            Click to debit wallets for students who have sufficient balance.
                        </p>
                    </div>
                    <button type="submit" class="btn btn-finance btn-finance-success">
                        <i class="bi bi-arrow-clockwise"></i> Process Payments
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
    @endif

    <!-- Filters -->
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <form method="GET" action="{{ route('swimming.reports.daily-attendance') }}" class="row g-3">
            <div class="col-md-4">
                <label class="finance-form-label">Date</label>
                <input type="date" name="date" class="finance-form-control" value="{{ $selected_date }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">Classroom</label>
                <select name="classroom_id" class="finance-form-select" onchange="this.form.submit()">
                    <option value="">All Classrooms</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" {{ ($selected_classroom_id ?? '') == $classroom->id ? 'selected' : '' }}>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <a href="{{ route('swimming.reports.daily-attendance') }}" class="btn btn-finance btn-finance-secondary">
                    <i class="bi bi-x-circle"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Attendance by Classroom -->
    @if($attendance->isNotEmpty())
        @foreach($attendance as $classroomId => $records)
            @php
                $classroom = $records->first()->classroom ?? null;
            @endphp
            <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
                <div class="finance-card-header">
                    <h5 class="mb-0">
                        <i class="bi bi-people"></i> {{ $classroom->name ?? 'Unknown Classroom' }}
                        <span class="badge bg-info ms-2">{{ $records->count() }} student(s)</span>
                    </h5>
                </div>
                <div class="finance-card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Admission #</th>
                                    <th>Student Name</th>
                                    <th class="text-end">Amount</th>
                                    <th>Payment Status</th>
                                    <th>Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($records as $index => $record)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><strong>{{ $record->student->admission_number ?? 'N/A' }}</strong></td>
                                        <td>
                                            {{ $record->student->first_name ?? '' }} {{ $record->student->last_name ?? '' }}
                                        </td>
                                        <td class="text-end">
                                            <strong>Ksh {{ number_format($record->session_cost ?? 0, 2) }}</strong>
                                        </td>
                                        <td>
                                            @if($record->payment_status === 'paid')
                                                <span class="badge bg-success">Paid</span>
                                            @else
                                                <span class="badge bg-danger">Unpaid</span>
                                            @endif
                                        </td>
                                        <td>
                                            <small class="text-muted">{{ $record->created_at->format('H:i') }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong>Ksh {{ number_format($records->sum('session_cost'), 2) }}</strong></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Summary -->
        @php
            $totalStudents = $attendance->sum(function($records) { return $records->count(); });
            $totalAmount = $attendance->flatten()->sum('session_cost');
            $paidCount = $attendance->flatten()->where('payment_status', 'paid')->count();
            $unpaidCount = $attendance->flatten()->where('payment_status', 'unpaid')->count();
        @endphp
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="finance-stat-card border-primary finance-animate">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Students</h6>
                            <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">{{ $totalStudents }}</h4>
                        </div>
                        <i class="bi bi-people" style="font-size: 2rem; color: var(--finance-primary);"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="finance-stat-card border-success finance-animate">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Amount</h6>
                            <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">Ksh {{ number_format($totalAmount, 2) }}</h4>
                        </div>
                        <i class="bi bi-cash-stack" style="font-size: 2rem; color: var(--finance-success);"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="finance-stat-card border-success finance-animate">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Paid</h6>
                            <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">{{ $paidCount }}</h4>
                        </div>
                        <i class="bi bi-check-circle" style="font-size: 2rem; color: var(--finance-success);"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="finance-stat-card border-danger finance-animate">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Unpaid</h6>
                            <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">{{ $unpaidCount }}</h4>
                        </div>
                        <i class="bi bi-x-circle" style="font-size: 2rem; color: var(--finance-danger);"></i>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
            <div class="finance-card-body text-center py-5">
                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                <p class="text-muted mt-3 mb-0">No attendance records found for this date</p>
                <small class="text-muted">Try selecting a different date or classroom</small>
            </div>
        </div>
    @endif
  </div>
</div>
@endsection
