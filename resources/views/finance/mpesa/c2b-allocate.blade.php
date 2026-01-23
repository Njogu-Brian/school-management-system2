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
                                            @if(!empty($suggestion['classroom_name']))
                                                <br><small class="text-muted">Class: {{ $suggestion['classroom_name'] }}</small>
                                            @endif
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

                        <!-- Step 2: Swimming Transaction -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_swimming_transaction" 
                                       id="isSwimmingTransaction" value="1" 
                                       {{ $transaction->is_swimming_transaction ? 'checked' : '' }}>
                                <label class="form-check-label" for="isSwimmingTransaction">
                                    <strong>Swimming Payment</strong>
                                    <small class="text-muted d-block">If checked, payment will be allocated to swimming wallet balances instead of invoices</small>
                                </label>
                            </div>
                        </div>

                        <!-- Step 3: Payment Amount -->
                        <div class="mb-4">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">3</span>
                                Payment Amount <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="amount" id="paymentAmount" class="finance-form-control" 
                                   value="{{ $transaction->trans_amount }}" step="0.01" min="0.01" 
                                   max="{{ $transaction->trans_amount }}" required>
                            <small class="text-muted">Transaction amount: KES {{ number_format($transaction->trans_amount, 2) }}</small>
                        </div>

                        <!-- Step 4: Multi-Student Allocation (for siblings) -->
                        <div class="mb-4" id="multiStudentSection" style="display: none;">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">4</span>
                                Allocate to Multiple Students (Siblings)
                            </label>
                            <div id="siblingsList" class="border rounded p-3">
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-info-circle"></i> Select a student to see their siblings
                                </div>
                            </div>
                            <div class="mt-3">
                                <strong>Total Allocated:</strong> 
                                <span id="totalSiblingAllocated" class="text-primary fs-5">KES 0.00</span>
                                <span id="remainingSiblingAmount" class="text-muted ms-2"></span>
                            </div>
                        </div>

                        <!-- Hidden fields -->
                        <input type="hidden" name="payment_method" value="mpesa">

                        <!-- Step 5: Select Invoices to Allocate (only for non-swimming) -->
                        <div class="mb-4" id="invoiceAllocationSection" style="display: none;">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">5</span>
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
let siblingAllocations = [];
let isSwimming = {{ $transaction->is_swimming_transaction ? 'true' : 'false' }};

$(document).ready(function() {
    // Watch for swimming checkbox
    $('#isSwimmingTransaction').on('change', function() {
        isSwimming = $(this).is(':checked');
        if (isSwimming) {
            $('#invoiceAllocationSection').hide();
            $('#multiStudentSection').show();
            // If student is already selected, load siblings
            if (selectedStudent) {
                loadSiblings(selectedStudent);
            }
        } else {
            $('#multiStudentSection').hide();
            if (selectedStudent) {
                loadStudentInvoices(selectedStudent.id);
            }
        }
        updateSubmitButton();
    });
    
    // Initialize swimming section visibility
    if (isSwimming) {
        $('#multiStudentSection').show();
        $('#invoiceAllocationSection').hide();
    }
    
    // Watch for student selection
    $(document).on('studentSelected', function(e, student) {
        if (student && student.id) {
            selectedStudent = student;
            if (isSwimming) {
                loadSiblings(student);
            } else {
                loadStudentInvoices(student.id);
            }
            updateSubmitButton();
        }
    });
    
    // Watch for payment amount changes
    $('#paymentAmount').on('input change', function() {
        updateSubmitButton();
        if ($('#invoiceAllocationSection').is(':visible')) {
            updateAllocations();
        }
        if ($('#multiStudentSection').is(':visible')) {
            updateSiblingAllocations();
        }
    });

    // Pre-select if transaction already has a student
    @if($transaction->student_id)
        @if($transaction->is_swimming_transaction)
            // For swimming, we need to load siblings
            $.get('/api/students/{{ $transaction->student_id }}', function(student) {
                if (student) {
                    loadSiblings(student);
                }
            });
        @else
            loadStudentInvoices({{ $transaction->student_id }});
        @endif
    @endif
    
    // Initial button state check
    updateSubmitButton();
});

