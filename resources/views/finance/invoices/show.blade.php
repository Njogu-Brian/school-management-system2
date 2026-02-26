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
        'subtitle' => $invoice->student->full_name ? 'For ' . $invoice->student->full_name : 'Invoice details',
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
                        <strong>Name:</strong> {{ $student->full_name ?? 'Unknown' }}
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
        @php
            $uniformItem = \App\Services\UniformFeeService::getUniformItem($invoice);
        @endphp
        <div class="finance-card-body border-bottom">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <span class="text-muted"><i class="bi bi-tshirt"></i> {{ $uniformItem ? 'Uniform line on this invoice. Change amount or remove in the table below.' : 'Add a uniform line for this student (optional). Amount will update fee balance and appear on invoice, payments and statement.' }}</span>
                @if(!$uniformItem)
                <form action="{{ route('finance.invoices.uniform.store', $invoice) }}" method="POST" class="d-flex align-items-center gap-2">
                    @csrf
                    <label class="form-label mb-0">Amount (Ksh)</label>
                    <input type="number" name="amount" class="form-control form-control-sm" style="width: 120px;" step="0.01" min="0" required placeholder="0.00">
                    <button type="submit" class="btn btn-sm btn-finance btn-finance-primary"><i class="bi bi-plus-lg"></i> Add Uniform</button>
                </form>
                @else
                <a href="#item-{{ $uniformItem->id }}" class="btn btn-sm btn-finance btn-finance-outline"><i class="bi bi-pencil"></i> Adjust / Remove in table below</a>
                @endif
            </div>
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
                        <tr id="item-{{ $item->id }}">
                            <td>{{ $lineNumber }}</td>
                            <td>
                                {{ $item->votehead->name ?? 'Unknown' }}
                                @if($item->is_optional)
                                    <span class="badge bg-info ms-1">Optional</span>
                                @endif
                            </td>
                            <td class="text-end" id="amountCell{{ $item->id }}">
                                <strong class="amount-display">Ksh {{ number_format($item->amount, 2) }}</strong>
                                <div class="amount-edit-form" style="display: none;">
                                    <input type="number" 
                                           class="form-control form-control-sm" 
                                           id="newAmount{{ $item->id }}" 
                                           value="{{ $item->amount }}" 
                                           step="0.01" 
                                           min="0"
                                           style="width: 120px; display: inline-block;">
                                </div>
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
                                <div class="edit-actions-view{{ $item->id }}">
                                    @if(($item->source ?? '') === \App\Services\UniformFeeService::SOURCE)
                                    <button type="button" 
                                            class="btn btn-sm btn-finance btn-finance-outline"
                                            onclick="startInlineEdit({{ $item->id }}, {{ $item->amount }}, '{{ addslashes($item->votehead->name ?? 'Uniform') }}', true)">
                                        <i class="bi bi-pencil"></i> Adjust
                                    </button>
                                    <form action="{{ route('finance.invoices.uniform.remove', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Remove uniform line from this invoice?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Remove</button>
                                    </form>
                                    @else
                                    <button type="button" 
                                            class="btn btn-sm btn-finance btn-finance-outline"
                                            onclick="startInlineEdit({{ $item->id }}, {{ $item->amount }}, '{{ addslashes($item->votehead->name ?? 'Item') }}', false)">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    @endif
                                </div>
                                <div class="edit-actions-edit{{ $item->id }}" style="display: none;">
                                    <button type="button" 
                                            class="btn btn-sm btn-success"
                                            onclick="saveInlineEdit({{ $item->id }}, {{ $invoice->id }}, {{ ($item->source ?? '') === \App\Services\UniformFeeService::SOURCE ? 'true' : 'false' }})">
                                        <i class="bi bi-check"></i> Save
                                    </button>
                                    <button type="button" 
                                            class="btn btn-sm btn-secondary"
                                            onclick="cancelInlineEdit({{ $item->id }})">
                                        <i class="bi bi-x"></i> Cancel
                                    </button>
                                </div>
                            </td>
                        </tr>
                        
                        <!-- Inline Edit Reason Row -->
                        <tr id="reasonRow{{ $item->id }}" style="display: none;" class="table-warning" data-is-uniform="{{ ($item->source ?? '') === \App\Services\UniformFeeService::SOURCE ? '1' : '0' }}">
                            <td></td>
                            <td colspan="8">
                                <div class="mb-2">
                                    @if(($item->source ?? '') === \App\Services\UniformFeeService::SOURCE)
                                    <label class="form-label small mb-1"><strong>Optional note:</strong></label>
                                    <textarea class="form-control form-control-sm" 
                                              id="reason{{ $item->id }}" 
                                              rows="2" 
                                              placeholder="Optional note (amount is updated directly; no credit/debit note)"></textarea>
                                    @else
                                    <label class="form-label small mb-1"><strong>Reason for amount change:</strong> <span class="text-danger">*</span></label>
                                    <textarea class="form-control form-control-sm" 
                                              id="reason{{ $item->id }}" 
                                              rows="2" 
                                              placeholder="Enter reason for changing the amount..."
                                              required></textarea>
                                    <small class="text-muted">Decreasing amount will create a credit note. Increasing will create a debit note.</small>
                                    @endif
                                </div>
                            </td>
                            <td></td>
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
                        
                        <!-- No modals needed - using inline editing -->
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

    <!-- Payment History (payments that have allocations to this invoice's items) -->
    @php
        $paymentsForHistory = $paymentsForHistory ?? $invoice->payments;
    @endphp
    @if($paymentsForHistory->isNotEmpty())
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
                            <th class="text-end">Allocated to this invoice</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($paymentsForHistory as $payment)
                        @php
                            $allocatedToInvoice = $payment->allocations
                                ->filter(fn($a) => $a->invoiceItem && $a->invoiceItem->invoice_id == $invoice->id)
                                ->sum('amount');
                        @endphp
                        <tr>
                            <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : 'N/A' }}</td>
                            <td>
                                <a href="{{ route('finance.payments.show', $payment) }}">
                                    {{ $payment->receipt_number ?? $payment->transaction_code }}
                                </a>
                            </td>
                            <td class="text-end">Ksh {{ number_format($payment->amount, 2) }}</td>
                            <td class="text-end">
                                <span class="{{ $allocatedToInvoice < $payment->amount ? 'text-warning' : '' }}">
                                    Ksh {{ number_format($allocatedToInvoice, 2) }}
                                </span>
                                @if($allocatedToInvoice < $payment->amount && $allocatedToInvoice > 0)
                                    <small class="text-muted d-block">(partial)</small>
                                @endif
                            </td>
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
// Inline editing for invoice items - NO MODAL, NO FLICKERING
function startInlineEdit(itemId, currentAmount, voteheadName, isUniform) {
    console.log('[Invoice Edit] Starting inline edit for item:', itemId);
    
    // Hide display, show edit form
    document.querySelector('#amountCell' + itemId + ' .amount-display').style.display = 'none';
    document.querySelector('#amountCell' + itemId + ' .amount-edit-form').style.display = 'inline-block';
    
    // Show reason row
    document.getElementById('reasonRow' + itemId).style.display = 'table-row';
    var reasonInput = document.getElementById('reason' + itemId);
    if (reasonInput) {
        reasonInput.removeAttribute('required');
        if (isUniform) reasonInput.placeholder = 'Optional note (no credit/debit note)';
    }
    
    // Toggle action buttons
    document.querySelector('.edit-actions-view' + itemId).style.display = 'none';
    document.querySelector('.edit-actions-edit' + itemId).style.display = 'inline-block';
    
    // Focus on amount input
    document.getElementById('newAmount' + itemId).focus();
    document.getElementById('newAmount' + itemId).select();
}

