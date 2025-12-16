@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-file-text"></i> Invoice: {{ $invoice->invoice_number }}
                </h3>
                <div>
                    <a href="{{ route('finance.invoices.print_single', $invoice) }}" 
                       target="_blank" 
                       class="btn btn-outline-secondary">
                       <i class="bi bi-printer"></i> Print PDF
                    </a>
                    <a href="{{ route('finance.invoices.history', $invoice) }}" class="btn btn-outline-info">
                        <i class="bi bi-clock-history"></i> History
                    </a>
                    <a href="{{ route('finance.invoices.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    @includeIf('finance.invoices.partials.alerts')

    <!-- Invoice Header -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">Student Information</h5>
                    <p class="mb-2">
                        <strong>Name:</strong> {{ $invoice->student->first_name ?? 'Unknown' }} {{ $invoice->student->last_name ?? '' }}
                    </p>
                    <p class="mb-2">
                        <strong>Admission Number:</strong> {{ $invoice->student->admission_number ?? '—' }}
                    </p>
                    <p class="mb-2">
                        <strong>Class:</strong> {{ $invoice->student->classroom->name ?? '—' }} 
                        @if($invoice->student->stream)
                            / {{ $invoice->student->stream->name }}
                        @endif
                    </p>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-3">Invoice Details</h5>
                    <p class="mb-2">
                        <strong>Invoice Number:</strong> {{ $invoice->invoice_number }}
                    </p>
                    <p class="mb-2">
                        <strong>Academic Year:</strong> {{ $invoice->academicYear->name ?? $invoice->year ?? '—' }}
                    </p>
                    <p class="mb-2">
                        <strong>Term:</strong> {{ $invoice->term->name ?? 'Term ' . $invoice->term ?? '—' }}
                    </p>
                    <p class="mb-2">
                        <strong>Issue Date:</strong> {{ $invoice->issued_date ? \Carbon\Carbon::parse($invoice->issued_date)->format('d M Y') : '—' }}
                    </p>
                    @if($invoice->due_date)
                    <p class="mb-2">
                        <strong>Due Date:</strong> 
                        <span class="{{ $invoice->isOverdue() ? 'text-danger' : '' }}">
                            {{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}
                        </span>
                    </p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Total Amount</h6>
                    <h4 class="text-primary mb-0">Ksh {{ number_format($invoice->total, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Paid Amount</h6>
                    <h4 class="text-success mb-0">Ksh {{ number_format($invoice->paid_amount ?? 0, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Balance</h6>
                    <h4 class="text-warning mb-0">Ksh {{ number_format($invoice->balance ?? $invoice->total, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h6 class="text-muted mb-2">Status</h6>
                    <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partial' ? 'warning' : 'danger') }} fs-6">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Discount Summary -->
    @php
        $itemDiscounts = $invoice->items->sum('discount_amount');
        $invoiceDiscount = $invoice->discount_amount ?? 0;
        $totalDiscounts = $itemDiscounts + $invoiceDiscount;
    @endphp
    @if($totalDiscounts > 0)
    <div class="card shadow-sm mb-4 border-success">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="bi bi-percent"></i> Discounts Applied</h5>
        </div>
        <div class="card-body">
            @if(isset($appliedDiscounts) && $appliedDiscounts->isNotEmpty())
            <div class="mb-3">
                <h6 class="mb-2">Applied Discounts:</h6>
                <ul class="list-unstyled mb-0">
                    @foreach($appliedDiscounts as $discount)
                    @php
                        $discountName = $discount->discountTemplate->name ?? $discount->reason ?? 'Discount';
                        $discountType = $discount->type === 'percentage' ? $discount->value . '%' : 'Ksh ' . number_format($discount->value, 2);
                        $scopeLabel = ucfirst($discount->scope);
                        if ($discount->scope === 'votehead' && $discount->votehead) {
                            $scopeLabel .= ' (' . $discount->votehead->name . ')';
                        }
                    @endphp
                    <li class="mb-2">
                        <strong>{{ $discountName }}</strong>
                        <span class="text-muted">({{ $scopeLabel }})</span>
                        <br>
                        <small class="text-muted">{{ $discountType }} - {{ $discount->description ?? $discount->reason }}</small>
                    </li>
                    @endforeach
                </ul>
            </div>
            <hr>
            @endif
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Item-Level Discounts:</strong></p>
                    <p class="text-success fs-5 mb-0">-Ksh {{ number_format($itemDiscounts, 2) }}</p>
                </div>
                @if($invoiceDiscount > 0)
                <div class="col-md-6">
                    <p class="mb-1"><strong>Invoice-Level Discount:</strong></p>
                    <p class="text-success fs-5 mb-0">-Ksh {{ number_format($invoiceDiscount, 2) }}</p>
                </div>
                @endif
            </div>
            <hr>
            <div class="row">
                <div class="col-md-12">
                    <p class="mb-0"><strong>Total Discounts:</strong> <span class="text-success fs-4">-Ksh {{ number_format($totalDiscounts, 2) }}</span></p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Invoice Items with Inline Editing -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Invoice Items, Discounts & Adjustments</h5>
            @if($invoice->balance > 0)
            <a href="{{ route('finance.payments.create', ['student_id' => $invoice->student_id, 'invoice_id' => $invoice->id]) }}" class="btn btn-sm btn-primary">
                <i class="bi bi-cash-stack"></i> Record Payment
            </a>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Votehead</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">After Discount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th>Status</th>
                            <th>Effective Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $lineNumber = 0;
                            $allLineItems = collect();
                            
                            // Add invoice items
                            foreach ($invoice->items as $item) {
                                $allLineItems->push([
                                    'type' => 'item',
                                    'data' => $item,
                                    'sort_order' => 1
                                ]);
                            }
                            
                            // Add item-level discounts as line items
                            foreach ($invoice->items as $item) {
                                if (($item->discount_amount ?? 0) > 0) {
                                    $allLineItems->push([
                                        'type' => 'item_discount',
                                        'data' => $item,
                                        'sort_order' => 2
                                    ]);
                                }
                            }
                            
                            // Add invoice-level discount
                            if (($invoice->discount_amount ?? 0) > 0) {
                                $allLineItems->push([
                                    'type' => 'invoice_discount',
                                    'data' => $invoice,
                                    'sort_order' => 3
                                ]);
                            }
                            
                            // Add credit notes
                            foreach ($invoice->creditNotes as $creditNote) {
                                $allLineItems->push([
                                    'type' => 'credit_note',
                                    'data' => $creditNote,
                                    'sort_order' => 4
                                ]);
                            }
                            
                            // Add debit notes
                            foreach ($invoice->debitNotes as $debitNote) {
                                $allLineItems->push([
                                    'type' => 'debit_note',
                                    'data' => $debitNote,
                                    'sort_order' => 5
                                ]);
                            }
                            
                            // Sort by sort_order and then by date/created_at
                            $now = \Carbon\Carbon::now();
                            $allLineItems = $allLineItems->sortBy(function($item) use ($now) {
                                // Primary sort: sort_order
                                $sortKey = $item['sort_order'] . '_';
                                
                                // Secondary sort: date
                                if ($item['type'] === 'item') {
                                    $date = $item['data']->created_at ?? $now;
                                } elseif (in_array($item['type'], ['credit_note', 'debit_note'])) {
                                    $date = $item['data']->issued_at ?? $now;
                                } else {
                                    $date = $now;
                                }
                                
                                // Combine sort_order and timestamp for sorting
                                return $sortKey . $date->timestamp;
                            });
                        @endphp
                        
                        @forelse($allLineItems as $lineItem)
                        @php
                            $lineNumber++;
                            $type = $lineItem['type'];
                            $data = $lineItem['data'];
                        @endphp
                        
                        @if($type === 'item')
                        @php
                            $item = $data;
                            $discount = $item->discount_amount ?? 0;
                            $afterDiscount = $item->amount - $discount;
                            $paid = $item->getAllocatedAmount() ?? 0;
                            $balance = $afterDiscount - $paid;
                        @endphp
                        <tr>
                            <td>{{ $lineNumber }}</td>
                            <td>
                                {{ $item->votehead->name ?? 'Unknown' }}
                                @if($item->is_optional)
                                    <span class="badge bg-info ms-1">Optional</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong>Ksh {{ number_format($item->amount, 2) }}</strong>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">Ksh 0.00</span>
                            </td>
                            <td class="text-end">
                                <strong class="text-primary">Ksh {{ number_format($item->amount, 2) }}</strong>
                            </td>
                            <td class="text-end">
                                <span class="text-success">Ksh {{ number_format($paid, 2) }}</span>
                            </td>
                            <td class="text-end">
                                <span class="text-{{ $balance > 0 ? 'danger' : 'success' }}">
                                    Ksh {{ number_format($balance, 2) }}
                                </span>
                            </td>
                            <td>
                                @if($balance <= 0)
                                    <span class="badge bg-success">Paid</span>
                                @elseif($paid > 0)
                                    <span class="badge bg-warning">Partial</span>
                                @else
                                    <span class="badge bg-danger">Unpaid</span>
                                @endif
                            </td>
                            <td>
                                @if($item->effective_date)
                                    {{ \Carbon\Carbon::parse($item->effective_date)->format('d M Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editItemModal{{ $item->id }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </td>
                        </tr>

                        @elseif($type === 'item_discount')
                        @php
                            $item = $data;
                            $discount = $item->discount_amount ?? 0;
                        @endphp
                        <tr class="table-success">
                            <td>{{ $lineNumber }}</td>
                            <td>
                                <i class="bi bi-percent text-success"></i> 
                                <strong>Discount - {{ $item->votehead->name ?? 'Unknown' }}</strong>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td class="text-end">
                                <span class="text-success"><strong>-Ksh {{ number_format($discount, 2) }}</strong></span>
                            </td>
                            <td class="text-end">
                                <span class="text-success"><strong>-Ksh {{ number_format($discount, 2) }}</strong></span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td>
                                <span class="badge bg-success">Discount</span>
                            </td>
                            <td>
                                <span class="text-muted">—</span>
                            </td>
                            <td>
                                <span class="text-muted">—</span>
                            </td>
                        </tr>
                        
                        @elseif($type === 'invoice_discount')
                        @php
                            $invoiceDiscount = $invoice->discount_amount ?? 0;
                        @endphp
                        <tr class="table-success">
                            <td>{{ $lineNumber }}</td>
                            <td>
                                <i class="bi bi-percent text-success"></i> 
                                <strong>Invoice Discount</strong>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td class="text-end">
                                <span class="text-success"><strong>-Ksh {{ number_format($invoiceDiscount, 2) }}</strong></span>
                            </td>
                            <td class="text-end">
                                <span class="text-success"><strong>-Ksh {{ number_format($invoiceDiscount, 2) }}</strong></span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td>
                                <span class="badge bg-success">Discount</span>
                            </td>
                            <td>
                                <span class="text-muted">—</span>
                            </td>
                            <td>
                                <span class="text-muted">—</span>
                            </td>
                        </tr>
                        
                        @elseif($type === 'credit_note')
                        @php
                            $creditNote = $data;
                        @endphp
                        <tr class="table-success">
                            <td>{{ $lineNumber }}</td>
                            <td>
                                <i class="bi bi-arrow-down-circle text-success"></i> 
                                <strong>Credit Note: {{ $creditNote->credit_note_number }}</strong>
                                <br>
                                <small class="text-muted">{{ $creditNote->reason }}</small>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td class="text-end">
                                <span class="text-success"><strong>-Ksh {{ number_format($creditNote->amount, 2) }}</strong></span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td>
                                <span class="badge bg-success">Credit</span>
                            </td>
                            <td>
                                @if($creditNote->issued_at)
                                    {{ \Carbon\Carbon::parse($creditNote->issued_at)->format('d M Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted">—</span>
                            </td>
                        </tr>
                        
                        @elseif($type === 'debit_note')
                        @php
                            $debitNote = $data;
                        @endphp
                        <tr class="table-danger">
                            <td>{{ $lineNumber }}</td>
                            <td>
                                <i class="bi bi-arrow-up-circle text-danger"></i> 
                                <strong>Debit Note: {{ $debitNote->debit_note_number }}</strong>
                                <br>
                                <small class="text-muted">{{ $debitNote->reason }}</small>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td class="text-end">
                                <span class="text-danger"><strong>+Ksh {{ number_format($debitNote->amount, 2) }}</strong></span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td class="text-end">
                                <span class="text-muted">—</span>
                            </td>
                            <td>
                                <span class="badge bg-danger">Debit</span>
                            </td>
                            <td>
                                @if($debitNote->issued_at)
                                    {{ \Carbon\Carbon::parse($debitNote->issued_at)->format('d M Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                <span class="text-muted">—</span>
                            </td>
                        </tr>
                        @endif
                        @endforeach
                        
                        <!-- Edit Item Modals (only for invoice items) -->
                        @foreach($invoice->items as $item)
                        <!-- Edit Item Modal -->
                        <div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('finance.invoices.items.update', [$invoice->id, $item->id]) }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit Invoice Item</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="mb-3">
                                                <label class="form-label">Votehead:</label>
                                                <input type="text" class="form-control" value="{{ $item->votehead->name ?? 'N/A' }}" disabled>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Current Amount:</label>
                                                <input type="text" class="form-control" value="Ksh {{ number_format($item->amount, 2) }}" disabled>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">New Amount <span class="text-danger">*</span></label>
                                                <input type="number" 
                                                       name="new_amount" 
                                                       step="0.01" 
                                                       min="0" 
                                                       class="form-control @error('new_amount') is-invalid @enderror" 
                                                       value="{{ $item->amount }}" 
                                                       required>
                                                @error('new_amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                                <small class="text-muted">
                                                    Decreasing amount will create a credit note. Increasing will create a debit note.
                                                </small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Reason <span class="text-danger">*</span></label>
                                                <textarea name="reason" 
                                                          class="form-control @error('reason') is-invalid @enderror" 
                                                          rows="3" 
                                                          placeholder="Reason for amount change" 
                                                          required>{{ old('reason') }}</textarea>
                                                @error('reason')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Update Item</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        @endforeach
                        
                        @if($allLineItems->isEmpty())
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                No invoice items found.
                            </td>
                        </tr>
                        @endif
                    </tbody>
                    <tfoot class="table-light">
                        @php
                            $totalAmount = $invoice->items->sum('amount');
                            $totalDiscount = $invoice->items->sum('discount_amount') + ($invoice->discount_amount ?? 0);
                            $totalCreditNotes = $invoice->creditNotes->sum('amount');
                            $totalDebitNotes = $invoice->debitNotes->sum('amount');
                            $totalAfterDiscount = $totalAmount - $totalDiscount;
                            $totalAfterAdjustments = $totalAfterDiscount - $totalCreditNotes + $totalDebitNotes;
                            $totalPaid = $invoice->items->sum(function($i) { return $i->getAllocatedAmount() ?? 0; });
                            $totalBalance = $totalAfterAdjustments - $totalPaid;
                        @endphp
                        <tr>
                            <th colspan="2" class="text-end">Subtotals:</th>
                            <th class="text-end">Ksh {{ number_format($totalAmount, 2) }}</th>
                            <th class="text-end">
                                @if($totalDiscount > 0)
                                    <span class="text-success">-Ksh {{ number_format($totalDiscount, 2) }}</span>
                                @else
                                    <span class="text-muted">Ksh 0.00</span>
                                @endif
                            </th>
                            <th class="text-end"><strong>Ksh {{ number_format($totalAfterAdjustments, 2) }}</strong></th>
                            <th class="text-end">Ksh {{ number_format($totalPaid, 2) }}</th>
                            <th class="text-end"><strong>Ksh {{ number_format($totalBalance, 2) }}</strong></th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Credit/Debit Notes are now shown as line items in the invoice items table above --}}

    <!-- Payment History -->
    @if($invoice->payments->isNotEmpty())
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Payment History</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Receipt Number</th>
                            <th class="text-end">Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->payments as $payment)
                        <tr>
                            <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('finance.payments.show', $payment) }}">
                                    {{ $payment->receipt_number ?? $payment->transaction_code }}
                                </a>
                            </td>
                            <td class="text-end">Ksh {{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->paymentMethod->name ?? $payment->payment_method ?? 'N/A' }}</td>
                            <td>{{ $payment->reference ?? '—' }}</td>
                            <td>
                                <a href="{{ route('finance.payments.show', $payment) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
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
@endsection
