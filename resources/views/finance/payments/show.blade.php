@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Payment #' . ($payment->receipt_number ?? $payment->transaction_code),
        'icon' => 'bi bi-cash-stack',
        'subtitle' => $payment->student?->first_name ? 'For ' . $payment->student->first_name . ' ' . ($payment->student->last_name ?? '') : 'Payment details',
        'actions' => '<a href="' . route('finance.payments.receipt.view', $payment) . '" class="btn btn-finance btn-finance-primary" onclick="window.open(\'' . route('finance.payments.receipt.view', $payment) . '\', \'ReceiptWindow\', \'width=800,height=900,scrollbars=yes,resizable=yes,toolbar=no,menubar=no,location=no,status=no\'); return false;"><i class="bi bi-printer"></i> View/Print Receipt</a><button type="button" class="btn btn-finance btn-finance-secondary" onclick="openSendDocument(\'receipt\', [' . $payment->id . '], {channel:\'sms\', message:\'Your receipt is ready. Please find the link below.\'})"><i class="bi bi-send"></i> Send Now</button><a href="' . route('finance.payments.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    @include('finance.invoices.partials.alerts')

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
                                    {{ $payment->student->first_name ?? 'N/A' }} {{ $payment->student->last_name ?? '' }}
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
                                    <strong class="text-success">Ksh {{ number_format($payment->allocated_amount ?? 0, 2) }}</strong>
                                </dd>

                                <dt class="col-sm-5">Unallocated Amount:</dt>
                                <dd class="col-sm-7">
                                    <strong class="text-warning">Ksh {{ number_format($payment->unallocated_amount ?? $payment->amount - ($payment->allocated_amount ?? 0), 2) }}</strong>
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
                    @if($payment->unallocated_amount > 0)
                    <button type="button" class="btn btn-sm btn-finance btn-finance-primary" data-bs-toggle="modal" data-bs-target="#allocateModal">
                        <i class="bi bi-plus-circle"></i> Allocate Payment
                    </button>
                    @endif
                </div>
                <div class="finance-card-body p-0">
                    @php
                        $allocations = $payment->allocations ?? collect();
                        $invoiceItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($payment) {
                            $q->where('student_id', $payment->student_id);
                        })->where('status', 'active')->get();
                    @endphp
                    
                    @if($allocations->isNotEmpty())
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
                        @if($payment->unallocated_amount > 0)
                        <button type="button" class="btn btn-finance btn-finance-primary" data-bs-toggle="modal" data-bs-target="#allocateModal">
                            <i class="bi bi-plus-circle"></i> Allocate Payment
                        </button>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
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
                        @if($payment->student_id)
                        <a href="{{ route('finance.invoices.index', ['student_id' => $payment->student_id]) }}" class="btn btn-finance btn-finance-outline">
                            <i class="bi bi-file-text"></i> View Student Invoices
                        </a>
                        @endif
                        @if(!$payment->reversed)
                        <button type="button" class="btn btn-finance btn-finance-secondary w-100 mb-2" data-bs-toggle="modal" data-bs-target="#transferPaymentModal">
                            <i class="bi bi-arrow-left-right"></i> Transfer/Share Payment
                        </button>
                        <form action="{{ route('finance.payments.reverse', $payment) }}" method="POST" onsubmit="return confirm('Are you sure you want to reverse this payment? This will remove all allocations and recalculate invoices. This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="bi bi-arrow-counterclockwise"></i> Reverse Payment
                            </button>
                        </form>
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
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <small class="finance-muted d-block">Total Paid</small>
                            <span class="h5 text-primary mb-0">Ksh {{ number_format($payment->amount, 2) }}</span>
                        </div>
                        <span class="finance-badge badge-paid">Paid</span>
                    </div>
                    <div class="mb-2">
                        <strong>Allocated:</strong><br>
                        <span class="h6 text-success">Ksh {{ number_format($payment->allocated_amount ?? 0, 2) }}</span>
                    </div>
                    <div class="mb-0">
                        <strong>Unallocated:</strong><br>
                        <span class="h6 text-warning">Ksh {{ number_format($payment->unallocated_amount ?? $payment->amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>

<!-- Allocation Modal -->
@if($payment->unallocated_amount > 0)
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
                        Available to allocate: <strong>Ksh {{ number_format($payment->unallocated_amount ?? $payment->amount, 2) }}</strong>
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
                        <strong>Available to transfer:</strong> Ksh {{ number_format($payment->unallocated_amount, 2) }}
                        @if($payment->unallocated_amount <= 0)
                            <div class="small text-warning mt-1">Note: currently no unallocated amount; adjust manually if needed.</div>
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
                                <input type="number" step="0.01" min="0.01" max="{{ $payment->unallocated_amount }}" name="transfer_amount" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div id="transferMultipleStudents" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Students to Share With <span class="text-danger">*</span></label>
                            <div id="sharedStudentsList">
                                <div class="shared-student-item mb-2">
                                    @include('partials.student_live_search', [
                                        'hiddenInputId' => 'shared_student_id_0',
                                        'hiddenInputName' => 'shared_students[]',
                                        'displayInputId' => 'shared_student_name_0',
                                        'resultsId' => 'shared_student_results_0',
                                        'placeholder' => 'Type name or admission #',
                                        'inputClass' => 'form-control shared-student-name'
                                    ])
                                    <div class="input-group mt-2">
                                        <span class="input-group-text">Ksh</span>
                                        <input type="number" step="0.01" min="0.01" name="shared_amounts[]" class="form-control shared-amount" placeholder="Amount">
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-student-btn">
                                            <i class="bi bi-x"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="addSharedStudent">
                                <i class="bi bi-plus"></i> Add Student
                            </button>
                            <div class="mt-2">
                                <strong>Total Allocated: <span id="totalSharedAmount">Ksh 0.00</span></strong>
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
    const availableAmount = {{ $payment->unallocated_amount ?? $payment->amount }};
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
    
    if (transferType) {
        transferType.addEventListener('change', function() {
            if (this.value === 'transfer') {
                transferSingle.style.display = 'block';
                transferMultiple.style.display = 'none';
            } else if (this.value === 'share') {
                transferSingle.style.display = 'none';
                transferMultiple.style.display = 'block';
            } else {
                transferSingle.style.display = 'none';
                transferMultiple.style.display = 'none';
            }
        });
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
                a.addEventListener('click', (e) => {
                    e.preventDefault();
                    displayInput.value = `${item.full_name} (${item.admission_number})`;
                    hiddenInput.value = item.id;
                    resultsList.classList.add('d-none');
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
    
    // Add shared student (clones the first item's markup)
    const addSharedStudentBtn = document.getElementById('addSharedStudent');
    if (addSharedStudentBtn) {
        let sharedIndex = 1;
        const list = document.getElementById('sharedStudentsList');
        const template = list.querySelector('.shared-student-item');
        addSharedStudentBtn.addEventListener('click', function() {
            const clone = template.cloneNode(true);
            const newIndex = sharedIndex++;
            // Update IDs to keep them unique
            clone.querySelectorAll('[id]').forEach(el => {
                el.id = el.id.replace('_0', `_${newIndex}`);
            });
            clone.querySelectorAll('[name="shared_students[]"]').forEach(el => el.value = '');
            clone.querySelectorAll('[name="shared_amounts[]"]').forEach(el => el.value = '');
            list.appendChild(clone);
            const wrapper = clone.querySelector('.student-live-search-wrapper');
            initLiveSearchWrapper(wrapper);
            clone.querySelector('.remove-student-btn')?.addEventListener('click', function() {
                clone.remove();
                updateTotalShared();
            });
            clone.querySelector('.shared-amount')?.addEventListener('input', updateTotalShared);
        });
    }
    
    // Update total shared amount
    function updateTotalShared() {
        const amounts = document.querySelectorAll('.shared-amount');
        let total = 0;
        amounts.forEach(input => {
            total += parseFloat(input.value) || 0;
        });
        document.getElementById('totalSharedAmount').textContent = `Ksh ${total.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }
    
    // Remove student functionality
    document.addEventListener('click', function(e) {
        if (e.target.closest('.remove-student-btn')) {
            e.target.closest('.shared-student-item').remove();
            updateTotalShared();
        }
    });
});
</script>
@endpush
@endsection
