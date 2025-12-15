@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-cash-stack"></i> Payments
                </h3>
                <a href="{{ route('finance.payments.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Record Payment
                </a>
            </div>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.payments.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All Students</option>
                        @foreach(\App\Models\Student::orderBy('first_name')->get() as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->first_name }} {{ $student->last_name }} ({{ $student->admission_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payment Method</label>
                    <select name="payment_method_id" class="form-select">
                        <option value="">All Methods</option>
                        @foreach(\App\Models\PaymentMethod::where('is_active', true)->get() as $method)
                            <option value="{{ $method->id }}" {{ request('payment_method_id') == $method->id ? 'selected' : '' }}>
                                {{ $method->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From Date</label>
                    <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To Date</label>
                    <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt #</th>
                            <th>Student</th>
                            <th>Payment Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Allocated</th>
                            <th class="text-end">Unallocated</th>
                            <th>Payment Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td>
                                <strong>{{ $payment->receipt_number ?? $payment->transaction_code }}</strong>
                            </td>
                            <td>
                                {{ $payment->student->first_name ?? 'N/A' }} {{ $payment->student->last_name ?? '' }}
                                <br><small class="text-muted">{{ $payment->student->admission_number ?? 'N/A' }}</small>
                            </td>
                            <td>
                                {{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : 'N/A' }}
                            </td>
                            <td class="text-end">
                                <strong>Ksh {{ number_format($payment->amount, 2) }}</strong>
                            </td>
                            <td class="text-end">
                                <span class="text-success">Ksh {{ number_format($payment->allocated_amount ?? 0, 2) }}</span>
                            </td>
                            <td class="text-end">
                                @php
                                    $unallocated = $payment->unallocated_amount ?? ($payment->amount - ($payment->allocated_amount ?? 0));
                                @endphp
                                @if($unallocated > 0)
                                    <span class="text-warning">Ksh {{ number_format($unallocated, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                {{ $payment->paymentMethod->name ?? $payment->payment_method ?? 'N/A' }}
                            </td>
                            <td>
                                <small>{{ $payment->reference ?? '—' }}</small>
                            </td>
                            <td>
                                @php
                                    $status = $payment->status ?? 'completed';
                                    if ($payment->allocated_amount >= $payment->amount) {
                                        $status = 'allocated';
                                    } elseif ($payment->allocated_amount > 0) {
                                        $status = 'partial';
                                    }
                                @endphp
                                <span class="badge bg-{{ $status === 'allocated' ? 'success' : ($status === 'partial' ? 'warning' : 'info') }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('finance.payments.show', $payment) }}" class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('finance.payments.receipt', $payment) }}" class="btn btn-outline-secondary" target="_blank" title="Print Receipt">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <p class="text-muted mb-0">No payments found.</p>
                                <a href="{{ route('finance.payments.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus-circle"></i> Record First Payment
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    @if($payments->isNotEmpty())
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="3" class="text-end">Totals:</th>
                            <th class="text-end">Ksh {{ number_format($payments->sum('amount'), 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($payments->sum('allocated_amount'), 2) }}</th>
                            <th class="text-end">Ksh {{ number_format($payments->sum(function($p) { return $p->unallocated_amount ?? ($p->amount - ($p->allocated_amount ?? 0)); }), 2) }}</th>
                            <th colspan="4"></th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>
        @if($payments->hasPages())
        <div class="card-footer">
            {{ $payments->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
