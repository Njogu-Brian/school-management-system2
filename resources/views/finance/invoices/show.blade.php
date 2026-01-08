@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @php
        $payNowBtn = '';
        if ($invoice->balance > 0) {
            $payNowBtn = '<a href="' . route('finance.mpesa.prompt-payment.form', ['student_id' => $invoice->student_id, 'invoice_id' => $invoice->id]) . '" class="btn btn-finance btn-success"><i class="bi bi-phone"></i> Prompt Parent to Pay (M-PESA)</a>';
            $payNowBtn .= '<a href="' . route('finance.mpesa.links.create', ['student_id' => $invoice->student_id, 'invoice_id' => $invoice->id]) . '" class="btn btn-finance btn-primary"><i class="bi bi-link-45deg"></i> Generate Payment Link</a>';
        }
    @endphp
    
    @include('finance.partials.header', [
        'title' => 'Invoice: ' . $invoice->invoice_number,
        'icon' => 'bi bi-file-text',
        'subtitle' => $invoice->student->first_name ? 'For ' . $invoice->student->first_name . ' ' . ($invoice->student->last_name ?? '') : 'Invoice details',
        'actions' => $payNowBtn . '<a href="' . route('finance.invoices.print_single', $invoice) . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-printer"></i> Print PDF</a><button type="button" class="btn btn-finance btn-finance-secondary" onclick="openSendDocument(\'invoice\', [' . $invoice->id . '], {channel:\'sms\', message:\'Please find your invoice link below.\'})"><i class="bi bi-send"></i> Send Now</button><a href="' . route('finance.invoices.history', $invoice) . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-clock-history"></i> History</a><a href="' . route('finance.invoices.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    @php
        $student = $invoice->student;
    @endphp

    <div class="finance-card finance-animate mb-4 mt-4 shadow-sm rounded-4 border-0">
        <div class="finance-card-body p-4">
    @includeIf('finance.invoices.partials.alerts')

    <!-- Invoice Header -->
    <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
        <div class="finance-card-body p-4">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3">Student Information</h5>
                    <p class="mb-2">
                        <strong>Name:</strong> {{ $student->first_name ?? 'Unknown' }} {{ $student->last_name ?? '' }}
                    </p>
                    <p class="mb-2">
                        <strong>Admission Number:</strong> {{ $student->admission_number ?? '—' }}
                    </p>
                    <p class="mb-2">
                        <strong>Class:</strong> {{ $student?->classroom?->name ?? '—' }} 
                        @if($student && $student->stream)
                            / {{ $student->stream->name }}
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
                        <strong>Term:</strong> {{ $invoice->term->name ?? ($invoice->term ? 'Term ' . $invoice->term : '—') }}
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
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="finance-card finance-animate h-100 shadow-sm rounded-4 border-0">
                <div class="finance-card-body text-center p-4">
                    <h6 class="finance-muted mb-2">Total Amount</h6>
                    <h4 class="text-primary mb-0">Ksh {{ number_format($invoice->total, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-card finance-animate h-100 shadow-sm rounded-4 border-0">
                <div class="finance-card-body text-center p-4">
                    <h6 class="finance-muted mb-2">Paid Amount</h6>
                    <h4 class="text-success mb-0">Ksh {{ number_format($invoice->paid_amount ?? 0, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-card finance-animate h-100 shadow-sm rounded-4 border-0">
                <div class="finance-card-body text-center p-4">
                    <h6 class="finance-muted mb-2">Balance</h6>
                    <h4 class="text-warning mb-0">Ksh {{ number_format($invoice->balance ?? $invoice->total, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="finance-card finance-animate h-100 shadow-sm rounded-4 border-0">
                <div class="finance-card-body text-center p-4">
                    <h6 class="finance-muted mb-2">Status</h6>
                    <span class="finance-badge badge-{{ $invoice->status === 'paid' ? 'paid' : ($invoice->status === 'partial' ? 'partial' : 'unpaid') }} fs-6">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Discount Summary -->
    @php
        // Ensure we're using fresh data
        $invoice->load('items');
        $itemDiscounts = $invoice->items->sum('discount_amount') ?? 0;
        $invoiceDiscount = $invoice->discount_amount ?? 0;
        $totalDiscounts = $itemDiscounts + $invoiceDiscount;
    @endphp
    <div class="finance-card finance-animate mb-4 {{ $totalDiscounts > 0 ? 'border-success' : 'border-secondary' }} shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-{{ $totalDiscounts > 0 ? 'success' : 'secondary' }}">
                    <i class="bi bi-percent"></i>
                </span>
                <h5 class="mb-0">Discounts Applied</h5>
            </div>
            <span class="finance-badge badge-{{ $totalDiscounts > 0 ? 'partial' : 'unpaid' }}">
                {{ $totalDiscounts > 0 ? 'Active' : 'None' }}
            </span>
        </div>
        <div class="finance-card-body p-4">
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
            @else
            <div class="mb-3">
                <p class="text-muted mb-0"><em>No discounts applied to this invoice.</em></p>
            </div>
            <hr>
            @endif
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1"><strong>Item-Level Discounts:</strong></p>
                    <p class="{{ $itemDiscounts > 0 ? 'text-success' : 'text-muted' }} fs-5 mb-0">
                        {{ $itemDiscounts > 0 ? '-' : '' }}Ksh {{ number_format($itemDiscounts, 2) }}
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-1"><strong>Invoice-Level Discount:</strong></p>
                    <p class="{{ $invoiceDiscount > 0 ? 'text-success' : 'text-muted' }} fs-5 mb-0">
                        {{ $invoiceDiscount > 0 ? '-' : '' }}Ksh {{ number_format($invoiceDiscount, 2) }}
                    </p>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-md-12">
                    <p class="mb-0"><strong>Total Discounts:</strong> 
                        <span class="{{ $totalDiscounts > 0 ? 'text-success' : 'text-muted' }} fs-4">
                            {{ $totalDiscounts > 0 ? '-' : '' }}Ksh {{ number_format($totalDiscounts, 2) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Invoice Items with Inline Editing -->
    <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Invoice Items, Discounts & Adjustments</h5>
            @if($invoice->balance > 0)
            <a href="{{ route('finance.payments.create', ['student_id' => $invoice->student_id, 'invoice_id' => $invoice->id]) }}" class="btn btn-sm btn-finance btn-finance-primary">
                <i class="bi bi-cash-stack"></i> Record Payment
            </a>
            @endif
        </div>
        <div class="finance-card-body p-0">
            <div class="table-responsive px-3 pb-3">
                <table class="finance-table table-hover align-middle mb-0">
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
                            $itemsPerPage = 10;
                            $itemsPage = max(1, (int) request()->get('items_page', 1));
                            $lineNumber = ($itemsPage - 1) * $itemsPerPage;
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

                            $itemsTotal = $allLineItems->count();
                            $itemsPages = max(1, (int) ceil($itemsTotal / $itemsPerPage));
                            $itemsPage = min($itemsPage, $itemsPages);
                            $pagedLineItems = $allLineItems->slice(($itemsPage - 1) * $itemsPerPage, $itemsPerPage);
                            $query = request()->except('items_page');
                        @endphp
                        
                        @forelse($pagedLineItems as $lineItem)
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
                                        class="btn btn-sm btn-finance btn-finance-outline" 
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
                        <div class="modal fade" id="editItemModal{{ $item->id }}" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('finance.invoices.items.update', [$invoice->id, $item->id]) }}" class="edit-item-form" id="editItemForm{{ $item->id }}" onsubmit="return false;">
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
                                            <button type="submit" class="btn btn-finance btn-finance-primary">Update Item</button>
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
                            // Ensure invoice is recalculated for accurate balance
                            $invoice->recalculate();
                            
                            $totalAmount = $invoice->items->sum('amount');
                            $totalDiscount = $invoice->items->sum('discount_amount') + ($invoice->discount_amount ?? 0);
                            $totalPaid = $invoice->paid_amount ?? 0;
                            
                            // Use invoice balance (already calculated correctly in recalculate)
                            $totalBalance = $invoice->balance ?? 0;
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
                            <th class="text-end"><strong>Ksh {{ number_format($invoice->total, 2) }}</strong></th>
                            <th class="text-end">Ksh {{ number_format($totalPaid, 2) }}</th>
                            <th class="text-end"><strong>Ksh {{ number_format($totalBalance, 2) }}</strong></th>
                            <th colspan="3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if($itemsPages > 1)
            <div class="d-flex justify-content-between align-items-center px-4 pb-4 pt-2">
                <small class="text-muted">
                    Showing {{ ($itemsPage - 1) * $itemsPerPage + 1 }} -
                    {{ min($itemsPage * $itemsPerPage, $itemsTotal) }} of {{ $itemsTotal }}
                </small>
                <div class="btn-group">
                    <a class="btn btn-sm btn-finance btn-finance-outline {{ $itemsPage === 1 ? 'disabled' : '' }}"
                       href="{{ $itemsPage === 1 ? '#' : route('finance.invoices.show', $invoice) . '?' . http_build_query(array_merge($query, ['items_page' => $itemsPage - 1])) }}">
                        Previous
                    </a>
                    <a class="btn btn-sm btn-finance btn-finance-primary {{ $itemsPage === $itemsPages ? 'disabled' : '' }}"
                       href="{{ $itemsPage === $itemsPages ? '#' : route('finance.invoices.show', $invoice) . '?' . http_build_query(array_merge($query, ['items_page' => $itemsPage + 1])) }}">
                        Next
                    </a>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Credit/Debit Notes are now shown as line items in the invoice items table above --}}

    <!-- Payment History -->
    @if($invoice->payments->isNotEmpty())
    <div class="finance-card finance-animate mb-5 shadow-sm rounded-4 border-0">
        <div class="finance-card-header">
            <h5 class="mb-0">Payment History</h5>
        </div>
        <div class="finance-card-body p-0">
            <div class="table-responsive px-3 pb-3">
                <table class="finance-table table-hover align-middle mb-0">
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
                            <td>{{ $payment->transaction_code ?? '—' }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('finance.payments.show', $payment) }}" class="btn btn-sm btn-finance btn-finance-outline" title="View Payment">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if(!$payment->reversed)
                                    <form action="{{ route('finance.payments.reverse', $payment) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reverse this payment? This action cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" title="Reverse Payment">
                                            <i class="bi bi-arrow-counterclockwise"></i>
                                        </button>
                                    </form>
                                    @else
                                    <span class="badge bg-secondary">Reversed</span>
                                    @endif
                                </div>
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

@push('scripts')
<script>
// Handle AJAX form submission for invoice item editing
(function() {
    'use strict';
    
    console.log('[Invoice Edit] Script loaded');
    
    function handleFormSubmit(e) {
        const form = e.target;
        
        console.log('[Invoice Edit] Submit event detected on:', form);
        
        // Only handle forms with class 'edit-item-form'
        if (!form || !form.classList || !form.classList.contains('edit-item-form')) {
            console.log('[Invoice Edit] Not an edit-item-form, ignoring');
            return true; // Let other forms submit normally
        }
        
        console.log('[Invoice Edit] This is an edit-item-form, intercepting');
        
        // Prevent ALL default behaviors
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        // Prevent form from actually submitting
        if (form.onsubmit) {
            form.onsubmit = null;
        }
        
        console.log('[Invoice Edit] Form submit intercepted:', form.action);
        console.log('[Invoice Edit] Form element:', form);
        
        // Get CSRF token from form - try multiple ways BEFORE creating FormData
        let csrfToken = null;
        
        // Method 1: From form input (most reliable)
        const csrfInput = form.querySelector('input[name="_token"]');
        if (csrfInput) {
            csrfToken = csrfInput.value;
            console.log('[Invoice Edit] CSRF token found in form input');
        }
        
        // Method 2: From meta tag in head
        if (!csrfToken) {
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                csrfToken = metaToken.getAttribute('content');
                console.log('[Invoice Edit] CSRF token found in meta tag');
            }
        }
        
        // Method 3: Try to find any hidden input with _token
        if (!csrfToken) {
            const allInputs = form.querySelectorAll('input[type="hidden"]');
            for (let input of allInputs) {
                if (input.name === '_token' || input.name.includes('token')) {
                    csrfToken = input.value;
                    console.log('[Invoice Edit] CSRF token found in hidden input:', input.name);
                    break;
                }
            }
        }
        
        console.log('[Invoice Edit] CSRF token found:', !!csrfToken);
        console.log('[Invoice Edit] CSRF token length:', csrfToken ? csrfToken.length : 0);
        
        if (!csrfToken) {
            console.error('[Invoice Edit] CSRF token not found.');
            console.error('[Invoice Edit] Form inputs:', Array.from(form.querySelectorAll('input')).map(i => i.name + '=' + i.type));
            console.error('[Invoice Edit] Form HTML (first 1000 chars):', form.innerHTML.substring(0, 1000));
            alert('CSRF token not found. Please refresh the page and try again.');
            return false;
        }
        
        const formData = new FormData(form);
        const submitButton = form.querySelector('button[type="submit"]');
        const originalText = submitButton ? submitButton.innerHTML : '';
        
        // Validate form fields manually for better error display
        const newAmountInput = form.querySelector('input[name="new_amount"]');
        const reasonInput = form.querySelector('textarea[name="reason"]');
        
        if (!newAmountInput || !newAmountInput.value) {
            alert('Please enter a new amount');
            if (newAmountInput) newAmountInput.focus();
            return false;
        }
        
        if (!reasonInput || !reasonInput.value.trim()) {
            alert('Please enter a reason for the change');
            if (reasonInput) reasonInput.focus();
            return false;
        }
        
        console.log('[Invoice Edit] Form validation passed');
        console.log('[Invoice Edit] New amount:', newAmountInput.value);
        console.log('[Invoice Edit] Reason:', reasonInput.value);
        
        // Disable submit button
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Updating...';
        }
        
        console.log('[Invoice Edit] Sending AJAX request to:', form.action);
        console.log('[Invoice Edit] FormData contents:');
        for (let pair of formData.entries()) {
            console.log('[Invoice Edit]  ', pair[0], '=', pair[1]);
        }
        
        // Use XMLHttpRequest for better compatibility
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        
        console.log('[Invoice Edit] Request headers set');
        
        xhr.onload = function() {
            console.log('[Invoice Edit] Response status:', xhr.status);
            console.log('[Invoice Edit] Response text:', xhr.responseText.substring(0, 500));
            
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const data = JSON.parse(xhr.responseText);
                    console.log('[Invoice Edit] Parsed data:', data);
                    
                    if (data.success !== false && (data.success || data.message)) {
                        // Show success message
                        const message = data.message || 'Invoice item updated successfully';
                        alert(message);
                        
                        // Close modal
                        const modalElement = form.closest('.modal');
                        if (modalElement) {
                            const modal = bootstrap.Modal.getInstance(modalElement);
                            if (modal) {
                                modal.hide();
                            }
                        }
                        
                        // Reload page to show updated amounts
                        setTimeout(() => {
                            window.location.reload();
                        }, 300);
                    } else {
                        const errorMsg = data.error || data.message || 'Failed to update amount';
                        alert(errorMsg);
                        if (submitButton) {
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalText;
                        }
                    }
                } catch (parseError) {
                    console.error('[Invoice Edit] JSON parse error:', parseError);
                    console.error('[Invoice Edit] Response was:', xhr.responseText);
                    // If response is HTML (validation errors), reload page to show errors
                    alert('Update completed. Reloading page...');
                    window.location.reload();
                }
            } else {
                // Error response
                console.error('[Invoice Edit] Error response:', xhr.status, xhr.responseText);
                try {
                    const data = JSON.parse(xhr.responseText);
                    alert(data.error || data.message || 'Update failed. Please try again.');
                } catch (e) {
                    alert('Update failed (Status: ' + xhr.status + '). Please try again.');
                }
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            }
        };
        
        xhr.onerror = function() {
            console.error('[Invoice Edit] XHR network error');
            alert('Network error. Please check your connection and try again.');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        };
        
        xhr.ontimeout = function() {
            console.error('[Invoice Edit] XHR timeout');
            alert('Request timed out. Please try again.');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        };
        
        xhr.timeout = 30000; // 30 second timeout
        
        try {
            xhr.send(formData);
        } catch (sendError) {
            console.error('[Invoice Edit] Error sending request:', sendError);
            alert('Error sending request: ' + sendError.message);
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = originalText;
            }
        }
        
        return false;
    }
    
    // Track which forms we've already attached handlers to
    const attachedForms = new WeakSet();
    
    // Initialize when DOM is ready
    function init() {
        console.log('[Invoice Edit] Initializing...');
        
        // Attach global submit handler ONCE
        document.addEventListener('submit', handleFormSubmit, true); // Use capture phase
        console.log('[Invoice Edit] Global submit handler attached');
        
        // Process existing forms
        attachToForms();
        
        // Listen for modal shown events to handle dynamically shown forms
        document.addEventListener('shown.bs.modal', function(e) {
            console.log('[Invoice Edit] Modal shown');
            const modal = e.target;
            
            // Wait a bit for modal to fully render
            setTimeout(() => {
                const forms = modal.querySelectorAll('.edit-item-form');
                console.log('[Invoice Edit] Forms in modal:', forms.length);
                forms.forEach(form => {
                    if (!attachedForms.has(form)) {
                        console.log('[Invoice Edit] New form found in modal:', form.id || form.action);
                        attachToForm(form);
                        attachedForms.add(form);
                    }
                });
            }, 50);
        });
        
        // Prevent modal from closing on backdrop click while form is being submitted
        document.addEventListener('hide.bs.modal', function(e) {
            const modal = e.target;
            const forms = modal.querySelectorAll('.edit-item-form button[type="submit"]:disabled');
            if (forms.length > 0) {
                console.log('[Invoice Edit] Preventing modal close - form is being submitted');
                e.preventDefault();
            }
        });
    }
    
    function attachToForms() {
        const forms = document.querySelectorAll('.edit-item-form');
        console.log('[Invoice Edit] Found', forms.length, 'forms on page');
        
        forms.forEach(form => {
            if (!attachedForms.has(form)) {
                attachToForm(form);
                attachedForms.add(form);
            }
        });
    }
    
    function attachToForm(form) {
        console.log('[Invoice Edit] Attaching handlers to form:', form.id || form.action);
        
        // Remove any existing onsubmit attribute
        if (form.hasAttribute('onsubmit')) {
            form.removeAttribute('onsubmit');
        }
        
        // Attach submit handler
        form.addEventListener('submit', handleFormSubmit, true);
        
        // Also attach to submit button for debugging
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                console.log('[Invoice Edit] Submit button clicked on form:', form.id);
            });
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endpush
@endsection
