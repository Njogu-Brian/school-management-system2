@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Swimming Attendance Records & Reports',
        'icon' => 'bi bi-water',
        'subtitle' => 'View and manage swimming attendance records',
        'actions' => '<a href="' . route('swimming.wallets.index') . '" class="btn btn-finance btn-finance-secondary me-2"><i class="bi bi-wallet2"></i> Send Balances & Payment Links</a><a href="' . route('swimming.attendance.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Mark Attendance</a>'
    ])

    @include('finance.invoices.partials.alerts')

    @if(auth()->user()->hasAnyRole(['Super Admin', 'Admin']) && isset($view_mode) && $view_mode === 'daily')
    <!-- Send Payment Reminders - Only shown in daily view -->
    @php
        // Unpaid = payment_status = unpaid OR wallet balance < 0 (student owes money)
        $unpaidAttendance = $attendance->flatten()->filter(function($record) {
            if ($record->payment_status === 'unpaid') {
                return true;
            }
            // Check wallet balance if available
            $walletBalance = $record->wallet_balance ?? 0;
            if ($walletBalance < 0) {
                return true; // Student owes money even if marked as paid
            }
            return false;
        })->where('session_cost', '>', 0);
        $totalUnpaidAmount = $unpaidAttendance->sum('session_cost');
    @endphp
    @if($unpaidAttendance->isNotEmpty())
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-body">
            <form method="POST" action="{{ route('swimming.attendance.send-payment-reminders') }}" onsubmit="return confirm('Send payment reminders to parents for {{ $unpaidAttendance->count() }} unpaid attendance record(s)?');">
                @csrf
                <input type="hidden" name="date" value="{{ $selected_date }}">
                <input type="hidden" name="classroom_id" value="{{ $selected_classroom_id ?? '' }}">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Unpaid Swimming Attendance</h6>
                        <p class="text-muted small mb-0">
                            {{ $unpaidAttendance->count() }} record(s) unpaid. 
                            Total amount: Ksh {{ number_format($totalUnpaidAmount, 2) }}. 
                            Send payment reminders to parents via SMS/Email/WhatsApp.
                        </p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="channelSMS" checked>
                            <label class="form-check-label" for="channelSMS">SMS</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="channelEmail" checked>
                            <label class="form-check-label" for="channelEmail">Email</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" id="channelWhatsApp">
                            <label class="form-check-label" for="channelWhatsApp">WhatsApp</label>
                        </div>
                        <button type="submit" class="btn btn-finance btn-finance-success">
                            <i class="bi bi-send"></i> Send Reminders
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif
    @endif

    <!-- Filters -->
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <form method="GET" action="{{ route('swimming.attendance.index') }}" class="row g-3">
            <div class="col-md-2">
                <label class="finance-form-label">Date (for daily view)</label>
                <input type="date" name="date" class="finance-form-control" value="{{ $filters['date'] ?? ($selected_date ?? '') }}" placeholder="Single date">
            </div>
            <div class="col-md-2">
                <label class="finance-form-label">Classroom</label>
                <select name="classroom_id" class="finance-form-select">
                    <option value="">All Classrooms</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" {{ ($filters['classroom_id'] ?? ($selected_classroom_id ?? '')) == $classroom->id ? 'selected' : '' }}>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="finance-form-label">From Date</label>
                <input type="date" name="date_from" class="finance-form-control" value="{{ $filters['date_from'] ?? '' }}" placeholder="Start date">
            </div>
            <div class="col-md-2">
                <label class="finance-form-label">To Date</label>
                <input type="date" name="date_to" class="finance-form-control" value="{{ $filters['date_to'] ?? '' }}" placeholder="End date">
            </div>
            <div class="col-md-2">
                <label class="finance-form-label">Payment Status</label>
                <select name="payment_status" class="finance-form-select">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ ($filters['payment_status'] ?? '') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ ($filters['payment_status'] ?? '') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="unpaid" {{ ($filters['payment_status'] ?? '') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-finance btn-finance-primary flex-fill">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('swimming.attendance.index') }}" class="btn btn-finance btn-finance-secondary" title="Clear Filters">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>

    @if(isset($view_mode) && $view_mode === 'daily')
        <!-- Daily Report View - Grouped by Classroom -->
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
                                                {{ $record->student->full_name ?? '' }}
                                            </td>
                                            <td class="text-end">
                                                <strong>Ksh {{ number_format($record->session_cost ?? 0, 2) }}</strong>
                                            </td>
                                            <td>
                                                @php
                                                    $walletBalance = $record->wallet_balance ?? 0;
                                                    $sessionCost = $record->session_cost ?? 0;
                                                    
                                                    // Paid: wallet balance >= 0 (they have money or are even)
                                                    // Partial: wallet balance is negative but less than session cost (they paid something)
                                                    // Unpaid: wallet balance is negative and equals or exceeds session cost (they haven't paid)
                                                    if ($walletBalance >= 0) {
                                                        $statusClass = 'bg-success';
                                                        $statusText = 'Paid';
                                                    } elseif ($walletBalance < 0 && abs($walletBalance) < $sessionCost) {
                                                        $statusClass = 'bg-warning text-dark';
                                                        $statusText = 'Partial';
                                                    } else {
                                                        $statusClass = 'bg-danger';
                                                        $statusText = 'Unpaid';
                                                    }
                                                @endphp
                                                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                                @if($walletBalance < 0)
                                                    <br><small class="text-danger">Bal: Ksh {{ number_format($walletBalance, 2) }}</small>
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

            <!-- Summary Statistics -->
            @php
                $totalStudents = $attendance->sum(function($records) { return $records->count(); });
                $totalAmount = $attendance->flatten()->sum('session_cost');
                
                // Count based on actual wallet balance
                $paidCount = $attendance->flatten()->filter(function($record) {
                    $walletBalance = $record->wallet_balance ?? 0;
                    return $walletBalance >= 0;
                })->count();
                
                $partialCount = $attendance->flatten()->filter(function($record) {
                    $walletBalance = $record->wallet_balance ?? 0;
                    $sessionCost = $record->session_cost ?? 0;
                    return $walletBalance < 0 && abs($walletBalance) < $sessionCost;
                })->count();
                
                $unpaidCount = $attendance->flatten()->filter(function($record) {
                    $walletBalance = $record->wallet_balance ?? 0;
                    $sessionCost = $record->session_cost ?? 0;
                    return $walletBalance < 0 && abs($walletBalance) >= $sessionCost;
                })->count();
            @endphp
            <div class="row g-3 mb-4">
                <div class="col-md-2">
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
                    <div class="finance-stat-card border-info finance-animate">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Amount</h6>
                                <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">Ksh {{ number_format($totalAmount, 2) }}</h4>
                            </div>
                            <i class="bi bi-cash-stack" style="font-size: 2rem; color: var(--finance-info);"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <div class="finance-stat-card border-warning finance-animate">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Partial</h6>
                                <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">{{ $partialCount }}</h4>
                            </div>
                            <i class="bi bi-hourglass-split" style="font-size: 2rem; color: var(--finance-warning);"></i>
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
    @else
        <!-- List View - Paginated Records -->
        <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
            <div class="finance-card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">Attendance Records</h5>
                    <p class="text-muted small mb-0">{{ $attendance->total() }} record(s) found</p>
                </div>
            </div>
            <div class="finance-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-modern align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Admission #</th>
                                <th>Classroom</th>
                                <th class="text-end">Amount</th>
                                <th>Payment Status</th>
                                <th>Created At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendance as $record)
                                <tr>
                                    <td>
                                        <strong>{{ $record->attendance_date->format('d M Y') }}</strong>
                                    </td>
                                    <td>
                                        {{ $record->student->full_name ?? '' }}
                                    </td>
                                    <td>
                                        <strong>{{ $record->student->admission_number ?? 'N/A' }}</strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $record->classroom->name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="text-end">
                                        <strong>Ksh {{ number_format($record->session_cost ?? 0, 2) }}</strong>
                                    </td>
                                    <td>
                                        @php
                                            // Get actual wallet balance to determine true payment status
                                            $walletBalance = $record->wallet_balance ?? \App\Models\SwimmingWallet::getOrCreateForStudent($record->student_id)->balance ?? 0;
                                            $sessionCost = $record->session_cost ?? 0;
                                            
                                            // Paid: wallet balance >= 0 (they have money or are even)
                                            // Partial: wallet balance is negative but less than session cost (they paid something)
                                            // Unpaid: wallet balance is negative and equals or exceeds session cost (they haven't paid)
                                            if ($walletBalance >= 0) {
                                                $statusClass = 'bg-success';
                                                $statusText = 'Paid';
                                            } elseif ($walletBalance < 0 && abs($walletBalance) < $sessionCost) {
                                                $statusClass = 'bg-warning text-dark';
                                                $statusText = 'Partial';
                                            } else {
                                                $statusClass = 'bg-danger';
                                                $statusText = 'Unpaid';
                                            }
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                                        @if($walletBalance < 0)
                                            <br><small class="text-danger">Bal: Ksh {{ number_format($walletBalance, 2) }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $record->created_at->format('d M Y H:i') }}</small>
                                    </td>
                                    <td class="text-end">
                                        @if($record->payment_status === 'unpaid')
                                            <form action="{{ route('swimming.attendance.retry-payment', $record) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-finance btn-finance-warning" title="Retry Payment">
                                                    <i class="bi bi-arrow-clockwise"></i> Retry
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <div class="text-muted">
                                            <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                            <p class="mt-3 mb-0">No attendance records found</p>
                                            <small>Try adjusting your filters</small>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($attendance->hasPages())
                <div class="finance-card-footer">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $attendance->firstItem() }} to {{ $attendance->lastItem() }} of {{ $attendance->total() }} records
                        </div>
                        <div>
                            {{ $attendance->links() }}
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif
  </div>
</div>
@endsection
