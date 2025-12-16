@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-cash-stack"></i> Payment #{{ $payment->receipt_number ?? $payment->transaction_code }}
                </h3>
                <div>
                    <a href="{{ route('finance.payments.receipt', $payment) }}" class="btn btn-primary" target="_blank">
                        <i class="bi bi-printer"></i> Print Receipt
                    </a>
                    <a href="{{ route('finance.payments.index') }}" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Back
                    </a>
                </div>
            </div>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Payment Information</h5>
                </div>
                <div class="card-body">
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
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Payment Allocations</h5>
                    @if($payment->unallocated_amount > 0)
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#allocateModal">
                        <i class="bi bi-plus-circle"></i> Allocate Payment
                    </button>
                    @endif
                </div>
                <div class="card-body p-0">
                    @php
                        $allocations = $payment->allocations ?? collect();
                        $invoiceItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($payment) {
                            $q->where('student_id', $payment->student_id);
                        })->where('status', 'active')->get();
                    @endphp
                    
                    @if($allocations->isNotEmpty())
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
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
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#allocateModal">
                            <i class="bi bi-plus-circle"></i> Allocate Payment
                        </button>
                        @endif
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('finance.payments.receipt', $payment) }}" class="btn btn-outline-primary" target="_blank">
                            <i class="bi bi-download"></i> Download Receipt PDF
                        </a>
                        @if($payment->student_id)
                        <a href="{{ route('finance.invoices.index', ['student_id' => $payment->student_id]) }}" class="btn btn-outline-info">
                            <i class="bi bi-file-text"></i> View Student Invoices
                        </a>
                        @endif
                        @if(!$payment->reversed)
                        <form action="{{ route('finance.payments.reverse', $payment) }}" method="POST" onsubmit="return confirm('Are you sure you want to reverse this payment? This will remove all allocations and recalculate invoices. This action cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger w-100">
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

            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">Payment Summary</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Total Paid:</strong><br>
                        <span class="h5 text-primary">Ksh {{ number_format($payment->amount, 2) }}</span>
                    </p>
                    <hr>
                    <p class="mb-2">
                        <strong>Allocated:</strong><br>
                        <span class="h6 text-success">Ksh {{ number_format($payment->allocated_amount ?? 0, 2) }}</span>
                    </p>
                    <p class="mb-0">
                        <strong>Unallocated:</strong><br>
                        <span class="h6 text-warning">Ksh {{ number_format($payment->unallocated_amount ?? $payment->amount, 2) }}</span>
                    </p>
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
                    <button type="submit" class="btn btn-primary">Allocate Payment</button>
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
});
</script>
@endpush
@endsection
