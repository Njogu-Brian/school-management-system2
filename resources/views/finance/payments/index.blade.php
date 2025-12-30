@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Payments',
        'icon' => 'bi bi-cash-stack',
        'subtitle' => 'Track and manage all payment records',
        'actions' => '<a href="' . route('finance.payments.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Record Payment</a>'
    ])

    @include('finance.invoices.partials.alerts')
    
    @if(session('payment_id'))
        <script>
            window.addEventListener('load', function() {
                // Open receipt in popup window (not tab)
                const paymentId = {{ session('payment_id') }};
                const receiptUrl = '{{ route("finance.payments.receipt.view", ":id") }}'.replace(':id', paymentId);
                
                // Request permission for popup
                const popup = window.open(
                    receiptUrl,
                    'ReceiptWindow',
                    'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no'
                );
                
                if (!popup || popup.closed || typeof popup.closed == 'undefined') {
                    // Popup blocked - fallback to new tab
                    alert('Popup blocked. Please allow popups for this site to view receipt automatically.');
                    window.open(receiptUrl, '_blank');
                } else {
                    popup.focus();
                }
            });
        </script>
    @endif

    <!-- Filters -->
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0">
        <form method="GET" action="{{ route('finance.payments.index') }}" class="row g-3">
            <div class="col-md-6 col-lg-3">
                <label class="finance-form-label">Student</label>
                @include('partials.student_live_search', [
                    'hiddenInputId' => 'student_id',
                    'displayInputId' => 'studentFilterSearch',
                    'resultsId' => 'studentFilterResults',
                    'placeholder' => 'Type name or admission #',
                    'initialLabel' => request('student_id') ? (optional(\App\Models\Student::find(request('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(request('student_id')))->admission_number . ')') : ''
                ])
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">Status</label>
                <select name="status" class="finance-form-select">
                    <option value="">All Statuses</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">Payment Method</label>
                <select name="payment_method_id" class="finance-form-select">
                    <option value="">All Methods</option>
                    @foreach(\App\Models\PaymentMethod::where('is_active', true)->get() as $method)
                        <option value="{{ $method->id }}" {{ request('payment_method_id') == $method->id ? 'selected' : '' }}>
                            {{ $method->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">From Date</label>
                <input type="date" name="from_date" class="finance-form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-6 col-lg-2">
                <label class="finance-form-label">To Date</label>
                <input type="date" name="to_date" class="finance-form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-6 col-lg-1 d-flex align-items-end">
                <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Bulk send toolbar -->
    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
        <div class="text-muted small">Select receipts to send via SMS / Email / WhatsApp.</div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-finance btn-finance-secondary"
                onclick="openSendDocument('receipt', collectCheckedIds('.receipt-checkbox'))">
                <i class="bi bi-send"></i> Send Selected
            </button>
            <a href="{{ route('finance.payments.create') }}" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Record Payment</a>
        </div>
    </div>

    <!-- Payments Table -->
    <div class="finance-table-wrapper finance-animate shadow-sm rounded-4 border-0">
        <div class="table-responsive px-3 pb-3">
            <table class="finance-table align-middle">
                <thead>
                        <tr>
                            <th style="width:32px;"><input type="checkbox" id="receiptCheckAll"></th>
                            <th>Receipt #</th>
                            <th>Student</th>
                            <th>Payment Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Allocated</th>
                            <th class="text-end">Unallocated</th>
                            <th>Payment Method</th>
                            <th>Transaction Code</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payments as $payment)
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input receipt-checkbox" value="{{ $payment->id }}">
                            </td>
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
                                <small>{{ $payment->transaction_code ?? '—' }}</small>
                            </td>
                            <td>
                                @php
                                    // Use computed status from Payment model
                                    $status = $payment->status; // This uses the getStatusAttribute accessor
                                    $badgeClass = match($status) {
                                        'completed' => 'success',
                                        'partial' => 'warning',
                                        'unallocated' => 'info',
                                        'reversed' => 'danger',
                                        default => 'secondary'
                                    };
                                    $statusLabel = match($status) {
                                        'completed' => 'Completed',
                                        'partial' => 'Partial',
                                        'unallocated' => 'Unallocated',
                                        'reversed' => 'Reversed',
                                        default => ucfirst($status)
                                    };
                                @endphp
                                <span class="badge bg-{{ $badgeClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td>
                                <div class="finance-action-buttons">
                                    <a href="{{ route('finance.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('finance.payments.receipt', $payment) }}" 
                                       class="btn btn-sm btn-outline-secondary" 
                                       title="Print Receipt"
                                       onclick="window.open('{{ route('finance.payments.receipt', $payment) }}', 'ReceiptWindow', 'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no'); return false;">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-outline-success" title="Send"
                                        onclick="openSendDocument('receipt', [{{ $payment->id }}])">
                                        <i class="bi bi-send"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10">
                                <div class="finance-empty-state">
                                    <div class="finance-empty-state-icon">
                                        <i class="bi bi-cash-stack"></i>
                                    </div>
                                    <h4>No payments found</h4>
                                    <p class="text-muted mb-3">Record your first payment to get started</p>
                                    <a href="{{ route('finance.payments.create') }}" class="btn btn-finance btn-finance-primary">
                                        <i class="bi bi-plus-circle"></i> Record First Payment
                                    </a>
                                </div>
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
        @if($payments->hasPages())
        <div class="finance-card-body" style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            {{ $payments->links() }}
        </div>
        @endif
    </div>

@include('communication.partials.document-send-modal')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('receiptCheckAll');
    const boxes = document.querySelectorAll('.receipt-checkbox');
    function refresh() {
        if (checkAll) {
            const allChecked = boxes.length && Array.from(boxes).every(b => b.checked);
            checkAll.checked = allChecked;
        }
    }
    checkAll?.addEventListener('change', () => {
        boxes.forEach(b => b.checked = checkAll.checked);
    });
    boxes.forEach(b => b.addEventListener('change', refresh));
    refresh();
});
</script>
@endpush
@endsection
