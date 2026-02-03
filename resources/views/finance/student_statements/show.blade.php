@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Student Fee Statement',
        'icon' => 'bi bi-file-text',
        'subtitle' => $student->full_name . ' (' . $student->admission_number . ')',
        'actions' => (isset($comparisonPreviewId) && $comparisonPreviewId ? '<a href="' . route('finance.fees-comparison-import.show', $comparisonPreviewId) . '" class="btn btn-finance btn-finance-outline me-2"><i class="bi bi-arrow-left"></i> Back to comparison</a>' : '') . '<a href="' . route('finance.student-statements.export', ['student' => $student->id, 'year' => $year, 'term' => $term, 'format' => 'pdf']) . '" target="_blank" class="btn btn-finance btn-finance-primary"><i class="bi bi-file-pdf"></i> Export PDF</a><a href="' . route('finance.student-statements.export', ['student' => $student->id, 'year' => $year, 'term' => $term, 'format' => 'csv']) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a><a href="' . route('finance.student-statements.print', ['student' => $student->id, 'year' => $year, 'term' => $term]) . '" class="btn btn-finance btn-finance-outline" onclick="window.open(\'' . route('finance.student-statements.print', ['student' => $student->id, 'year' => $year, 'term' => $term]) . '\', \'StatementWindow\', \'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no\'); return false;"><i class="bi bi-printer"></i> Print</a><button type=\"button\" class=\"btn btn-finance btn-finance-secondary\" onclick=\"openSendDocument(\'statement\', [' . $student->id . '], {channel:\'sms\', message:\'Your statement is ready. Please find the link below.\'})\"><i class=\"bi bi-send\"></i> Send</button>'
    ])

    {{-- Filters --}}
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0">
        <form method="GET" action="{{ route('finance.student-statements.show', $student) }}" class="row g-3" id="statementFilterForm">
            @if(!empty($comparisonPreviewId))
                <input type="hidden" name="comparison_preview_id" value="{{ $comparisonPreviewId }}">
            @endif
            <div class="col-md-4">
                <label class="finance-form-label">Academic Year</label>
                <select name="year" id="yearSelect" class="finance-form-select">
                    @foreach($years as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">Term</label>
                <select name="term" id="termSelect" class="finance-form-select">
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
                    <strong>Stream:</strong> 
                    @if($student->stream_id && $student->stream)
                        {{ $student->stream->name }}
                    @else
                        N/A
                    @endif
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
                        @if($balance > 0)
                            <button type="button" class="btn btn-success btn-sm mt-2" onclick="openPayBalanceModal({{ $student->id }}, {{ $balance }})">
                                <i class="bi bi-wallet"></i> Pay Now
                            </button>
                        @endif
                    </div>
                    <i class="bi bi-wallet2" style="font-size: 2rem; color: {{ $balance > 0 ? '#dc3545' : '#10b981' }};"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Add Statement Entry --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle"></i> <span>Add Statement Entry</span>
        </div>
        <div class="finance-card-body p-4">
            <form method="POST" action="{{ route('finance.student-statements.entries.store', $student) }}" id="statementEntryForm">
                @csrf
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="term" value="{{ $term }}">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="finance-form-label">Entry Type</label>
                        <select name="entry_type" id="entryType" class="finance-form-select" required>
                            <option value="debit">Debit (Charge)</option>
                            <option value="credit">Credit (Credit Note)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="finance-form-label">Date</label>
                        <input type="date" name="entry_date" class="finance-form-input" required>
                    </div>
                    <div class="col-md-3">
                        <label class="finance-form-label">Reference</label>
                        <input type="text" name="reference" class="finance-form-input" required placeholder="Reference number">
                    </div>
                    <div class="col-md-3">
                        <label class="finance-form-label">Votehead</label>
                        <select name="votehead_id" class="finance-form-select" required>
                            @foreach($voteheads as $votehead)
                                <option value="{{ $votehead->id }}">{{ $votehead->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="finance-form-label">Description</label>
                        <input type="text" name="description" class="finance-form-input" required placeholder="Narration / notes">
                    </div>
                    <div class="col-md-3">
                        <label class="finance-form-label">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" class="finance-form-input" required>
                    </div>
                    <div class="col-md-3" id="debitInvoiceGroup">
                        <label class="finance-form-label">Invoice (Debit)</label>
                        <select name="invoice_id" class="finance-form-select">
                            <option value="">Auto-create invoice</option>
                            @foreach($invoices as $invoice)
                                <option value="{{ $invoice->id }}">{{ $invoice->invoice_number }} ({{ $invoice->term->name ?? 'Term' }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6" id="creditItemGroup" style="display: none;">
                        <label class="finance-form-label">Invoice Item (Credit)</label>
                        <select name="invoice_item_id" id="creditInvoiceItem" class="finance-form-select">
                            <option value="">Select invoice item</option>
                            @foreach($invoices as $invoice)
                                @foreach($invoice->items as $item)
                                    <option value="{{ $item->id }}" data-invoice-id="{{ $invoice->id }}">
                                        {{ $invoice->invoice_number }} - {{ $item->votehead->name ?? 'Votehead' }} (Ksh {{ number_format($item->amount, 2) }})
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        <small class="text-muted">Select the invoice item to credit.</small>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-check-circle"></i> Add Entry
                        </button>
                    </div>
                </div>
            </form>
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
                            // Calculate starting balance
                            $runningBalance = 0;
                            $finalBalance = 0;
                            
                            // For legacy years, start from the first term's starting balance
                            // For 2026+, start from balance brought forward
                            if ($year < 2026) {
                                // Get starting balance from first term of the year
                                $firstTerm = \App\Models\LegacyStatementTerm::where('student_id', $student->id)
                                    ->where('academic_year', $year)
                                    ->orderBy('term_number')
                                    ->first();
                                $runningBalance = $firstTerm->starting_balance ?? 0;
                            } else {
                                // For 2026+, start from balance brought forward only if not already in invoices
                                $runningBalance = ($hasBalanceBroughtForwardInInvoices ?? false) ? 0 : ($balanceBroughtForward ?? 0);
                            }
                        @endphp
                        
                        @forelse($detailedTransactions ?? collect() as $transaction)
                            @php
                                $runningBalance += $transaction['debit'] - $transaction['credit'];
                                $finalBalance = $runningBalance;
                                $transactionId = $transaction['model_id'] ?? null;
                                $transactionType = $transaction['type'] ?? 'Unknown';
                                $isReversal = $transaction['is_reversal'] ?? false;
                                $paymentChannel = null;
                                
                                // Get payment channel for payment transactions
                                if($transactionType == 'Payment' && isset($transaction['payment_id'])) {
                                    $payment = \App\Models\Payment::find($transaction['payment_id']);
                                    $paymentChannel = $payment ? $payment->payment_channel : null;
                                }
                            @endphp
                            <tr style="{{ $isReversal ? 'background-color: #fff3cd;' : '' }}">
                                <td>{{ \Carbon\Carbon::parse($transaction['date'])->format('d M Y') }}</td>
                                <td>
                                    @if($transactionType == 'Invoice Item')
                                        <span class="badge bg-primary">Invoice</span>
                                    @elseif($transactionType == 'Payment')
                                        <span class="badge bg-success">
                                            Payment
                                            @if($paymentChannel)
                                                <br><small style="font-size: 0.7em;">
                                                    @if($paymentChannel == 'stk_push')
                                                        <i class="bi bi-phone"></i> M-PESA STK
                                                    @elseif($paymentChannel == 'payment_link')
                                                        <i class="bi bi-link-45deg"></i> Payment Link
                                                    @elseif($paymentChannel == 'paybill_manual')
                                                        <i class="bi bi-phone"></i> M-PESA Paybill
                                                    @else
                                                        {{ ucfirst(str_replace('_', ' ', $paymentChannel)) }}
                                                    @endif
                                                </small>
                                            @endif
                                        </span>
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
                                <td>
                                    <code>{{ $transaction['reference'] }}</code>
                                    @if($transactionType == 'Payment' && isset($transaction['payment_id']))
                                        @php
                                            $payment = \App\Models\Payment::find($transaction['payment_id']);
                                        @endphp
                                        @if($payment && $payment->mpesa_receipt_number)
                                            <br><small class="text-success"><i class="bi bi-check-circle"></i> {{ $payment->mpesa_receipt_number }}</small>
                                        @endif
                                    @endif
                                </td>
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
                                            @if(isset($transaction['invoice_item_id']))
                                            <button type="button" class="btn btn-sm btn-outline-secondary editable-amount" 
                                                    data-item-id="{{ $transaction['invoice_item_id'] }}"
                                                    data-current-amount="{{ $transaction['debit'] }}"
                                                    data-invoice-id="{{ $transaction['invoice_id'] ?? '' }}"
                                                    title="Edit amount (creates credit or debit note)">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            @endif
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
                                        @elseif($transactionType == 'Legacy' && $transactionId)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary edit-legacy-line"
                                                    data-line-id="{{ $transactionId }}"
                                                    data-date="{{ \Carbon\Carbon::parse($transaction['date'])->toDateString() }}"
                                                    data-description="{{ $transaction['description'] }}"
                                                    data-reference="{{ $transaction['reference'] }}"
                                                    data-votehead="{{ $transaction['votehead'] }}"
                                                    data-debit="{{ $transaction['debit'] }}"
                                                    data-credit="{{ $transaction['credit'] }}"
                                                    title="Edit legacy entry">
                                                <i class="bi bi-pencil"></i>
                                            </button>
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
    // Update terms when academic year changes
    const yearSelect = document.getElementById('yearSelect');
    const termSelect = document.getElementById('termSelect');
    const form = document.getElementById('statementFilterForm');
    
    if (yearSelect && termSelect) {
        yearSelect.addEventListener('change', function() {
            const selectedYear = this.value;
            
            // Fetch terms for the selected year
            fetch(`{{ route('finance.student-statements.show', $student) }}?year=${selectedYear}&get_terms=1`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.terms) {
                    termSelect.innerHTML = '<option value="">All Terms</option>';
                    data.terms.forEach(term => {
                        const option = document.createElement('option');
                        option.value = term.id;
                        option.textContent = term.name;
                        termSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error fetching terms:', error);
                // Fallback: submit form to reload with new year
                form.submit();
            });
        });
    }
    
    // Inline editing for votehead line items (same as invoice: creates credit/debit notes)
    const invoiceItemUpdateBase = "{{ url('/finance/invoices') }}";
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
                            <h5 class="modal-title">Edit Votehead Line Item Amount</h5>
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
                                    <small class="text-muted">Decreasing amount will create a credit note. Increasing will create a debit note.</small>
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
                
                fetch(`${invoiceItemUpdateBase}/${invoiceId}/items/${itemId}/update`, {
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

    // Add entry form toggles
    const entryType = document.getElementById('entryType');
    const creditGroup = document.getElementById('creditItemGroup');
    const debitGroup = document.getElementById('debitInvoiceGroup');
    const creditItemSelect = document.getElementById('creditInvoiceItem');
    if (entryType) {
        const toggleEntryGroups = () => {
            if (entryType.value === 'credit') {
                if (creditGroup) creditGroup.style.display = 'block';
                if (debitGroup) debitGroup.style.display = 'none';
                if (creditItemSelect) creditItemSelect.required = true;
            } else {
                if (creditGroup) creditGroup.style.display = 'none';
                if (debitGroup) debitGroup.style.display = 'block';
                if (creditItemSelect) creditItemSelect.required = false;
            }
        };
        entryType.addEventListener('change', toggleEntryGroups);
        toggleEntryGroups();
    }

    // Legacy line editing
    const legacyUpdateBase = "{{ url('/finance/student-statements/' . $student->id . '/legacy-lines') }}";
    document.querySelectorAll('.edit-legacy-line').forEach(button => {
        button.addEventListener('click', function() {
            const lineId = this.dataset.lineId;
            const currentDebit = parseFloat(this.dataset.debit || 0);
            const currentCredit = parseFloat(this.dataset.credit || 0);

            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.id = 'editLegacyLineModal';
            modal.innerHTML = `
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Legacy Entry</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form id="editLegacyLineForm">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="txn_date" class="form-control" value="${this.dataset.date || ''}">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Description</label>
                                    <input type="text" name="narration_raw" class="form-control" value="${this.dataset.description || ''}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Reference</label>
                                    <input type="text" name="reference_number" class="form-control" value="${this.dataset.reference || ''}" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Votehead</label>
                                    <input type="text" name="votehead" class="form-control" value="${this.dataset.votehead || ''}" required>
                                </div>
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label">Debit</label>
                                        <input type="number" step="0.01" min="0" name="amount_dr" class="form-control" value="${currentDebit || ''}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Credit</label>
                                        <input type="number" step="0.01" min="0" name="amount_cr" class="form-control" value="${currentCredit || ''}">
                                    </div>
                                </div>
                                <input type="hidden" name="confidence" value="high">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-finance btn-finance-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();

            document.getElementById('editLegacyLineForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('_method', 'PUT');
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

                fetch(`${legacyUpdateBase}/${lineId}`, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        bsModal.hide();
                        modal.remove();
                        window.location.reload();
                    } else {
                        alert(data.error || 'Failed to update legacy entry');
                    }
                })
                .catch(() => {
                    alert('Failed to update legacy entry');
                });
            });

            modal.addEventListener('hidden.bs.modal', function() {
                modal.remove();
            });
        });
    });

    // Pay Balance Modal
    window.openPayBalanceModal = function(studentId, balance) {
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.innerHTML = `
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-wallet"></i> Pay Outstanding Balance</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('finance.mpesa.prompt-payment') }}" method="POST">
                        @csrf
                        <input type="hidden" name="student_id" value="${studentId}">
                        <div class="modal-body">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> You can pay the full balance or a partial amount.
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Outstanding Balance</label>
                                <input type="text" class="form-control" value="KES ${new Intl.NumberFormat().format(balance)}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Amount to Pay <span class="text-danger">*</span></label>
                                <input type="number" name="amount" class="form-control" 
                                       value="${balance}" min="1" max="${balance}" step="0.01" required>
                                <small class="text-muted">Enter amount (Max: KES ${new Intl.NumberFormat().format(balance)})</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" name="phone_number" class="form-control" 
                                       placeholder="e.g., 0712345678" required>
                                <small class="text-muted">Enter M-PESA phone number to receive payment prompt</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notes (Optional)</label>
                                <textarea name="notes" class="form-control" rows="2" 
                                          placeholder="Add any notes about this payment..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-phone"></i> Send Payment Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
        modal.addEventListener('hidden.bs.modal', function() {
            modal.remove();
        });
    };
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