function selectSuggestion(suggestion) {
    // Simulate student selection
    $('#student_id').val(suggestion.student_id);
    const classDisplay = suggestion.classroom_name ? ` - ${suggestion.classroom_name}` : '';
    $('#studentSearchDisplay').val(suggestion.student_name + ' (' + suggestion.admission_number + ')' + classDisplay);
    
    // If suggestion has siblings, show multi-student section
    if (suggestion.siblings && suggestion.siblings.length > 0 && isSwimming) {
        loadSiblingsFromSuggestion(suggestion);
    } else if (isSwimming) {
        // Load siblings for selected student
        $.get('/api/students/' + suggestion.student_id, function(student) {
            if (student) {
                loadSiblings(student);
            }
        });
    } else {
        loadStudentInvoices(suggestion.student_id);
    }
}

function loadSiblingsFromSuggestion(suggestion) {
    // Get all sibling IDs from suggestion
    const siblingIds = suggestion.siblings || [];
    siblingIds.push(suggestion.student_id); // Include the selected student
    
    // Load all sibling details
    Promise.all(siblingIds.map(id => 
        fetch('/api/students/' + id).then(r => r.json())
    )).then(students => {
        displaySiblings(students, suggestion.student_id);
    });
}

function loadSiblings(student) {
    if (!student.family_id) {
        $('#siblingsList').html(`
            <div class="text-center text-muted py-3">
                <i class="bi bi-info-circle"></i> No siblings found for this student
            </div>
        `);
        return;
    }
    
    $('#siblingsList').html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading siblings...</div>');
    
    // Search for any student with the same family_id to get all siblings
    // We'll search by a unique identifier and then filter by family_id
    $.get('/api/students/search?q=' + encodeURIComponent(student.admission_number) + '&include_alumni_archived=0', function(results) {
        // Find the student's family members
        let familyMembers = results.filter(s => s.family_id === student.family_id && s.id !== student.id);
        
        // If we found the student but no siblings in results, try to get the student first to ensure we have family_id
        if (familyMembers.length === 0 && results.length > 0) {
            // Get full student data to ensure we have family_id
            $.get('/api/students/' + student.id, function(fullStudent) {
                if (fullStudent.family_id) {
                    // Search again with a broader query to find siblings
                    $.get('/api/students/search?q=' + encodeURIComponent(fullStudent.admission_number.substring(0, 3)) + '&include_alumni_archived=0', function(allResults) {
                        familyMembers = allResults.filter(s => s.family_id === fullStudent.family_id && s.id !== fullStudent.id);
                        // Include the selected student in the list
                        familyMembers.unshift(fullStudent);
                        displaySiblings(familyMembers, fullStudent.id);
                    });
                } else {
                    $('#siblingsList').html(`
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-info-circle"></i> No siblings found for this student
                        </div>
                    `);
                }
            });
        } else {
            // Include the selected student in the list
            familyMembers.unshift(student);
            displaySiblings(familyMembers, student.id);
        }
    });
}

