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
                                <div class="list-group-item px-0 cursor-pointer suggestion-item" 
                                     data-suggestion='{{ json_encode($suggestion) }}'>
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
                                            <button type="button" class="btn btn-sm btn-finance btn-finance-primary mt-2 select-suggestion-btn">
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
                                'initialLabel' => $transaction->student ? $transaction->student->full_name . ' (' . $transaction->student->admission_number . ')' : '',
                                'initialStudentId' => $transaction->student_id
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
                            <button type="submit" class="btn btn-finance btn-finance-primary btn-lg" id="submitBtn">
                                <i class="bi bi-check-circle"></i> Complete Allocation
                            </button>
                            
                            <!-- Response Messages Container -->
                            <div id="responseMessages" class="mt-3"></div>
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
// Check initial state from checkbox (user may have checked it even if transaction wasn't originally swimming)
let isSwimming = $('#isSwimmingTransaction').is(':checked') || {{ $transaction->is_swimming_transaction ? 'true' : 'false' }};

$(document).ready(function() {
    // IMMEDIATE: Check if student is pre-selected and enable button right away
    const initialStudentId = $('#student_id').val();
    console.log('Page loaded - initial student_id from hidden input:', initialStudentId, 'Type:', typeof initialStudentId);
    
    // Button is always enabled - validation happens on submit
    
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
            // Ensure hidden input is set
            $('#student_id').val(student.id.toString());
            console.log('Student selected event - set student_id to:', student.id);
            
            if (isSwimming) {
                loadSiblings(student);
            } else {
                loadStudentInvoices(student.id);
            }
        }
    });
    
    // Watch for payment amount changes
    $('#paymentAmount').on('input change', function() {
        if ($('#invoiceAllocationSection').is(':visible')) {
            updateAllocations();
        }
        if ($('#multiStudentSection').is(':visible')) {
            updateSiblingAllocations();
        }
    });

    // Pre-select if transaction already has a student
    @if($transaction->student_id)
        // Set selectedStudent variable - use the transaction's student_id directly
        const preSelectedStudentId = {{ $transaction->student_id }};
        console.log('Pre-selected student ID:', preSelectedStudentId, 'isSwimming:', isSwimming);
        
        // Set hidden input
        $('#student_id').val(preSelectedStudentId.toString());
        console.log('Set student_id to:', $('#student_id').val());
        
        // Get full student data to set selectedStudent (for loading invoices/siblings)
        $.get('/api/students/' + preSelectedStudentId, function(student) {
            console.log('Loaded student data:', student);
            if (student) {
                selectedStudent = student;
                
                // Check current swimming state (from checkbox, not just transaction flag)
                const currentSwimmingState = $('#isSwimmingTransaction').is(':checked');
                console.log('Current swimming checkbox state:', currentSwimmingState);
                
                if (currentSwimmingState || isSwimming) {
                    // For swimming, ensure section is visible and load siblings
                    $('#multiStudentSection').show();
                    $('#invoiceAllocationSection').hide();
                    isSwimming = true; // Update the variable
                    loadSiblings(student);
                } else {
                    // For regular, load invoices
                    loadStudentInvoices(student.id);
                }
            }
        }).fail(function(xhr, status, error) {
            console.error('Failed to load student:', error);
        });
    @endif
    
    // Button is always enabled - no need for delayed checks
});

// Handle suggestion clicks using event delegation (more reliable than inline onclick)
$(document).on('click', '.suggestion-item, .select-suggestion-btn', function(e) {
    e.stopPropagation();
    const $item = $(this).closest('.suggestion-item');
    const suggestionData = $item.data('suggestion');
    
    if (suggestionData) {
        selectSuggestion(suggestionData);
    }
});

