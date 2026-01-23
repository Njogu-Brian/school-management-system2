@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Allocate M-PESA Transaction',
        'icon' => 'bi bi-arrow-left-right',
        'subtitle' => 'Assign transaction to student and invoices',
        'actions' => '<a href="' . route('finance.mpesa.c2b.dashboard') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>'
    ])

    <div class="row g-4">
        <!-- Left Column: Transaction Details -->
        <div class="col-lg-5">
            <!-- Transaction Info -->
            <div class="finance-card finance-animate mb-4">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-receipt me-2"></i>
                        Transaction Details
                    </h5>
                </div>
                <div class="finance-card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span class="text-muted">Amount:</span>
                                <strong class="text-success fs-4">KES {{ number_format($transaction->trans_amount, 2) }}</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span class="text-muted">M-PESA Code:</span>
                                <strong><code>{{ $transaction->trans_id }}</code></strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span class="text-muted">Date & Time:</span>
                                <strong>{{ $transaction->trans_time->format('d M Y, H:i:s') }}</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span class="text-muted">Payer Name:</span>
                                <strong>{{ $transaction->full_name }}</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span class="text-muted">Phone:</span>
                                <strong>{{ $transaction->formatted_phone }}</strong>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                <span class="text-muted">Reference:</span>
                                <strong>{{ $transaction->bill_ref_number ?? '-' }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Smart Matching Suggestions -->
            @if(!empty($transaction->matching_suggestions) && count($transaction->matching_suggestions) > 0)
                <div class="finance-card finance-animate">
                    <div class="finance-card-header">
                        <h5 class="finance-card-title">
                            <i class="bi bi-lightbulb me-2"></i>
                            Smart Match Suggestions
                        </h5>
                    </div>
                    <div class="finance-card-body">
                        <div class="list-group list-group-flush">
                            @foreach($transaction->matching_suggestions as $suggestion)
                                <div class="list-group-item px-0 cursor-pointer" onclick="selectSuggestion({{ json_encode($suggestion) }})">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1">{{ $suggestion['student_name'] }}</h6>
                                            <small class="text-muted">Admission: {{ $suggestion['admission_number'] }}</small>
                                            <br>
                                            <small class="text-muted">{{ $suggestion['reason'] }}</small>
                                        </div>
                                        <div class="text-end">
                                            @if($suggestion['confidence'] >= 80)
                                                <span class="badge bg-success">{{ $suggestion['confidence'] }}%</span>
                                            @elseif($suggestion['confidence'] >= 60)
                                                <span class="badge bg-warning">{{ $suggestion['confidence'] }}%</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $suggestion['confidence'] }}%</span>
                                            @endif
                                            <br>
                                            <button type="button" class="btn btn-sm btn-finance btn-finance-primary mt-2">
                                                <i class="bi bi-check"></i> Select
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column: Allocation Form -->
        <div class="col-lg-7">
            <div class="finance-card finance-animate">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-pencil-square me-2"></i>
                        Allocate to Student & Invoices
                    </h5>
                </div>
                <div class="finance-card-body">
                    <form action="{{ route('finance.mpesa.c2b.allocate', $transaction->id) }}" method="POST" id="allocationForm">
                        @csrf

                        <!-- Step 1: Select Student -->
                        <div class="mb-4">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">1</span>
                                Select Student <span class="text-danger">*</span>
                            </label>
                            @include('partials.student_live_search', [
                                'hiddenInputId' => 'student_id',
                                'displayInputId' => 'studentSearchDisplay',
                                'resultsId' => 'studentSearchResults',
                                'placeholder' => 'Search by name or admission number',
                                'initialLabel' => $transaction->student ? $transaction->student->full_name . ' (' . $transaction->student->admission_number . ')' : ''
                            ])
                            @error('student_id')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Step 2: Payment Amount -->
                        <div class="mb-4">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">2</span>
                                Payment Amount <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="amount" id="paymentAmount" class="finance-form-control" 
                                   value="{{ $transaction->trans_amount }}" step="0.01" min="0.01" 
                                   max="{{ $transaction->trans_amount }}" required>
                            <small class="text-muted">Transaction amount: KES {{ number_format($transaction->trans_amount, 2) }}</small>
                        </div>

                        <!-- Hidden fields -->
                        <input type="hidden" name="payment_method" value="mpesa">

                        <!-- Step 3: Select Invoices to Allocate -->
                        <div class="mb-4" id="invoiceAllocationSection" style="display: none;">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">3</span>
                                Allocate to Invoices
                            </label>
                            <div id="invoicesList" class="border rounded p-3">
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-hourglass-split"></i> Select a student first
                                </div>
                            </div>
                            <div class="mt-3">
                                <strong>Total Allocated:</strong> 
                                <span id="totalAllocated" class="text-primary fs-5">KES 0.00</span>
                                <span id="remainingAmount" class="text-muted ms-2"></span>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="finance-card-footer">
                            <button type="submit" class="btn btn-finance btn-finance-primary btn-lg" id="submitBtn" disabled>
                                <i class="bi bi-check-circle"></i> Complete Allocation
                            </button>
                            <a href="{{ route('finance.mpesa.c2b.dashboard') }}" class="btn btn-finance btn-finance-outline">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