function displaySiblings(siblings, selectedStudentId) {
    if (siblings.length === 0) {
        // No siblings found, but still allow single student allocation
        $('#siblingsList').html(`
            <div class="text-center text-muted py-3">
                <i class="bi bi-info-circle"></i> No siblings found. Payment will be allocated to selected student only.
            </div>
        `);
        // Set allocation to selected student with full amount
        const remainingAmount = parseFloat($('#paymentAmount').val());
        siblingAllocations = [{
            student_id: selectedStudentId,
            amount: remainingAmount
        }];
        updateSiblingAllocations();
        return;
    }
    
    let html = '<div class="list-group list-group-flush">';
    const remainingAmount = parseFloat($('#paymentAmount').val());
    const amountPerSibling = remainingAmount / siblings.length;
    
    siblingAllocations = [];
    
    siblings.forEach(function(sibling) {
        const suggestedAmount = amountPerSibling;
        siblingAllocations.push({
            student_id: sibling.id,
            amount: suggestedAmount
        });
        
        const classDisplay = sibling.classroom_name ? ` - ${sibling.classroom_name}` : '';
        html += `
            <div class="list-group-item px-0">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <strong>${sibling.full_name}</strong>
                        <br><small class="text-muted">Admission: ${sibling.admission_number}${classDisplay}</small>
                    </div>
                    <div class="text-end" style="width: 180px;">
                        <input type="number" 
                               class="form-control form-control-sm sibling-allocation-input" 
                               data-student-id="${sibling.id}"
                               value="${suggestedAmount.toFixed(2)}"
                               step="0.01"
                               min="0"
                               placeholder="0.00">
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    $('#siblingsList').html(html);
    
    // Bind input events
    $('.sibling-allocation-input').on('input', updateSiblingAllocations);
    
    // Calculate initial totals
    updateSiblingAllocations();
}

function updateSiblingAllocations() {
    // If no input fields exist (single student, no siblings), keep existing allocations
    if ($('.sibling-allocation-input').length === 0) {
        // Check if we have allocations already set (from displaySiblings when no siblings)
        if (siblingAllocations.length > 0) {
            let total = siblingAllocations.reduce((sum, a) => sum + a.amount, 0);
            $('#totalSiblingAllocated').text('KES ' + total.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
            let remaining = transactionAmount - total;
            if (Math.abs(remaining) < 0.01) {
                $('#remainingSiblingAmount').html('<span class="text-success">(Fully allocated)</span>');
            }
            updateSubmitButton();
        }
        return;
    }
    
    siblingAllocations = [];
    let total = 0;
    
    $('.sibling-allocation-input').each(function() {
        let amount = parseFloat($(this).val()) || 0;
        let studentId = $(this).data('student-id');
        
        if (amount > 0) {
            siblingAllocations.push({
                student_id: studentId,
                amount: amount
            });
            total += amount;
        }
    });
    
    $('#totalSiblingAllocated').text('KES ' + total.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    
    let remaining = transactionAmount - total;
    if (remaining > 0.01) {
        $('#remainingSiblingAmount').html('<span class="text-warning">(Remaining: KES ' + remaining.toFixed(2) + ')</span>');
    } else if (remaining < -0.01) {
        $('#remainingSiblingAmount').html('<span class="text-danger">(Over by: KES ' + Math.abs(remaining).toFixed(2) + ')</span>');
    } else {
        $('#remainingSiblingAmount').html('<span class="text-success">(Fully allocated)</span>');
    }
    
    updateSubmitButton();
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
    let hasStudent = studentId && studentId.length > 0;
    let hasValidAmount = paymentAmount > 0;
    let hasValidAllocations = false;
    
    if (isSwimming) {
        // For swimming transactions, check sibling allocations
        let totalSiblingAllocated = siblingAllocations.reduce((sum, a) => sum + a.amount, 0);
        let remaining = paymentAmount - totalSiblingAllocated;
        
        // For swimming, we need at least one student with amount > 0
        // If no siblings found, allow single student allocation (will be handled in displaySiblings)
        if ($('#multiStudentSection').is(':visible')) {
            hasValidAllocations = siblingAllocations.length > 0 && 
                                  siblingAllocations.some(a => a.amount > 0) &&
                                  Math.abs(remaining) < 0.01; // Must fully allocate
        } else {
            // Section not visible yet, but student is selected - allow if student exists
            hasValidAllocations = hasStudent;
        }
    } else {
        // For regular transactions, check invoice allocations
        let totalAllocated = allocations.reduce((sum, a) => sum + a.amount, 0);
        let remaining = paymentAmount - totalAllocated;
        
        // Check if invoice section is visible
        if ($('#invoiceAllocationSection').is(':visible')) {
            // If invoices exist, allocations must match payment amount (within 0.01 tolerance)
            hasValidAllocations = Math.abs(remaining) < 0.01 && allocations.length > 0;
        } else {
            // If no invoices section (no unpaid invoices), allow submission
            hasValidAllocations = true;
        }
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
    
    if (isSwimming) {
        // For swimming transactions, validate sibling allocations
        if (siblingAllocations.length === 0) {
            alert('Please allocate amount to at least one student.');
            return false;
        }
        
        // Add sibling allocations to form
        siblingAllocations.forEach(function(allocation, index) {
            $('<input>').attr({
                type: 'hidden',
                name: `sibling_allocations[${index}][student_id]`,
                value: allocation.student_id
            }).appendTo('#allocationForm');
            
            $('<input>').attr({
                type: 'hidden',
                name: `sibling_allocations[${index}][amount]`,
                value: allocation.amount
            }).appendTo('#allocationForm');
        });
    } else {
        // For regular transactions, validate invoice allocations
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
    }
    
    // Disable button and submit
    $('#submitBtn').prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
    this.submit();
});
</script>
@endsection

