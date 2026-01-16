@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Swimming Attendance Records',
        'icon' => 'bi bi-water',
        'subtitle' => 'View and manage swimming attendance records',
        'actions' => '<a href="' . route('swimming.attendance.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Mark Attendance</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <form method="GET" action="{{ route('swimming.attendance.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="finance-form-label">Classroom</label>
                <select name="classroom_id" class="finance-form-select">
                    <option value="">All Classrooms</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" {{ ($filters['classroom_id'] ?? '') == $classroom->id ? 'selected' : '' }}>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">From Date</label>
                <input type="date" name="date_from" class="finance-form-control" value="{{ $filters['date_from'] ?? '' }}">
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">To Date</label>
                <input type="date" name="date_to" class="finance-form-control" value="{{ $filters['date_to'] ?? '' }}">
            </div>
            <div class="col-md-2">
                <label class="finance-form-label">Payment Status</label>
                <select name="payment_status" class="finance-form-select">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ ($filters['payment_status'] ?? '') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="unpaid" {{ ($filters['payment_status'] ?? '') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-finance btn-finance-primary flex-fill">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('swimming.attendance.index') }}" class="btn btn-finance btn-finance-secondary" title="Clear Filters">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Attendance Records Table -->
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
                                    {{ $record->student->first_name ?? '' }} {{ $record->student->last_name ?? '' }}
                                </td>
                                <td>
                                    <strong>{{ $record->student->admission_number ?? 'N/A' }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $record->classroom->name ?? 'N/A' }}</span>
                                </td>
                                <td class="text-end">
                                    <strong>Ksh {{ number_format($record->amount, 2) }}</strong>
                                </td>
                                <td>
                                    @if($record->payment_status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @else
                                        <span class="badge bg-danger">Unpaid</span>
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
  </div>
</div>
@endsection
