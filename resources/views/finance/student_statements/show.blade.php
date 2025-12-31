@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Student Fee Statement',
        'icon' => 'bi bi-file-text',
        'subtitle' => $student->full_name . ' (' . $student->admission_number . ')',
        'actions' => '<a href="' . route('finance.student-statements.export', ['student' => $student->id, 'year' => $year, 'term' => $term, 'format' => 'pdf']) . '" target="_blank" class="btn btn-finance btn-finance-primary"><i class="bi bi-file-pdf"></i> Export PDF</a><a href="' . route('finance.student-statements.export', ['student' => $student->id, 'year' => $year, 'term' => $term, 'format' => 'csv']) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a><a href="' . route('finance.student-statements.print', ['student' => $student->id, 'year' => $year, 'term' => $term]) . '" class="btn btn-finance btn-finance-outline" onclick="window.open(\'' . route('finance.student-statements.print', ['student' => $student->id, 'year' => $year, 'term' => $term]) . '\', \'StatementWindow\', \'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no\'); return false;"><i class="bi bi-printer"></i> Print</a><button type=\"button\" class=\"btn btn-finance btn-finance-secondary\" onclick=\"openSendDocument(\'statement\', [' . $student->id . '], {channel:\'sms\', message:\'Your statement is ready. Please find the link below.\'})\"><i class=\"bi bi-send\"></i> Send</button>'
    ])

    {{-- Filters --}}
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0">
        <form method="GET" action="{{ route('finance.student-statements.show', $student) }}" class="row g-3">
            <div class="col-md-4">
                <label class="finance-form-label">Academic Year</label>
                <select name="year" class="finance-form-select" onchange="this.form.submit()">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">Term</label>
                <select name="term" class="finance-form-select" onchange="this.form.submit()">
                    <option value="">All Terms</option>
                    @foreach($terms as $t)
                        <option value="{{ $t->id }}" {{ $term == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">&nbsp;</label>
                <div>
                    <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                        <i class="bi bi-filter"></i> Apply Filters
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- Student Info --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i> <span>Student Information</span>
        </div>
        <div class="finance-card-body p-4">
            <div class="row">
                <div class="col-md-2">
                    <strong>Name:</strong> {{ $student->full_name }}
                </div>
                <div class="col-md-2">
                    <strong>Admission Number:</strong> {{ $student->admission_number }}
                </div>
                <div class="col-md-2">
                    <strong>Class:</strong> {{ optional($student->classroom)->name ?? 'N/A' }}
                </div>
                <div class="col-md-2">
                    <strong>Stream:</strong> {{ optional($student->stream)->name ?? 'N/A' }}
                </div>
                <div class="col-md-4">
                    <strong>Year:</strong> {{ $year }} @if($term) | <strong>Term:</strong> {{ $terms->find($term)->name ?? "Term {$term}" }} @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="finance-stat-card border-primary finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Charges</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">Ksh {{ number_format($totalCharges, 2) }}</h4>
                    </div>
                    <i class="bi bi-arrow-up-circle" style="font-size: 2rem; color: var(--finance-primary);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card border-success finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Payments</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">Ksh {{ number_format($totalPayments, 2) }}</h4>
                    </div>
                    <i class="bi bi-cash-stack" style="font-size: 2rem; color: var(--finance-success);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card border-info finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Discounts</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">Ksh {{ number_format($totalDiscounts, 2) }}</h4>
                    </div>
                    <i class="bi bi-percent" style="font-size: 2rem; color: var(--finance-info);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-stat-card {{ $balance > 0 ? 'border-danger' : 'border-success' }} finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Balance</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700; color: {{ $balance > 0 ? '#dc3545' : '#10b981' }};">Ksh {{ number_format($balance, 2) }}</h4>
                    </div>
                    <i class="bi bi-wallet2" style="font-size: 2rem; color: {{ $balance > 0 ? '#dc3545' : '#10b981' }};"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Transactions --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-list-ul"></i> <span>Transaction History</span>
        </div>
        <div class="finance-card-body p-0">
            <div class="finance-table-wrapper">
                <table class="finance-table align-middle">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Votehead</th>
                            <th>Description</th>
                            <th>Reference</th>
                            <th class="text-end">Debit (Ksh)</th>
                            <th class="text-end">Credit (Ksh)</th>
                            <th class="text-end">Balance (Ksh)</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $runningBalance = 0;
                            $finalBalance = 0;
                        @endphp
                        
                        @forelse($detailedTransactions ?? collect() as $transaction)
                            @php
                                $runningBalance += $transaction['debit'] - $transaction['credit'];
                                $finalBalance = $runningBalance;
                                $transactionId = $transaction['model_id'] ?? null;
                                $transactionType = $transaction['type'] ?? 'Unknown';
                                $isReversal = $transaction['is_reversal'] ?? false;
                            @endphp
                            <tr style="{{ $isReversal ? 'background-color: #fff3cd;' : '' }}">
                                <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('d M Y') }}</td>
                                <td>
                                    @if($transactionType == 'Invoice Item')
                                        <span class="badge bg-primary">Invoice</span>
                                    @elseif($transactionType == 'Payment')
                                        <span class="badge bg-success">Payment</span>
                                    @elseif($transactionType == 'Payment Reversal')
                                        <span class="badge bg-danger"><i class="bi bi-arrow-counterclockwise"></i> Payment Reversal</span>
                                    @elseif($transactionType == 'Discount')
                                        <span class="badge bg-info">Discount</span>
                                    @elseif($transactionType == 'Credit Note')
                                        <span class="badge bg-warning">Credit Note</span>
                                    @elseif($transactionType == 'Debit Note')
                                        <span class="badge bg-danger">Debit Note</span>
                                    @elseif($transactionType == 'Posting Reversal')
                                        <span class="badge bg-secondary"><i class="bi bi-arrow-counterclockwise"></i> Posting Reversal</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $transactionType }}</span>
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $transaction['votehead'] ?? 'N/A' }}</strong>
                                </td>
                                <td>
                                    @if($transactionType == 'Invoice Item' && isset($transaction['invoice_item_id']))
                                        <span class="editable-amount" 
                                              data-item-id="{{ $transaction['invoice_item_id'] }}"
                                              data-current-amount="{{ $transaction['debit'] }}"
                                              data-invoice-id="{{ $transaction['invoice_id'] ?? '' }}"
                                              style="cursor: pointer; text-decoration: underline; color: var(--finance-primary);"
                                              title="Click to edit amount">
                                            {{ $transaction['description'] }}
                                        </span>
                                    @else
                                        {{ $transaction['description'] }}
                                    @endif
                                </td>
                                <td><code>{{ $transaction['reference'] }}</code></td>
                                <td class="text-end">{{ $transaction['debit'] > 0 ? 'Ksh ' . number_format($transaction['debit'], 2) : '—' }}</td>
                                <td class="text-end">{{ $transaction['credit'] > 0 ? 'Ksh ' . number_format($transaction['credit'], 2) : '—' }}</td>
                                <td class="text-end">
                                    <strong style="color: {{ $runningBalance > 0 ? '#dc3545' : ($runningBalance < 0 ? '#28a745' : '#333') }}">
                                        Ksh {{ number_format($runningBalance, 2) }}
                                    </strong>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        @if($transactionType == 'Invoice Item' && isset($transaction['invoice_id']))
                                            <a href="{{ route('finance.invoices.show', $transaction['invoice_id']) }}" class="btn btn-sm btn-outline-primary" title="View Invoice">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                        @elseif($transactionType == 'Payment' && isset($transaction['payment_id']))
                                            @php
                                                $payment = \App\Models\Payment::find($transaction['payment_id']);
                                            @endphp
                                            <a href="{{ route('finance.payments.show', $transaction['payment_id']) }}" class="btn btn-sm btn-outline-success" title="View Payment">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            @if($payment && !$payment->reversed && $payment->unallocated_amount > 0)
                                                <button type="button" class="btn btn-sm btn-outline-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#transferPaymentModal{{ $payment->id }}"
                                                        title="Transfer/Share Payment">
                                                    <i class="bi bi-arrow-left-right"></i>
                                                </button>
                                            @endif
                                            @if($payment && !$payment->reversed)
                                                <form action="{{ route('finance.payments.reverse', $payment->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this payment?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Reverse Payment">
                                                        <i class="bi bi-arrow-counterclockwise"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @elseif($transactionType == 'Credit Note' && $transactionId)
                                            <form action="{{ route('finance.credit-notes.reverse', $transactionId) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this credit note?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Reverse Credit Note">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @elseif($transactionType == 'Debit Note' && $transactionId)
                                            <form action="{{ route('finance.debit-notes.reverse', $transactionId) }}" method="POST" class="d-inline" onsubmit="return confirm('Reverse this debit note?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Reverse Debit Note">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">
                                    <div class="finance-empty-state">
                                        <i class="bi bi-inbox finance-empty-state-icon"></i>
                                        <h4>No transactions found</h4>
                                        <p>No financial transactions recorded for this period.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="5" class="text-end">Totals:</th>
                            <th class="text-end">Ksh {{ number_format(($detailedTransactions ?? collect())->sum('debit'), 2) }}</th>
                            <th class="text-end">Ksh {{ number_format(($detailedTransactions ?? collect())->sum('credit'), 2) }}</th>
                            <th class="text-end"><strong>Ksh {{ number_format($finalBalance ?? $balance, 2) }}</strong></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inline editing for invoice items
    document.querySelectorAll('.editable-amount').forEach(element => {
        element.addEventListener('click', function() {
            const itemId = this.dataset.itemId;
            const currentAmount = parseFloat(this.dataset.currentAmount);
            const invoiceId = this.dataset.invoiceId;
            
            // Create modal for editing
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'editItemModal';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Invoice Item Amount</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="editItemForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Current Amount</label>
                                    <input type="text" class="form-control" value="Ksh ${currentAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Ksh</span>
                                        <input type="number" step="0.01" min="0" name="new_amount" class="form-control" value="${currentAmount}" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                                    <input type="text" name="reason" class="form-control" required placeholder="Enter reason for adjustment">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Notes (Optional)</label>
                                    <textarea name="notes" class="form-control" rows="2" placeholder="Additional notes"></textarea>
                                </div>
                                <input type="hidden" name="invoice_item_id" value="${itemId}">
                                <input type="hidden" name="invoice_id" value="${invoiceId}">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-finance btn-finance-primary">Update Amount</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
            
            // Handle form submission
            document.getElementById('editItemForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const invoiceId = formData.get('invoice_id');
                
                fetch(`/finance/invoices/${invoiceId}/items/${itemId}/update`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success || data.message) {
                        bsModal.hide();
                        modal.remove();
                        // Reload page to show updated amounts
                        window.location.reload();
                    } else {
                        alert(data.error || 'Failed to update amount');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating the amount');
                });
            });
            
            // Remove modal on close
            modal.addEventListener('hidden.bs.modal', function() {
                modal.remove();
            });
        });
    });
});
</script>
@endpush

@push('styles')
<style>
@media print {
    .finance-header, .finance-filter-card, .btn, .sidebar, .content { margin-left: 0 !important; }
    .finance-card { page-break-inside: avoid; }
    .finance-stat-card { page-break-inside: avoid; }
}
</style>
@endpush
@include('communication.partials.document-send-modal')
@endsection

