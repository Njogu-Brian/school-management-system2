@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Payment #' . ($payment->receipt_number ?? $payment->transaction_code),
        'icon' => 'bi bi-cash-stack',
        'subtitle' => $payment->student?->full_name ? 'For ' . $payment->student->full_name : 'Payment details',
        'actions' => '<a href="' . route('finance.payments.receipt.view', $payment) . '" class="btn btn-finance btn-finance-primary" onclick="window.open(\'' . route('finance.payments.receipt.view', $payment) . '\', \'ReceiptWindow\', \'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no\'); return false;"><i class="bi bi-printer"></i> View/Print Receipt</a><button type="button" class="btn btn-finance btn-finance-secondary" onclick="openSendDocument(\'receipt\', [' . $payment->id . '], {message:\'Your receipt is ready. Please find the link below.\'})"><i class="bi bi-send"></i> Send Now</button><a href="' . route('finance.payments.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    @include('finance.invoices.partials.alerts')
    @php
        // Use live allocation totals to avoid stale cached fields
        $actualAllocatedAmount = $payment->reversed ? 0 : (float) ($payment->allocations->sum('amount') ?? 0);
        $actualUnallocatedAmount = $payment->reversed
            ? (float) $payment->amount
            : max(0, (float) $payment->amount - $actualAllocatedAmount);
    @endphp

    <div class="row">
        <div class="col-md-8">
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payment Information</h5>
                    <span class="finance-badge badge-paid">{{ $payment->receipt_number ?? $payment->transaction_code }}</span>
                </div>
                <div class="finance-card-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Student:</dt>
                                <dd class="col-sm-7">
                                    {{ $payment->student->full_name ?? 'N/A' }}
                                    <br><small class="text-muted">{{ $payment->student->admission_number ?? 'N/A' }}</small>
                                </dd>

                                <dt class="col-sm-5">Payment Amount:</dt>
                                <dd class="col-sm-7"><strong>Ksh {{ number_format($payment->amount, 2) }}</strong></dd>

                                <dt class="col-sm-5">Payment Date:</dt>
                                <dd class="col-sm-7">{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') : 'N/A' }}</dd>

                                <dt class="col-sm-5">Payment Method:</dt>
                                <dd class="col-sm-7">
                                    {{ $payment->paymentMethod->name ?? $payment->payment_method ?? 'N/A' }}
                                </dd>

                                <dt class="col-sm-5">Transaction Code:</dt>
                                <dd class="col-sm-7">
                                    <code>{{ $payment->transaction_code ?? 'N/A' }}</code>
                                </dd>

                                <dt class="col-sm-5">Receipt Number:</dt>
                                <dd class="col-sm-7">
                                    <strong>{{ $payment->receipt_number ?? 'Pending' }}</strong>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Allocated Amount:</dt>
                                <dd class="col-sm-7">
                                    <strong class="text-success">Ksh {{ number_format($actualAllocatedAmount, 2) }}</strong>
                                    @if($payment->reversed)
                                        <span class="badge bg-danger ms-2">Reversed</span>
                                    @endif
                                </dd>

                                <dt class="col-sm-5">Unallocated Amount:</dt>
                                <dd class="col-sm-7">
                                    <strong class="text-warning">Ksh {{ number_format($actualUnallocatedAmount, 2) }}</strong>
                                </dd>

                                <dt class="col-sm-5">Transaction Code:</dt>
                                <dd class="col-sm-7"><code>{{ $payment->transaction_code ?? 'N/A' }}</code></dd>

                                <dt class="col-sm-5">Payer Name:</dt>
                                <dd class="col-sm-7">{{ $payment->payer_name ?? 'N/A' }}</dd>

                                <dt class="col-sm-5">Payer Type:</dt>
                                <dd class="col-sm-7">
                                    @if($payment->payer_type)
                                        <span class="badge bg-info">{{ ucfirst($payment->payer_type) }}</span>
                                    @else
                                        N/A
                                    @endif
                                </dd>

                                @if($payment->narration)
                                <dt class="col-sm-5">Narration:</dt>
                                <dd class="col-sm-7">{{ $payment->narration }}</dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Allocations -->
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payment Allocations</h5>
                    @if(!$payment->reversed && $actualUnallocatedAmount > 0.01)
                    <button type="button" class="btn btn-sm btn-finance btn-finance-primary" data-bs-toggle="modal" data-bs-target="#allocateModal">
                        <i class="bi bi-plus-circle"></i> Allocate Payment
                    </button>
                    @endif
                </div>
                <div class="finance-card-body p-0">
                    @php
                        // For reversed payments, allocations should be empty (they were deleted)
                        // But we still want to show the "No allocations yet" message
                        $allocations = $payment->reversed ? collect() : ($payment->allocations ?? collect());
                        $invoiceItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($payment) {
                            $q->where('student_id', $payment->student_id);
                        })->where('status', 'active')->get();
                    @endphp
                    
                    @if($payment->reversed)
                    <div class="p-4 text-center">
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle"></i> 
                            <strong>This payment has been reversed.</strong>
                            @if($payment->reversal_reason)
                                <br><small>Reason: {{ $payment->reversal_reason }}</small>
                            @endif
                        </div>
                        <p class="text-muted mb-0">All allocations have been removed. This payment is no longer active.</p>
                    </div>
                    @elseif($allocations->isNotEmpty())
                    <div class="table-responsive px-3 pb-3">
                        <table class="finance-table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice</th>
                                    <th>Votehead</th>
                                    <th class="text-end">Item Amount</th>
                                    <th class="text-end">Allocated</th>
                                    <th class="text-end">Remaining</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allocations as $allocation)
                                @php
                                    $item = $allocation->invoiceItem ?? null;
                                    $invoice = $item->invoice ?? null;
                                @endphp
                                <tr>
                                    <td>
                                        @if($invoice)
                                            <a href="{{ route('finance.invoices.show', $invoice) }}">
                                                {{ $invoice->invoice_number }}
                                            </a>
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>{{ $item->votehead->name ?? 'N/A' }}</td>
                                    <td class="text-end">Ksh {{ number_format($item->amount ?? 0, 2) }}</td>
                                    <td class="text-end">
                                        <strong class="text-success">Ksh {{ number_format($allocation->amount, 2) }}</strong>
                                    </td>
                                    <td class="text-end">
                                        Ksh {{ number_format(($item->amount ?? 0) - ($allocation->amount ?? 0), 2) }}
                                    </td>
                                    <td>{{ $allocation->allocated_at ? \Carbon\Carbon::parse($allocation->allocated_at)->format('d M Y') : 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="p-4 text-center">
                        <p class="text-muted mb-3">No allocations yet.</p>
                        @if(!$payment->reversed && $actualUnallocatedAmount > 0.01)
                        <button type="button" class="btn btn-finance btn-finance-primary" data-bs-toggle="modal" data-bs-target="#allocateModal">
                            <i class="bi bi-plus-circle"></i> Allocate Payment
                        </button>
                        @endif
                    </div>
                    @endif
                </div>
            </div>

            <!-- Shared Transaction Information -->
            @if(isset($sharedInfo) && $sharedInfo && $sharedInfo['is_shared'])
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-people me-2"></i>Shared Among Siblings
                    </h5>
                    <button type="button" class="btn btn-sm btn-finance btn-finance-primary" onclick="toggleEditSharedAllocations()">
                        <i class="bi bi-pencil"></i> Edit Amounts
                    </button>
                </div>
                <div class="finance-card-body p-4">
                    <div class="mb-3">
                        <p class="text-muted mb-0">
                            <strong>Total Payment Amount:</strong> 
                            <span class="text-success">Ksh {{ number_format($sharedInfo['total_amount'], 2) }}</span>
                        </p>
                        <p class="text-muted mb-0">
                            <strong>Shared with:</strong> {{ count($sharedInfo['shared_allocations']) }} sibling(s)
                        </p>
                    </div>
                    
                    <div id="sharedAllocationsDisplay">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Admission Number</th>
                                        <th class="text-end">Amount</th>
                                        <th class="text-center">Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sharedInfo['shared_allocations'] as $allocation)
                                    @php
                                        $student = \App\Models\Student::find($allocation['student_id']);
                                        $siblingPayment = collect([$payment])->merge($sharedInfo['sibling_payments'])->firstWhere('student_id', $allocation['student_id']);
                                    @endphp
                                    <tr class="{{ $payment->student_id == $allocation['student_id'] ? 'table-primary' : '' }}">
                                        <td>
                                            @if($student)
                                                <a href="{{ route('students.show', $student) }}">
                                                    {{ $student->full_name }}
                                                </a>
                                                @if($payment->student_id == $allocation['student_id'])
                                                    <span class="badge bg-primary ms-2">Current</span>
                                                @endif
                                            @else
                                                <span class="text-muted">Student #{{ $allocation['student_id'] }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $student->admission_number ?? 'N/A' }}</td>
                                        <td class="text-end">
                                            <strong>Ksh {{ number_format($allocation['amount'], 2) }}</strong>
                                        </td>
                                        <td class="text-center">
                                            @if($siblingPayment)
                                                <a href="{{ route('finance.payments.show', $siblingPayment) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-receipt"></i> View
                                                </a>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <form id="editSharedAllocationsForm" method="POST" action="{{ route('finance.payments.update-shared-allocations', $payment) }}" style="display: none;">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <p class="text-muted">
                                <strong>Total amount:</strong> 
                                <span class="text-success">Ksh {{ number_format($sharedInfo['total_amount'], 2) }}</span>
                            </p>
                            <p class="text-warning small mb-3">
                                <i class="bi bi-exclamation-triangle"></i> 
                                Total shared amounts must equal exactly <strong>Ksh {{ number_format($sharedInfo['total_amount'], 2) }}</strong>
                            </p>
                        </div>
                        
                        @foreach($sharedInfo['shared_allocations'] as $index => $allocation)
                        @php
                            $student = \App\Models\Student::find($allocation['student_id']);
                        @endphp
                        <div class="mb-3 p-3 border rounded {{ $payment->student_id == $allocation['student_id'] ? 'bg-light' : '' }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <strong>
                                        @if($student)
                                            {{ $student->full_name }}
                                        @else
                                            Student #{{ $allocation['student_id'] }}
                                        @endif
                                    </strong>
                                    @if($payment->student_id == $allocation['student_id'])
                                        <span class="badge bg-primary ms-2">Current Payment</span>
                                    @endif
                                    <br>
                                    <small class="text-muted">{{ $student->admission_number ?? 'N/A' }}</small>
                                </div>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text">Ksh</span>
                                <input type="number" 
                                       step="0.01" 
                                       min="0" 
                                       class="form-control shared-allocation-amount" 
                                       name="allocations[{{ $index }}][amount]" 
                                       value="{{ number_format($allocation['amount'], 2, '.', '') }}"
                                       oninput="updateTotalSharedAllocations()"
                                       required>
                                <input type="hidden" name="allocations[{{ $index }}][student_id]" value="{{ $allocation['student_id'] }}">
                            </div>
                        </div>
                        @endforeach
                        <div id="sharedAllocationsExtras"></div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addSharedAllocationStudent">
                            <i class="bi bi-plus"></i> Add Another Student
                        </button>
                        
                        <div class="mt-3 p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Total Allocated:</strong>
                                    <span id="totalSharedAllocationsAmount" class="fs-5">Ksh 0.00</span>
                                </div>
                                <div>
                                    <strong>Remaining:</strong>
                                    <span id="remainingSharedAllocationsAmount" class="fs-5">Ksh {{ number_format($sharedInfo['total_amount'], 2) }}</span>
                                </div>
                            </div>
                            <div class="progress mt-2" style="height: 8px;">
                                <div id="sharedAllocationsProgress" class="progress-bar" role="progressbar" style="width: 0%"></div>
                            </div>
                            <small id="sharedAllocationsStatus" class="text-muted">Allocate exactly Ksh {{ number_format($sharedInfo['total_amount'], 2) }}</small>
                        </div>
                        
                        <div class="mt-3 d-flex gap-2">
                            <button type="submit" class="btn btn-finance btn-finance-primary" id="submitSharedAllocationsBtn" disabled>
                                <i class="bi bi-check-circle"></i> Update Allocations
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleEditSharedAllocations()">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @endif
        </div>

        <div class="col-md-4">
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="finance-card-body d-grid gap-2 p-4">
                    <div class="d-grid gap-2">
                        <a href="{{ route('finance.payments.receipt.view', $payment) }}" 
                           class="btn btn-finance btn-finance-primary"
                           onclick="window.open('{{ route('finance.payments.receipt.view', $payment) }}', 'ReceiptWindow', 'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no'); return false;">
                            <i class="bi bi-printer"></i> View/Print Receipt
                        </a>
                        <button type="button" class="btn btn-finance btn-finance-secondary" 
                                onclick="openSendDocument('receipt', [{{ $payment->id }}], {message:'Your receipt is ready. Please find the link below.'})">
                            <i class="bi bi-send"></i> Send Receipt (SMS/WhatsApp/Email)
                        </button>
                        @if($payment->student_id)
                        <a href="{{ route('finance.invoices.index', ['student_id' => $payment->student_id]) }}" class="btn btn-finance btn-finance-outline">
                            <i class="bi bi-file-text"></i> View Student Invoices
                        </a>
                        @endif
                        @if(!$payment->reversed)
                        <button type="button" class="btn btn-finance btn-finance-secondary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#transferPaymentModal">
                            <i class="bi bi-arrow-left-right"></i> Transfer/Share Payment
                        </button>
                        @if(isset($siblings) && $siblings->isNotEmpty())
                        <a href="{{ route('finance.payments.show', $payment, ['action' => 'share']) }}" class="btn btn-finance btn-finance-primary w-100 mb-2">
                            <i class="bi bi-people"></i> Share with Siblings
                        </a>
                        @endif
                        <form action="{{ route('finance.payments.reverse', $payment) }}" method="POST" id="reversePaymentForm" onsubmit="return confirmPaymentReversal(event)">
                            @csrf
                            @method('DELETE')
                            <div class="mb-2">
                                <label for="reversal_reason" class="form-label small">Reversal Reason (Optional)</label>
                                <textarea name="reversal_reason" id="reversal_reason" class="form-control form-control-sm" rows="2" maxlength="500" placeholder="Enter reason for reversal..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-arrow-counterclockwise"></i> Reverse Payment
                            </button>
                        </form>
                        <script>
                        function confirmPaymentReversal(e) {
                            e.preventDefault();
                            const form = e.target;
                            const allocationsCount = {{ $payment->allocations->count() }};
                            const amount = {{ $payment->amount }};
                            const allocatedAmount = {{ $payment->allocated_amount ?? 0 }};
                            
                            let message = 'Are you sure you want to reverse this payment?\n\n';
                            message += `Payment Amount: Ksh ${amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}\n`;
                            if (allocationsCount > 0) {
                                message += `This will affect ${allocationsCount} allocation(s) and ${allocatedAmount > 0 ? 'recalculate related invoices' : 'remove all allocations'}.\n`;
                            }
                            message += '\nThis action cannot be undone.';
                            
                            if (confirm(message)) {
                                form.submit();
                            }
                            return false;
                        }
                        </script>
                        @else
                        <div class="alert alert-warning mb-0">
                            <small><i class="bi bi-info-circle"></i> This payment has been reversed.</small>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Payment Summary</h5>
                </div>
                <div class="finance-card-body p-4">
                    @if($payment->reversed)
                        <div class="alert alert-warning mb-3">
                            <i class="bi bi-exclamation-triangle"></i> 
                            <strong>This payment has been reversed.</strong>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <small class="finance-muted d-block">Total Paid</small>
                            <span class="h5 text-primary mb-0">Ksh {{ number_format($payment->amount, 2) }}</span>
                        </div>
                        @if($payment->reversed)
                            <span class="finance-badge badge-danger">Reversed</span>
                        @else
                            <span class="finance-badge badge-paid">Paid</span>
                        @endif
                    </div>
                    <div class="mb-2">
                        <strong>Allocated:</strong><br>
                        <span class="h6 text-success">Ksh {{ number_format($actualAllocatedAmount, 2) }}</span>
                    </div>
                    <div class="mb-0">
                        <strong>Unallocated:</strong><br>
                        <span class="h6 text-warning">Ksh {{ number_format($actualUnallocatedAmount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>

<!-- Allocation Modal -->
@if(!$payment->reversed && $actualUnallocatedAmount > 0.01)
<div class="modal fade" id="allocateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('finance.payments.allocate', $payment) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Allocate Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">
                        Available to allocate: <strong>Ksh {{ number_format($actualUnallocatedAmount, 2) }}</strong>
                    </p>
                    
                    <div id="allocation_items">
                        @php
                            $outstandingItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($payment) {
                                $q->where('student_id', $payment->student_id)->where('status', '!=', 'paid');
                            })->where('status', 'active')->with('invoice', 'votehead')->get();
                        @endphp
                        
                        @if($outstandingItems->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Votehead</th>
                                        <th class="text-end">Outstanding</th>
                                        <th class="text-end">Allocate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($outstandingItems as $item)
                                    @php
                                        $outstanding = $item->amount - ($item->getAllocatedAmount() ?? 0);
                                    @endphp
                                    @if($outstanding > 0)
                                    <tr>
                                        <td>{{ $item->invoice->invoice_number ?? 'N/A' }}</td>
                                        <td>{{ $item->votehead->name ?? 'N/A' }}</td>
                                        <td class="text-end">Ksh {{ number_format($outstanding, 2) }}</td>
                                        <td class="text-end">
                                            <input type="number" 
                                                   name="allocations[{{ $loop->index }}][amount]" 
                                                   step="0.01" 
                                                   min="0" 
                                                   max="{{ $outstanding }}"
                                                   class="form-control form-control-sm allocation-amount" 
                                                   style="width: 120px; display: inline-block;">
                                            <input type="hidden" name="allocations[{{ $loop->index }}][invoice_item_id]" value="{{ $item->id }}">
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info">No outstanding invoice items found for this student.</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-finance btn-finance-primary">Allocate Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<!-- Transfer/Share Payment Modal -->
@if(!$payment->reversed)
<div class="modal fade" id="transferPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('finance.payments.transfer', $payment) }}" method="POST" id="transferPaymentForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Transfer/Share Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <strong>Available to transfer:</strong> Ksh {{ number_format($payment->amount, 2) }}
                        @if($actualAllocatedAmount > 0.01)
                            <div class="small text-info mt-1">
                                <i class="bi bi-info-circle"></i> 
                                Note: This payment has allocated amounts. Transferring allocated amounts will automatically deallocate them from the original student's invoices.
                            </div>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Transfer Type <span class="text-danger">*</span></label>
                        <select name="transfer_type" id="transferType" class="form-select" required>
                            <option value="">-- Select Type --</option>
                            <option value="transfer">Transfer to Another Student</option>
                            <option value="share">Share Among Multiple Students</option>
                        </select>
                    </div>
                    
                    <div id="transferSingleStudent" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Student <span class="text-danger">*</span></label>
                        @include('partials.student_live_search', [
                            'hiddenInputId' => 'targetStudentId',
                            'hiddenInputName' => 'target_student_id',
                            'displayInputId' => 'targetStudentName',
                            'resultsId' => 'targetStudentResults',
                            'placeholder' => 'Type name or admission #',
                            'inputClass' => 'form-control'
                        ])
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount to Transfer <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Ksh</span>
                                <input type="number" step="0.01" min="0.01" max="{{ $payment->amount }}" name="transfer_amount" id="transferAmount" class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <div id="transferMultipleStudents" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Students to Share With <span class="text-danger">*</span></label>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> Total shared amounts must equal exactly <strong>Ksh {{ number_format($payment->amount, 2) }}</strong>
                            </div>
                            @if(isset($siblings) && $siblings->isNotEmpty())
                                <div class="mb-3">
                                    <label class="form-label text-muted small">Siblings</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($siblings as $sibling)
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm add-sibling-btn"
                                                    data-student-id="{{ $sibling->id }}"
                                                    data-student-name="{{ $sibling->full_name }}"
                                                    data-student-admission="{{ $sibling->admission_number }}">
                                                <i class="bi bi-person-plus"></i> {{ $sibling->full_name }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <small class="text-muted">Click a sibling to add them to the share list.</small>
                                </div>
                            @endif
                            <div id="sharedStudentsList">
                                <!-- Original student (pre-populated) -->
                                <div class="shared-student-item mb-3 border-bottom pb-2">
                                    <label class="form-label text-muted small">Original Student</label>
                                    <input type="hidden" name="shared_students[]" value="{{ $payment->student_id }}" class="shared-student-id">
                                    <input type="text" class="form-control shared-student-name" value="{{ $payment->student->full_name }} ({{ $payment->student->admission_number }})" readonly>
                                    @php
                                        $studentBalance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($payment->student);
                                        $studentInvoiced = \App\Models\Invoice::where('student_id', $payment->student_id)->sum('total') ?? 0;
                                        $studentPaid = \App\Models\Payment::where('student_id', $payment->student_id)->where('reversed', false)->sum('amount') ?? 0;
                                    @endphp
                                    <small class="text-muted d-block mt-1">
                                        <strong>Balance:</strong> <span class="text-danger">Ksh {{ number_format($studentBalance, 2) }}</span>
                                        | <strong>Invoiced:</strong> Ksh {{ number_format($studentInvoiced, 2) }}
                                        | <strong>Paid:</strong> <span class="text-success">Ksh {{ number_format($studentPaid, 2) }}</span>
                                    </small>
                                    <div class="input-group mt-2">
                                        <span class="input-group-text">Ksh</span>
                                        <input type="number" step="0.01" min="0" max="{{ $payment->amount }}" name="shared_amounts[]" class="form-control shared-amount" placeholder="Amount" value="{{ number_format($payment->amount, 2, '.', '') }}" required>
                                        <span class="input-group-text bg-secondary text-white">Cannot Remove</span>
                                    </div>
                                </div>
                                <!-- Additional students start here -->
                                <div class="shared-student-item mb-2" data-index="1">
                                    @include('partials.student_live_search', [
                                        'hiddenInputId' => 'shared_student_id_1',
                                        'hiddenInputName' => 'shared_students[]',
                                        'displayInputId' => 'shared_student_name_1',
                                        'resultsId' => 'shared_student_results_1',
                                        'placeholder' => 'Type name or admission #',
                                        'inputClass' => 'form-control shared-student-name'
                                    ])
                                    <div class="input-group mt-2">
                                        <span class="input-group-text">Ksh</span>
                                        <input type="number" step="0.01" min="0" name="shared_amounts[]" class="form-control shared-amount" placeholder="Amount" value="0.00" required>
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-student-btn">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addSharedStudent">
                                <i class="bi bi-plus"></i> Add Another Student
                            </button>
                            <div class="mt-3 p-3 bg-light rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>Total Allocated:</strong>
                                        <span id="totalSharedAmount" class="fs-5">Ksh 0.00</span>
                                    </div>
                                    <div>
                                        <strong>Remaining:</strong>
                                        <span id="remainingAmount" class="fs-5">Ksh {{ number_format($payment->amount, 2) }}</span>
                                    </div>
                                </div>
                                <div class="progress mt-2" style="height: 8px;">
                                    <div id="allocationProgress" class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small id="allocationStatus" class="text-muted">Allocate exactly Ksh {{ number_format($payment->amount, 2) }}</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason/Notes</label>
                        <textarea name="transfer_reason" class="form-control" rows="2" placeholder="Reason for transfer/share"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-finance btn-finance-primary">Transfer Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const allocationInputs = document.querySelectorAll('.allocation-amount');
    const availableAmount = {{ $actualUnallocatedAmount }};
    let totalAllocated = 0;

    allocationInputs.forEach(input => {
        input.addEventListener('input', function() {
            // Recalculate total
            totalAllocated = Array.from(allocationInputs).reduce((sum, inp) => {
                return sum + (parseFloat(inp.value) || 0);
            }, 0);

            // Validate total doesn't exceed available
            if (totalAllocated > availableAmount) {
                this.setCustomValidity('Total allocation cannot exceed available amount');
            } else {
                this.setCustomValidity('');
            }
        });
    });
    
    // Transfer/Share Payment functionality
    const transferType = document.getElementById('transferType');
    const transferSingle = document.getElementById('transferSingleStudent');
    const transferMultiple = document.getElementById('transferMultipleStudents');
    const maxTransferAmount = {{ $payment->amount }};
    
    if (transferType) {
        transferType.addEventListener('change', function() {
            const transferAmountField = document.getElementById('transferAmount');
            const targetStudentField = document.getElementById('targetStudentId');
            const targetDisplayField = document.getElementById('targetStudentName');
            
            if (this.value === 'transfer') {
                transferSingle.style.display = 'block';
                transferMultiple.style.display = 'none';
                // Enable validation and submission for single transfer fields
                if (transferAmountField) {
                    transferAmountField.required = true;
                    transferAmountField.disabled = false;
                }
                if (targetStudentField) {
                    targetStudentField.required = true;
                    targetStudentField.disabled = false;
                }
                if (targetDisplayField) targetDisplayField.disabled = false;
            } else if (this.value === 'share') {
                transferSingle.style.display = 'none';
                transferMultiple.style.display = 'block';
                // Disable fields so they won't be submitted with the form
                if (transferAmountField) {
                    transferAmountField.required = false;
                    transferAmountField.disabled = true;
                    transferAmountField.value = ''; // Clear the value
                }
                if (targetStudentField) {
                    targetStudentField.required = false;
                    targetStudentField.disabled = true;
                    targetStudentField.value = ''; // Clear the value
                }
                if (targetDisplayField) {
                    targetDisplayField.disabled = true;
                    targetDisplayField.value = ''; // Clear the value
                }
                // Initialize total calculation when share is selected
                updateTotalShared();
            } else {
                transferSingle.style.display = 'none';
                transferMultiple.style.display = 'none';
                // Disable all fields when nothing selected
                if (transferAmountField) {
                    transferAmountField.required = false;
                    transferAmountField.disabled = true;
                }
                if (targetStudentField) {
                    targetStudentField.required = false;
                    targetStudentField.disabled = true;
                }
                if (targetDisplayField) targetDisplayField.disabled = true;
            }
        });
    }
    
    // Validate transfer amount
    const transferAmountInput = document.getElementById('transferAmount');
    if (transferAmountInput) {
        transferAmountInput.addEventListener('input', function() {
            const amount = parseFloat(this.value) || 0;
            if (amount > maxTransferAmount) {
                this.setCustomValidity(`Amount cannot exceed payment amount of Ksh ${maxTransferAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
            } else if (amount <= 0) {
                this.setCustomValidity('Amount must be greater than 0');
            } else {
                this.setCustomValidity('');
            }
        });
    }
    
    // Load student balance information
    async function loadStudentBalanceInfo(studentId, wrapper) {
        try {
            // Find or create balance info container in this wrapper
            let balanceContainer = wrapper.querySelector('.student-balance-info');
            if (!balanceContainer) {
                balanceContainer = document.createElement('div');
                balanceContainer.className = 'student-balance-info mt-2';
                // Insert after the student name input
                const inputGroup = wrapper.querySelector('.input-group');
                if (inputGroup && inputGroup.previousElementSibling) {
                    inputGroup.previousElementSibling.after(balanceContainer);
                } else if (wrapper.querySelector('input[type="text"]')) {
                    wrapper.querySelector('input[type="text"]').after(balanceContainer);
                }
            }
            
            balanceContainer.innerHTML = '<small class="text-muted"><i class="bi bi-hourglass-split"></i> Loading balance...</small>';
            
            const response = await fetch(`{{ route('finance.payments.student-info', ['student' => '__ID__']) }}`.replace('__ID__', studentId), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            const balance = parseFloat(data.balance?.total_balance || 0);
            const invoiced = parseFloat(data.balance?.total_invoiced || 0);
            const paid = parseFloat(data.balance?.total_paid || 0);
            
            balanceContainer.innerHTML = `
                <small class="text-muted">
                    <strong>Balance:</strong> <span class="text-danger">Ksh ${balance.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    | <strong>Invoiced:</strong> Ksh ${invoiced.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}
                    | <strong>Paid:</strong> <span class="text-success">Ksh ${paid.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                </small>
            `;
        } catch (error) {
            console.error('Error loading student balance:', error);
            const balanceContainer = wrapper.querySelector('.student-balance-info');
            if (balanceContainer) {
                balanceContainer.innerHTML = '<small class="text-danger"><i class="bi bi-exclamation-triangle"></i> Unable to load balance</small>';
            }
        }
    }
    
    // Simple live-search initializer for wrappers in this modal (works for dynamically added rows)
    function initLiveSearchWrapper(wrapper) {
        const displayInput = wrapper.querySelector('input[type="text"]');
        const hiddenInput = wrapper.querySelector('input[type="hidden"]');
        const resultsList = wrapper.querySelector('.student-search-results');
        let t = null;
        const debounceMs = 300;
        if (!displayInput || !hiddenInput || !resultsList) return;

        const render = (items) => {
            resultsList.innerHTML = '';
            if (!items.length) {
                resultsList.classList.add('d-none');
                return;
            }
            items.forEach(item => {
                const a = document.createElement('a');
                a.href = '#';
                a.className = 'list-group-item list-group-item-action py-2';
                a.textContent = `${item.full_name} (${item.admission_number}) - ${item.classroom_name || 'No Class'}`;
                a.addEventListener('click', async (e) => {
                    e.preventDefault();
                    displayInput.value = `${item.full_name} (${item.admission_number})`;
                    hiddenInput.value = item.id;
                    resultsList.classList.add('d-none');
                    
                    // Fetch and display student balance info
                    await loadStudentBalanceInfo(item.id, wrapper);
                    
                    updateTotalShared();
                });
                resultsList.appendChild(a);
            });
            resultsList.classList.remove('d-none');
        };

        displayInput.addEventListener('input', () => {
            clearTimeout(t);
            const q = displayInput.value.trim();
            hiddenInput.value = '';
            if (q.length < 2) {
                resultsList.classList.add('d-none');
                return;
            }
            resultsList.innerHTML = '<div class="list-group-item text-center text-muted">Searching...</div>';
            resultsList.classList.remove('d-none');
            t = setTimeout(async () => {
                try {
                    const res = await fetch(`{{ route('students.search') }}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    render(data);
                } catch (err) {
                    console.error('Student search error', err);
                    resultsList.innerHTML = '<div class="list-group-item text-danger text-center">Search failed</div>';
                }
            }, debounceMs);
        });

        document.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                resultsList.classList.add('d-none');
            }
        });
    }

    document.querySelectorAll('#transferPaymentModal .student-live-search-wrapper').forEach(initLiveSearchWrapper);
    
    function getSharedStudentIds() {
        return Array.from(document.querySelectorAll('#sharedStudentsList input[name="shared_students[]"]'))
            .map(el => parseInt(el.value, 10))
            .filter(id => !Number.isNaN(id) && id > 0);
    }
    
    function getSharedTemplateItem(list) {
        const items = Array.from(list.querySelectorAll('.shared-student-item'));
        const candidates = items.filter(item => item.querySelector('.student-live-search-wrapper') && !item.dataset.readonly);
        return candidates[candidates.length - 1] || items[items.length - 1];
    }
    
    function addSharedStudentRow({ studentId = null, studentLabel = '', readOnly = false } = {}) {
        const list = document.getElementById('sharedStudentsList');
        if (!list) return;
        
        const existingIds = getSharedStudentIds();
        if (studentId && existingIds.includes(parseInt(studentId, 10))) {
            return;
        }
        
        const template = getSharedTemplateItem(list);
        if (!template) return;
        
        const clone = template.cloneNode(true);
        const newIndex = list.querySelectorAll('.shared-student-item').length + 1;
        
        clone.setAttribute('data-index', newIndex);
        if (readOnly) {
            clone.dataset.readonly = '1';
        } else {
            delete clone.dataset.readonly;
        }
        clone.querySelectorAll('[id]').forEach(el => {
            const oldId = el.id;
            const baseName = oldId.replace(/_\d+$/, '');
            el.id = `${baseName}_${newIndex}`;
        });
        
        // Reset values
        clone.querySelectorAll('[name="shared_students[]"]').forEach(el => el.value = '');
        clone.querySelectorAll('[name="shared_amounts[]"]').forEach(el => el.value = '0.00');
        clone.querySelectorAll('input[type="text"]').forEach(el => {
            el.value = '';
            el.readOnly = false;
            el.classList.remove('bg-light');
        });
        clone.querySelectorAll('.student-search-results').forEach(el => {
            el.classList.add('d-none');
            el.innerHTML = '';
        });
        clone.querySelectorAll('.student-balance-info').forEach(el => el.remove());
        
        list.appendChild(clone);
        
        const wrapper = clone.querySelector('.student-live-search-wrapper');
        if (wrapper && !readOnly) {
            initLiveSearchWrapper(wrapper);
        }
        
        const displayInput = clone.querySelector('input[type="text"]');
        const hiddenInput = clone.querySelector('input[type="hidden"]');
        if (studentId && hiddenInput) {
            hiddenInput.value = studentId;
        }
        if (studentLabel && displayInput) {
            displayInput.value = studentLabel;
            if (readOnly) {
                displayInput.readOnly = true;
                displayInput.classList.add('bg-light');
            }
        }
        
        if (studentId && wrapper) {
            loadStudentBalanceInfo(studentId, wrapper);
        }
        
        clone.querySelector('.remove-student-btn')?.addEventListener('click', function() {
            clone.remove();
            updateTotalShared();
        });
        
        clone.querySelector('.shared-amount')?.addEventListener('input', updateTotalShared);
        
        updateTotalShared();
    }
    
    // Add shared student (clones the last added item's markup)
    const addSharedStudentBtn = document.getElementById('addSharedStudent');
    if (addSharedStudentBtn) {
        addSharedStudentBtn.addEventListener('click', function() {
            addSharedStudentRow();
        });
    }
    
    document.querySelectorAll('.add-sibling-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const studentId = this.getAttribute('data-student-id');
            const studentName = this.getAttribute('data-student-name');
            const admission = this.getAttribute('data-student-admission');
            const label = admission ? `${studentName} (${admission})` : studentName;
            addSharedStudentRow({ studentId, studentLabel: label, readOnly: true });
        });
    });
    
    // Update total shared amount with exact validation
    function updateTotalShared() {
        const amounts = document.querySelectorAll('.shared-amount');
        let total = 0;
        amounts.forEach(input => {
            const amount = parseFloat(input.value) || 0;
            total += amount;
        });
        
        const remaining = maxTransferAmount - total;
        const tolerance = 0.01; // Allow 1 cent tolerance
        
        // Update UI elements
        const totalElement = document.getElementById('totalSharedAmount');
        const remainingElement = document.getElementById('remainingAmount');
        const statusElement = document.getElementById('allocationStatus');
        const progressBar = document.getElementById('allocationProgress');
        const form = document.getElementById('transferPaymentForm');
        const submitBtn = form?.querySelector('button[type="submit"]');
        
        if (totalElement) {
            totalElement.textContent = `Ksh ${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
        
        if (remainingElement) {
            remainingElement.textContent = `Ksh ${Math.max(0, remaining).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
        
        // Calculate percentage
        const percentage = Math.min(100, (total / maxTransferAmount) * 100);
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
        }
        
        // Validate and update status
        if (Math.abs(remaining) < tolerance) {
            // Exact match - valid
            if (progressBar) {
                progressBar.className = 'progress-bar bg-success';
            }
            if (statusElement) {
                statusElement.textContent = '✓ Total matches payment amount exactly';
                statusElement.className = 'text-success';
            }
            if (submitBtn) submitBtn.disabled = false;
            
            // Clear validation errors
            amounts.forEach(input => input.setCustomValidity(''));
        } else if (remaining < -tolerance) {
            // Over-allocated - invalid
            if (progressBar) {
                progressBar.className = 'progress-bar bg-danger';
            }
            if (statusElement) {
                statusElement.textContent = `⚠ Total exceeds payment amount by Ksh ${Math.abs(remaining).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                statusElement.className = 'text-danger';
            }
            if (submitBtn) submitBtn.disabled = true;
            
            // Set validation error
            amounts.forEach(input => {
                if (parseFloat(input.value) > 0) {
                    input.setCustomValidity('Total shared amount exceeds payment amount');
                }
            });
        } else {
            // Under-allocated - invalid
            if (progressBar) {
                progressBar.className = 'progress-bar bg-warning';
            }
            if (statusElement) {
                statusElement.textContent = `⚠ Need to allocate Ksh ${remaining.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} more`;
                statusElement.className = 'text-warning';
            }
            if (submitBtn) submitBtn.disabled = true;
            
            // Clear validation errors but disable submit
            amounts.forEach(input => input.setCustomValidity(''));
        }
    }
    
    // Initialize shared amount validation
    document.querySelectorAll('.shared-amount').forEach(input => {
        input.addEventListener('input', updateTotalShared);
    });
    
    // Remove student functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-student-btn')) {
            e.target.closest('.shared-student-item').remove();
            updateTotalShared();
        }
    });
    
    // Form validation on submit
    const transferForm = document.getElementById('transferPaymentForm');
    if (transferForm) {
        transferForm.addEventListener('submit', function(e) {
            if (transferType && transferType.value === 'transfer') {
                const amount = parseFloat(transferAmountInput?.value || 0);
                if (amount > maxTransferAmount) {
                    e.preventDefault();
                    alert(`Transfer amount cannot exceed payment amount of Ksh ${maxTransferAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    return false;
                }
            } else if (transferType && transferType.value === 'share') {
                // Validate students with non-zero amounts
                const studentInputs = document.querySelectorAll('#sharedStudentsList input[name="shared_students[]"]');
                const amountInputs = document.querySelectorAll('#sharedStudentsList .shared-amount');
                let hasMissingStudent = false;
                let missingIndex = -1;
                let total = 0;
                
                amountInputs.forEach((amountInput, index) => {
                    const amount = parseFloat(amountInput.value) || 0;
                    total += amount;
                    const studentInput = studentInputs[index];
                    if (amount > 0 && (!studentInput || !studentInput.value || studentInput.value.trim() === '')) {
                        hasMissingStudent = true;
                        missingIndex = index;
                    }
                });
                
                if (hasMissingStudent) {
                    e.preventDefault();
                    alert(`Please select a valid student for all entries with an amount. Entry #${missingIndex + 1} has an amount but no student selected. Make sure to:\n1. Type the student name\n2. Wait for search results\n3. Click on a student from the dropdown`);
                    return false;
                }
                
                const tolerance = 0.01;
                if (Math.abs(total - maxTransferAmount) > tolerance) {
                    e.preventDefault();
                    alert(`Total shared amounts (Ksh ${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}) must equal exactly the payment amount of Ksh ${maxTransferAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                    return false;
                }
            }
        });
    }
    
    // Reinitialize live search when modal is shown
    const transferModal = document.getElementById('transferPaymentModal');
    if (transferModal) {
        transferModal.addEventListener('shown.bs.modal', function () {
            // Reinitialize all live search wrappers in the modal
            document.querySelectorAll('#transferPaymentModal .student-live-search-wrapper').forEach(initLiveSearchWrapper);
        });
    }
    
    // Auto-open transfer/share modal from query param
    const params = new URLSearchParams(window.location.search);
    const action = params.get('action');
    if (transferModal && action && (action === 'share' || action === 'transfer')) {
        const modal = new bootstrap.Modal(transferModal);
        modal.show();
        if (transferType) {
            transferType.value = action === 'transfer' ? 'transfer' : 'share';
            transferType.dispatchEvent(new Event('change'));
        }
    }
});
    
    @if(isset($sharedInfo) && $sharedInfo && $sharedInfo['is_shared'])
    // Shared Allocations functionality
    function toggleEditSharedAllocations() {
        const display = document.getElementById('sharedAllocationsDisplay');
        const form = document.getElementById('editSharedAllocationsForm');
        
        if (display.style.display === 'none') {
            display.style.display = 'block';
            form.style.display = 'none';
        } else {
            display.style.display = 'none';
            form.style.display = 'block';
            updateTotalSharedAllocations();
        }
    }
    
    function updateTotalSharedAllocations() {
        const amounts = document.querySelectorAll('.shared-allocation-amount');
        const totalAmount = {{ $sharedInfo['total_amount'] }};
        let total = 0;
        
        amounts.forEach(input => {
            const amount = parseFloat(input.value) || 0;
            total += amount;
        });
        
        const remaining = totalAmount - total;
        const tolerance = 0.01;
        
        // Update UI elements
        const totalElement = document.getElementById('totalSharedAllocationsAmount');
        const remainingElement = document.getElementById('remainingSharedAllocationsAmount');
        const statusElement = document.getElementById('sharedAllocationsStatus');
        const progressBar = document.getElementById('sharedAllocationsProgress');
        const submitBtn = document.getElementById('submitSharedAllocationsBtn');
        
        if (totalElement) {
            totalElement.textContent = `Ksh ${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
        
        if (remainingElement) {
            remainingElement.textContent = `Ksh ${Math.max(0, remaining).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        }
        
        // Calculate percentage
        const percentage = Math.min(100, (total / totalAmount) * 100);
        if (progressBar) {
            progressBar.style.width = `${percentage}%`;
        }
        
        // Validate and update status
        if (Math.abs(remaining) < tolerance) {
            // Exact match - valid
            if (progressBar) {
                progressBar.className = 'progress-bar bg-success';
            }
            if (statusElement) {
                statusElement.textContent = '✓ Total matches payment amount exactly';
                statusElement.className = 'text-success';
            }
            if (submitBtn) submitBtn.disabled = false;
            
            // Clear validation errors
            amounts.forEach(input => input.setCustomValidity(''));
        } else if (remaining < -tolerance) {
            // Over-allocated - invalid
            if (progressBar) {
                progressBar.className = 'progress-bar bg-danger';
            }
            if (statusElement) {
                statusElement.textContent = `⚠ Total exceeds payment amount by Ksh ${Math.abs(remaining).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                statusElement.className = 'text-danger';
            }
            if (submitBtn) submitBtn.disabled = true;
            
            // Set validation error
            amounts.forEach(input => {
                if (parseFloat(input.value) > 0) {
                    input.setCustomValidity('Total shared amount exceeds payment amount');
                }
            });
        } else {
            // Under-allocated - invalid
            if (progressBar) {
                progressBar.className = 'progress-bar bg-warning';
            }
            if (statusElement) {
                statusElement.textContent = `⚠ Need to allocate Ksh ${remaining.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} more`;
                statusElement.className = 'text-warning';
            }
            if (submitBtn) submitBtn.disabled = true;
            
            // Clear validation errors but disable submit
            amounts.forEach(input => input.setCustomValidity(''));
        }
    }
    
    // Initialize shared allocation validation
    document.querySelectorAll('.shared-allocation-amount').forEach(input => {
        input.addEventListener('input', updateTotalSharedAllocations);
    });

    const sharedAllocationsExtras = document.getElementById('sharedAllocationsExtras');
    const addSharedAllocationStudent = document.getElementById('addSharedAllocationStudent');
    let sharedAllocationsIndex = {{ count($sharedInfo['shared_allocations']) }};

    function addSharedAllocationRow() {
        const index = sharedAllocationsIndex++;
        const wrapperId = `shared_allocation_wrapper_${index}`;
        const hiddenId = `shared_allocation_student_id_${index}`;
        const displayId = `shared_allocation_student_name_${index}`;
        const resultsId = `shared_allocation_results_${index}`;
        
        const row = document.createElement('div');
        row.className = 'mb-3 p-3 border rounded shared-allocation-item';
        row.innerHTML = `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong>New Student</strong><br>
                    <small class="text-muted">Select student to include</small>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm remove-shared-allocation">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="student-live-search student-live-search-wrapper"
                 data-hidden="${hiddenId}"
                 data-display="${displayId}"
                 data-results="${resultsId}"
                 data-include-alumni-archived="0">
                <input type="hidden" id="${hiddenId}" name="allocations[${index}][student_id]" value="">
                <input type="text" id="${displayId}" class="form-control" placeholder="Type name or admission #">
                <div id="${resultsId}" class="list-group shadow-sm mt-1 d-none" style="max-height: 220px; overflow-y: auto;"></div>
                <small class="text-muted">Start typing; results appear below automatically.</small>
            </div>
            <div class="input-group mt-2">
                <span class="input-group-text">Ksh</span>
                <input type="number"
                       step="0.01"
                       min="0"
                       class="form-control shared-allocation-amount"
                       name="allocations[${index}][amount]"
                       value="0"
                       oninput="updateTotalSharedAllocations()"
                       required>
            </div>
        `;
        
        sharedAllocationsExtras.appendChild(row);
        if (window.initLiveSearchWrapper) {
            initLiveSearchWrapper(row.querySelector('.student-live-search-wrapper'));
        }
        row.querySelector('.shared-allocation-amount')?.addEventListener('input', updateTotalSharedAllocations);
        row.querySelector('.remove-shared-allocation')?.addEventListener('click', function () {
            row.remove();
            updateTotalSharedAllocations();
        });
    }

    addSharedAllocationStudent?.addEventListener('click', function () {
        addSharedAllocationRow();
    });
    
    // Form validation on submit
    const sharedAllocationsForm = document.getElementById('editSharedAllocationsForm');
    if (sharedAllocationsForm) {
        sharedAllocationsForm.addEventListener('submit', function(e) {
            const studentInputs = document.querySelectorAll('#editSharedAllocationsForm input[name^="allocations"][name$="[student_id]"]');
            const emptyStudent = Array.from(studentInputs).some(input => !input.value);
            if (emptyStudent) {
                e.preventDefault();
                alert('Please select a valid student for all shared allocation rows.');
                return false;
            }

            const amounts = document.querySelectorAll('.shared-allocation-amount');
            const totalAmount = {{ $sharedInfo['total_amount'] }};
            let total = 0;
            
            amounts.forEach(input => {
                total += parseFloat(input.value) || 0;
            });
            
            const tolerance = 0.01;
            if (Math.abs(total - totalAmount) > tolerance) {
                e.preventDefault();
                alert(`Total shared amounts (Ksh ${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}) must equal exactly the payment amount of Ksh ${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`);
                return false;
            }
        });
    }
    @endif
});
</script>
@endpush

@include('communication.partials.document-send-modal')

@endsection
