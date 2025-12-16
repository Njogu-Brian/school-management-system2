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
            <h5 class="mb-0">Invoice Items</h5>
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
                        @forelse($invoice->items as $item)
                        @php
                            $discount = $item->discount_amount ?? 0;
                            $afterDiscount = $item->amount - $discount;
                            $paid = $item->getAllocatedAmount() ?? 0;
                            $balance = $afterDiscount - $paid;
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
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
                                @if($discount > 0)
                                    <span class="text-success">-Ksh {{ number_format($discount, 2) }}</span>
                                @else
                                    <span class="text-muted">Ksh 0.00</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <strong class="text-primary">Ksh {{ number_format($afterDiscount, 2) }}</strong>
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
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                No invoice items found.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        @php
                            $totalAmount = $invoice->items->sum('amount');
                            $totalDiscount = $invoice->items->sum('discount_amount') + ($invoice->discount_amount ?? 0);
                            $totalAfterDiscount = $totalAmount - $totalDiscount;
                            $totalPaid = $invoice->items->sum(function($i) { return $i->getAllocatedAmount() ?? 0; });
                            $totalBalance = $totalAfterDiscount - $totalPaid;
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
                            <th class="text-end"><strong>Ksh {{ number_format($totalAfterDiscount, 2) }}</strong></th>
                            <th class="text-end">Ksh {{ number_format($totalPaid, 2) }}</th>
                            <th class="text-end"><strong>Ksh {{ number_format($totalBalance, 2) }}</strong></th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Credit/Debit Notes Summary -->
    @if($invoice->creditNotes->isNotEmpty() || $invoice->debitNotes->isNotEmpty())
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Adjustments</h5>
        </div>
        <div class="card-body">
            <div class="row">
                @if($invoice->creditNotes->isNotEmpty())
                <div class="col-md-6">
                    <h6 class="text-success">Credit Notes</h6>
                    <ul class="list-unstyled">
                        @foreach($invoice->creditNotes as $note)
                        <li class="mb-2">
                            <strong>{{ $note->credit_note_number }}:</strong> 
                            Ksh {{ number_format($note->amount, 2) }} - {{ $note->reason }}
                        </li>
                        @endforeach
                    </ul>
                    <p><strong>Total Credits:</strong> Ksh {{ number_format($invoice->creditNotes->sum('amount'), 2) }}</p>
                </div>
                @endif
                @if($invoice->debitNotes->isNotEmpty())
                <div class="col-md-6">
                    <h6 class="text-danger">Debit Notes</h6>
                    <ul class="list-unstyled">
                        @foreach($invoice->debitNotes as $note)
                        <li class="mb-2">
                            <strong>{{ $note->debit_note_number }}:</strong> 
                            Ksh {{ number_format($note->amount, 2) }} - {{ $note->reason }}
                        </li>
                        @endforeach
                    </ul>
                    <p><strong>Total Debits:</strong> Ksh {{ number_format($invoice->debitNotes->sum('amount'), 2) }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

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