function selectSuggestion(suggestion) {
    console.log('selectSuggestion called with:', suggestion);
    
    // Simulate student selection
    $('#student_id').val(suggestion.student_id);
    const classDisplay = suggestion.classroom_name ? ` - ${suggestion.classroom_name}` : '';
    $('#studentSearchDisplay').val(suggestion.student_name + ' (' + suggestion.admission_number + ')' + classDisplay);
    
    // Update selectedStudent variable
    selectedStudent = {
        id: suggestion.student_id,
        full_name: suggestion.student_name,
        admission_number: suggestion.admission_number,
        classroom_name: suggestion.classroom_name
    };
    
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
    
    // Button is always enabled - validation happens on submit
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
                <i class="bi bi-info-circle"></i> No siblings found. Payment will be allocated to selected student only.
            </div>
        `);
        // Set allocation to selected student with full amount
        const remainingAmount = parseFloat($('#paymentAmount').val());
        siblingAllocations = [{
            student_id: student.id,
            amount: remainingAmount
        }];
        console.log('No family_id - setting single student allocation:', siblingAllocations);
        updateSiblingAllocations();
        return;
    }
    
    $('#siblingsList').html('<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Loading siblings...</div>');
    
    // Search for any student with the same family_id to get all siblings
    // We'll search by a unique identifier and then filter by family_id
    $.get('/api/students/search?q=' + encodeURIComponent(student.admission_number) + '&include_alumni_archived=0', function(results) {
        // Find the student's family members (excluding the selected student)
        let familyMembers = results.filter(s => s.family_id === student.family_id && s.id !== student.id);
        
        console.log('Sibling search results:', {
            totalResults: results.length,
            familyMembers: familyMembers.length,
            studentFamilyId: student.family_id
        });
        
        // If we found the student but no siblings in results, try to get the student first to ensure we have family_id
        if (familyMembers.length === 0 && results.length > 0) {
            // Get full student data to ensure we have family_id
            $.get('/api/students/' + student.id, function(fullStudent) {
                if (fullStudent.family_id) {
                    // Search again with a broader query to find siblings
                    $.get('/api/students/search?q=' + encodeURIComponent(fullStudent.admission_number.substring(0, 3)) + '&include_alumni_archived=0', function(allResults) {
                        familyMembers = allResults.filter(s => s.family_id === fullStudent.family_id && s.id !== fullStudent.id);
                        console.log('Broader search results:', {
                            totalResults: allResults.length,
                            familyMembers: familyMembers.length
                        });
                        
                        if (familyMembers.length > 0) {
                            // Found siblings - include the selected student in the list
                            familyMembers.unshift(fullStudent);
                            displaySiblings(familyMembers, fullStudent.id);
                        } else {
                            // No siblings found - single student allocation
                            $('#siblingsList').html(`
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-info-circle"></i> No siblings found. Payment will be allocated to selected student only.
                                </div>
                            `);
                            const remainingAmount = parseFloat($('#paymentAmount').val());
                            siblingAllocations = [{
                                student_id: fullStudent.id,
                                amount: remainingAmount
                            }];
                            console.log('No siblings after broader search - setting single student allocation:', siblingAllocations);
                            updateSiblingAllocations();
                        }
                    }).fail(function() {
                        // Search failed - fall back to single student
                        setupSingleStudentAllocation(student);
                    });
                } else {
                    // No family_id - single student allocation
                    setupSingleStudentAllocation(student);
                }
            }).fail(function() {
                // Failed to load student - fall back to single student
                setupSingleStudentAllocation(student);
            });
        } else if (familyMembers.length > 0) {
            // Found some family members - include the selected student in the list
            const selectedStudentInList = familyMembers.find(s => s.id === student.id);
            if (!selectedStudentInList) {
                familyMembers.unshift(student);
            }
            displaySiblings(familyMembers, student.id);
        } else {
            // No family members found at all - single student allocation
            setupSingleStudentAllocation(student);
        }
    }).fail(function() {
        // Search failed - fall back to single student
        setupSingleStudentAllocation(student);
    });
}

function setupSingleStudentAllocation(student) {
    $('#siblingsList').html(`
        <div class="text-center text-muted py-3">
            <i class="bi bi-info-circle"></i> No siblings found. Payment will be allocated to selected student only.
        </div>
    `);
    const remainingAmount = parseFloat($('#paymentAmount').val());
    siblingAllocations = [{
        student_id: student.id,
        amount: remainingAmount
    }];
    console.log('Setting up single student allocation:', siblingAllocations);
    updateSiblingAllocations();
}

function displaySiblings(siblings, selectedStudentId) {
    console.log('displaySiblings called:', { siblings: siblings, selectedStudentId: selectedStudentId });
    
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
        console.log('No siblings - setting single allocation:', siblingAllocations);
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
    console.log('Siblings displayed, initial allocations:', siblingAllocations);
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
            } else {
                $('#remainingSiblingAmount').html('<span class="text-warning">(Remaining: KES ' + remaining.toFixed(2) + ')</span>');
            }
            console.log('updateSiblingAllocations - no input fields, using existing allocations:', siblingAllocations);
        } else {
            console.warn('updateSiblingAllocations - no input fields and no allocations set!');
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
    
    // Button is always enabled - validation happens on submit
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
            
            console.log('Invoice allocation:', {
                invoice_id: invoice.id,
                suggestedAmount: suggestedAmount,
                remainingAmount: remainingAmount
            });
            
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
        console.log('Invoices loaded, allocations:', allocations);
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
    
    // Button is always enabled - validation happens on submit
}

// Button is always enabled - validation happens on form submit
// No need for updateSubmitButton() function anymore

// Form submission with AJAX and detailed responses
$('#allocationForm').on('submit', function(e) {
    e.preventDefault();
    
    const studentId = $('#student_id').val();
    const paymentAmount = parseFloat($('#paymentAmount').val()) || 0;
    
    // Clear previous messages
    $('#responseMessages').html('');
    
    // Validate required fields
    if (!studentId || studentId.toString().trim() === '' || studentId === '0') {
        showError('Please select a student before completing the allocation.');
        $('#studentSearchDisplay').focus();
        return false;
    }
    
    if (!paymentAmount || paymentAmount <= 0) {
        showError('Please enter a valid payment amount.');
        $('#paymentAmount').focus();
        return false;
    }
    
    // Prepare form data
    const formData = new FormData(this);
    
    // Add swimming flag
    formData.append('is_swimming_transaction', isSwimming ? '1' : '0');
    formData.append('payment_method', 'mpesa');
    
    if (isSwimming) {
        // For swimming transactions, set up allocations if not already set
        if (siblingAllocations.length === 0) {
            // No allocations set - create single student allocation
            if (studentId) {
                siblingAllocations = [{
                    student_id: studentId,
                    amount: paymentAmount
                }];
                console.log('Auto-creating single student allocation for swimming:', siblingAllocations);
            } else {
                showError('Please select a student.');
                return false;
            }
        }
        
        // Add sibling allocations to form data
        siblingAllocations.forEach(function(allocation, index) {
            formData.append(`sibling_allocations[${index}][student_id]`, allocation.student_id);
            formData.append(`sibling_allocations[${index}][amount]`, allocation.amount);
        });
    } else {
        // For regular transactions, add allocations to form data (if any)
        if (allocations.length > 0) {
            allocations.forEach(function(allocation, index) {
                formData.append(`allocations[${index}][invoice_id]`, allocation.invoice_id);
                formData.append(`allocations[${index}][amount]`, allocation.amount);
            });
        }
        // If no allocations, backend will treat as advance payment
    }
    
    // Disable button and show processing state
    const $submitBtn = $('#submitBtn');
    const originalBtnHtml = $submitBtn.html();
    $submitBtn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Processing...');
    
    // Submit via AJAX
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        },
        success: function(response) {
            if (response.success) {
                showSuccess(response);
            } else {
                showError(response.message || 'An error occurred');
            }
        },
        error: function(xhr) {
            let errorMessage = 'An unexpected error occurred. Please try again.';
            let errorDetails = [];
            
            if (xhr.status === 422) {
                // Validation errors
                const errors = xhr.responseJSON?.errors || {};
                errorMessage = 'Validation failed. Please check the following:';
                Object.keys(errors).forEach(function(key) {
                    errors[key].forEach(function(msg) {
                        errorDetails.push(`• ${key}: ${msg}`);
                    });
                });
            } else if (xhr.status === 500) {
                // Server error
                const error = xhr.responseJSON?.error || xhr.responseJSON?.message || 'Server error';
                errorMessage = 'Server error occurred';
                errorDetails.push(`Error: ${error}`);
            } else if (xhr.responseJSON) {
                errorMessage = xhr.responseJSON.message || errorMessage;
                if (xhr.responseJSON.error) {
                    errorDetails.push(xhr.responseJSON.error);
                }
            }
            
            showError(errorMessage, errorDetails);
        },
        complete: function() {
            // Re-enable button
            $submitBtn.prop('disabled', false).html(originalBtnHtml);
        }
    });
});

// Show success message with details
function showSuccess(response) {
    const details = response.details || {};
    let html = `
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">
                <i class="bi bi-check-circle-fill me-2"></i>
                ${response.message}
            </h5>
            <hr>
            <div class="mb-2">
                <strong>Transaction Details:</strong>
                <ul class="mb-0 mt-2">
                    <li><strong>Transaction Code:</strong> ${details.transaction_code || 'N/A'}</li>
                    <li><strong>Amount:</strong> KES ${details.amount || '0.00'}</li>
    `;
    
    if (details.is_swimming) {
        html += `
                    <li><strong>Type:</strong> Swimming Payment</li>
                    <li><strong>Students:</strong> ${details.students_count || 0}</li>
                    <li><strong>Receipt Number(s):</strong> ${(details.receipt_numbers || []).join(', ')}</li>
                </ul>
            </div>
        `;
        
        if (details.students && details.students.length > 0) {
            html += `
                <div class="mt-3">
                    <strong>Allocation Breakdown:</strong>
                    <table class="table table-sm table-bordered mt-2">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Admission</th>
                                <th class="text-end">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            details.students.forEach(function(student) {
                html += `
                    <tr>
                        <td>${student.name}</td>
                        <td>${student.admission_number}</td>
                        <td class="text-end">KES ${student.amount}</td>
                    </tr>
                `;
            });
            html += `
                        </tbody>
                    </table>
                </div>
            `;
        }
    } else {
        html += `
                    <li><strong>Type:</strong> Invoice Payment</li>
                    <li><strong>Student:</strong> ${details.student_name || 'N/A'} (${details.student_admission || 'N/A'})</li>
                    <li><strong>Receipt Number:</strong> ${details.receipt_number || 'N/A'}</li>
        `;
        
        if (details.is_advance_payment) {
            html += `<li><strong>Note:</strong> <span class="text-warning">Recorded as advance payment (no outstanding invoices)</span></li>`;
        } else {
            html += `<li><strong>Invoices Allocated:</strong> ${details.allocations_count || 0}</li>`;
        }
        
        html += `
                </ul>
            </div>
        `;
    }
    
    html += `
            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Allocation completed successfully</small>
                <button type="button" class="btn btn-sm btn-success" onclick="window.location.href='{{ route('finance.mpesa.c2b.dashboard') }}'">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </button>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('#responseMessages').html(html);
    
    // Scroll to message
    $('html, body').animate({
        scrollTop: $('#responseMessages').offset().top - 100
    }, 500);
}

// Show error message with details
function showError(message, details = []) {
    let html = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                ${message}
            </h5>
    `;
    
    if (details.length > 0) {
        html += `
            <hr>
            <div class="mb-2">
                <strong>Details:</strong>
                <ul class="mb-0 mt-2">
        `;
        details.forEach(function(detail) {
            html += `<li>${detail}</li>`;
        });
        html += `
                </ul>
            </div>
        `;
    }
    
    html += `
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    $('#responseMessages').html(html);
    
    // Scroll to message
    $('html, body').animate({
        scrollTop: $('#responseMessages').offset().top - 100
    }, 500);
}
</script>
@endsection