let selectedStudent = null;
let transactionAmount = {{ $transaction->trans_amount }};
let allocations = [];

$(document).ready(function() {
    // Watch for student selection
    $(document).on('studentSelected', function(e, student) {
        if (student && student.id) {
            loadStudentInvoices(student.id);
            selectedStudent = student;
            updateSubmitButton();
        }
    });
    
    // Watch for payment amount changes
    $('#paymentAmount').on('input change', function() {
        updateSubmitButton();
        if ($('#invoiceAllocationSection').is(':visible')) {
            updateAllocations();
        }
    });

    // Pre-select if transaction already has a student
    @if($transaction->student_id)
        loadStudentInvoices({{ $transaction->student_id }});
    @endif
    
    // Initial button state check
    updateSubmitButton();
});

function selectSuggestion(suggestion) {
    // Simulate student selection
    $('#student_id').val(suggestion.student_id);
    $('#studentSearchDisplay').val(suggestion.student_name + ' (' + suggestion.admission_number + ')');
    
    // Trigger load
    loadStudentInvoices(suggestion.student_id);
}

function loadStudentInvoices(studentId) {
    $('#invoiceAllocationSection').show();
    $('#invoicesList').html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading invoices...</div>');
    
    $.get('/api/students/' + studentId + '/invoices', function(invoices) {
        let unpaidInvoices = invoices.filter(inv => inv.balance > 0);
        
        if (unpaidInvoices.length === 0) {
            $('#invoicesList').html(`
                <div class="text-center text-warning py-3">
                    <i class="bi bi-exclamation-triangle fs-2"></i>
                    <p class="mb-0 mt-2">No outstanding invoices for this student</p>
                    <small>The payment will be recorded as advance payment</small>
                </div>
            `);
            allocations = []; // No allocations needed for advance payment
            updateSubmitButton();
            return;
        }

        // Auto-allocate to invoices
        let remainingAmount = parseFloat($('#paymentAmount').val());
        allocations = [];
        
        let html = '<div class="list-group list-group-flush">';
        unpaidInvoices.forEach(function(invoice) {
            let suggestedAmount = Math.min(remainingAmount, invoice.balance);
            
            if (suggestedAmount > 0) {
                allocations.push({
                    invoice_id: invoice.id,
                    amount: suggestedAmount
                });
                remainingAmount -= suggestedAmount;
            }
            
            html += `
                <div class="list-group-item px-0">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <strong>${invoice.invoice_number}</strong>
                            <br><small class="text-muted">Balance: KES ${parseFloat(invoice.balance).toLocaleString('en-KE', {minimumFractionDigits: 2})}</small>
                            ${invoice.due_date ? '<br><small class="text-muted">Due: ' + invoice.due_date + '</small>' : ''}
                        </div>
                        <div class="text-end" style="width: 180px;">
                            <input type="number" 
                                   class="form-control form-control-sm allocation-input" 
                                   data-invoice-id="${invoice.id}"
                                   data-max="${invoice.balance}"
                                   value="${suggestedAmount.toFixed(2)}"
                                   step="0.01"
                                   min="0"
                                   max="${invoice.balance}"
                                   placeholder="0.00">
                        </div>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        $('#invoicesList').html(html);
        
        // Bind input events
        $('.allocation-input').on('input', updateAllocations);
        
        // Calculate initial totals and update button state
        updateAllocations();
    });
}

function updateAllocations() {
    allocations = [];
    let total = 0;
    
    $('.allocation-input').each(function() {
        let amount = parseFloat($(this).val()) || 0;
        let invoiceId = $(this).data('invoice-id');
        let max = parseFloat($(this).data('max'));
        
        // Enforce max
        if (amount > max) {
            amount = max;
            $(this).val(amount.toFixed(2));
        }
        
        if (amount > 0) {
            allocations.push({
                invoice_id: invoiceId,
                amount: amount
            });
            total += amount;
        }
    });
    
    $('#totalAllocated').text('KES ' + total.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    
    let remaining = transactionAmount - total;
    if (remaining > 0.01) {
        $('#remainingAmount').html('<span class="text-warning">(Remaining: KES ' + remaining.toFixed(2) + ')</span>');
    } else if (remaining < -0.01) {
        $('#remainingAmount').html('<span class="text-danger">(Over by: KES ' + Math.abs(remaining).toFixed(2) + ')</span>');
    } else {
        $('#remainingAmount').html('<span class="text-success">(Fully allocated)</span>');
    }
    
    // Enable/disable submit button based on valid allocations
    updateSubmitButton();
}

function updateSubmitButton() {
    let studentId = $('#student_id').val();
    let paymentAmount = parseFloat($('#paymentAmount').val()) || 0;
    let totalAllocated = allocations.reduce((sum, a) => sum + a.amount, 0);
    let remaining = paymentAmount - totalAllocated;
    
    // Button is enabled when:
    // 1. Student is selected
    // 2. Payment amount is valid (> 0)
    // 3. Allocations are valid (total matches payment amount, or no invoices exist)
    let hasStudent = studentId && studentId.length > 0;
    let hasValidAmount = paymentAmount > 0;
    let hasValidAllocations = false;
    
    // Check if invoice section is visible
    if ($('#invoiceAllocationSection').is(':visible')) {
        // If invoices exist, allocations must match payment amount (within 0.01 tolerance)
        hasValidAllocations = Math.abs(remaining) < 0.01 && allocations.length > 0;
    } else {
        // If no invoices section (no unpaid invoices), allow submission
        hasValidAllocations = true;
    }
    
    if (hasStudent && hasValidAmount && hasValidAllocations) {
        $('#submitBtn').prop('disabled', false).removeClass('btn-secondary').addClass('btn-primary');
    } else {
        $('#submitBtn').prop('disabled', true).removeClass('btn-primary').addClass('btn-secondary');
    }
}

// Form submission
$('#allocationForm').on('submit', function(e) {
    e.preventDefault();
    
    if (allocations.length === 0) {
        alert('Please allocate amount to at least one invoice.');
        return false;
    }
    
    // Add allocations to form
    allocations.forEach(function(allocation, index) {
        $('<input>').attr({
            type: 'hidden',
            name: `allocations[${index}][invoice_id]`,
            value: allocation.invoice_id
        }).appendTo('#allocationForm');
        
        $('<input>').attr({
            type: 'hidden',
            name: `allocations[${index}][amount]`,
            value: allocation.amount
        }).appendTo('#allocationForm');
    });
    
    // Disable button and submit
    $('#submitBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
    this.submit();
});
</script>
@endsection