function cancelInlineEdit(itemId) {
    console.log('[Invoice Edit] Cancelling inline edit for item:', itemId);
    
    // Hide edit form, show display
    document.querySelector('#amountCell' + itemId + ' .amount-display').style.display = 'inline';
    document.querySelector('#amountCell' + itemId + ' .amount-edit-form').style.display = 'none';
    
    // Hide reason row
    document.getElementById('reasonRow' + itemId).style.display = 'none';
    
    // Toggle action buttons
    document.querySelector('.edit-actions-view' + itemId).style.display = 'inline-block';
    document.querySelector('.edit-actions-edit' + itemId).style.display = 'none';
    
    // Clear reason field
    document.getElementById('reason' + itemId).value = '';
}

function saveInlineEdit(itemId, invoiceId, isUniform) {
    console.log('[Invoice Edit] Saving inline edit for item:', itemId);
    
    if (typeof isUniform === 'undefined') {
        var reasonRow = document.getElementById('reasonRow' + itemId);
        isUniform = reasonRow && reasonRow.getAttribute('data-is-uniform') === '1';
    }
    
    // Get values
    var newAmount = document.getElementById('newAmount' + itemId).value;
    var reason = document.getElementById('reason' + itemId).value;
    
    // Validate
    if (!newAmount || parseFloat(newAmount) < 0) {
        alert('Please enter a valid amount');
        document.getElementById('newAmount' + itemId).focus();
        return;
    }
    
    if (!isUniform && (!reason || reason.trim() === '')) {
        alert('Please enter a reason for the change');
        document.getElementById('reason' + itemId).focus();
        return;
    }
    
    // Get CSRF token
    var csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    
    // Disable save button
    var saveBtn = event.target;
    var originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
    
    // Prepare data
    var formData = new FormData();
    formData.append('new_amount', newAmount);
    formData.append('reason', isUniform ? (reason || 'Uniform amount adjusted') : reason);
    formData.append('_token', csrfToken);
    
    // Build URL
    var url = '/finance/invoices/' + invoiceId + '/items/' + itemId + '/update';
    
    console.log('[Invoice Edit] Sending update to:', url);
    
    // Send AJAX request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Accept', 'application/json');
    
    xhr.onload = function() {
        console.log('[Invoice Edit] Response received:', xhr.status);
        
        if (xhr.status >= 200 && xhr.status < 300) {
            try {
                var data = JSON.parse(xhr.responseText);
                if (data.success || data.message) {
                    // Show success message
                    alert(data.message || 'Invoice item updated successfully');
                    // Reload to show credit/debit note
                    window.location.reload();
                } else {
                    alert(data.error || 'Update failed');
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = originalText;
                }
            } catch (e) {
                console.log('[Invoice Edit] JSON parse error, reloading');
                alert('Update completed. Reloading...');
                window.location.reload();
            }
        } else {
            alert('Update failed. Please try again.');
            saveBtn.disabled = false;
            saveBtn.innerHTML = originalText;
        }
    };
    
    xhr.onerror = function() {
        alert('Network error. Please try again.');
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    };
    
    xhr.send(formData);
}

// Allow Enter key to save in amount field
document.addEventListener('keypress', function(e) {
    if (e.target.matches('[id^="newAmount"]')) {
        if (e.key === 'Enter') {
            e.preventDefault();
            // Focus on reason field
            var itemId = e.target.id.replace('newAmount', '');
            document.getElementById('reason' + itemId).focus();
        }
    } else if (e.target.matches('[id^="reason"]')) {
        // Ctrl+Enter to save
        if (e.key === 'Enter' && e.ctrlKey) {
            e.preventDefault();
            var itemId = e.target.id.replace('reason', '');
            var invoiceId = {{ $invoice->id }};
            saveInlineEdit(itemId, invoiceId);
        }
    }
});

console.log('[Invoice Edit] Inline editing initialized');
</script>
@endpush
@endsection
