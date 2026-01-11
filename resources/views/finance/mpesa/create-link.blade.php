@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Generate Payment Link',
        'icon' => 'bi bi-link-45deg',
        'subtitle' => 'Create shareable M-PESA payment links for parents',
        'actions' => '<a href="' . route('finance.mpesa.links.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-list"></i> View All Links</a><a href="' . route('finance.mpesa.dashboard') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>'
    ])

    <div class="row g-4">
        <!-- Main Form -->
        <div class="col-lg-8">
            <div class="finance-card finance-animate">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-plus-circle me-2"></i>
                        Create Secure Payment Link
                    </h5>
                </div>
                <div class="finance-card-body">
                    <!-- Info Alert -->
                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle fs-4 me-3"></i>
                            <div>
                                <strong>Payment links</strong> can be sent via SMS, Email, or WhatsApp to parents. Select student, choose invoices to pay, and send via your preferred channels.
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('finance.mpesa.links.store') }}" method="POST" id="createLinkForm">
                        @csrf

                        <!-- Step 1: Student Selection with Live Search -->
                        <div class="mb-4">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">1</span>
                                Select Student <span class="text-danger">*</span>
                            </label>
                            @include('partials.student_live_search', [
                                'hiddenInputId' => 'student_id',
                                'displayInputId' => 'studentSearchDisplay',
                                'resultsId' => 'studentSearchResults',
                                'placeholder' => 'Type name or admission #',
                                'initialLabel' => $student ? $student->full_name . ' (' . $student->admission_number . ')' : ''
                            ])
                            @error('student_id')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Step 2: Select Invoices -->
                        <div class="mb-4" id="invoiceSelectionSection" style="display: none;">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">2</span>
                                Select Invoices to Pay
                            </label>
                            <div id="invoicesList" class="border rounded p-3 bg-light">
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-hourglass-split"></i> Loading invoices...
                                </div>
                            </div>
                            <div class="mt-2">
                                <strong>Total Selected:</strong> 
                                <span id="totalAmount" class="text-primary fs-5">KES 0.00</span>
                                <input type="hidden" name="amount" id="amount" value="0">
                                <input type="hidden" name="selected_invoices" id="selected_invoices" value="">
                            </div>
                            @error('amount')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Step 3: Select Parents -->
                        <div class="mb-4" id="parentSelectionSection" style="display: none;">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">3</span>
                                Select Parent(s) to Notify <span class="text-danger">*</span>
                            </label>
                            <div id="parentsList" class="border rounded p-3">
                                <div class="text-center text-muted py-2">
                                    Select a student first
                                </div>
                            </div>
                            @error('parents')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Step 4: Select Communication Channels -->
                        <div class="mb-4" id="channelSelectionSection" style="display: none;">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">4</span>
                                Send Link Via <span class="text-danger">*</span>
                            </label>
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="send_channels[]" value="sms" id="sendSMS" checked>
                                    <label class="form-check-label" for="sendSMS">
                                        <i class="bi bi-chat-dots"></i> SMS (RKS_FINANCE)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="send_channels[]" value="email" id="sendEmail">
                                    <label class="form-check-label" for="sendEmail">
                                        <i class="bi bi-envelope"></i> Email
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="send_channels[]" value="whatsapp" id="sendWhatsApp">
                                    <label class="form-check-label" for="sendWhatsApp">
                                        <i class="bi bi-whatsapp"></i> WhatsApp
                                    </label>
                                </div>
                            </div>
                            <small class="text-muted">Payment link will be sent immediately via selected channels</small>
                        </div>

                        <!-- Additional Options -->
                        <div class="mb-4" id="optionsSection" style="display: none;">
                            <label class="finance-form-label">Additional Options</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="expires_in_days" class="form-label small">Link Expires In (Days)</label>
                                    <input type="number" name="expires_in_days" id="expires_in_days" 
                                           class="finance-form-control" min="1" max="365" placeholder="7"
                                           value="7">
                                </div>
                                <div class="col-md-6">
                                    <label for="max_uses" class="form-label small">Maximum Uses</label>
                                    <input type="number" name="max_uses" id="max_uses" 
                                           class="finance-form-control" min="1" max="100"
                                           value="1">
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="finance-card-footer mt-4">
                            <button type="submit" class="btn btn-finance btn-finance-primary btn-lg" id="submitBtn" disabled>
                                <i class="bi bi-link-45deg"></i> Generate & Send Payment Link
                            </button>
                            <a href="{{ route('finance.mpesa.dashboard') }}" class="btn btn-finance btn-finance-outline">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- How to Use Card -->
            <div class="finance-card finance-animate mb-4">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-question-circle me-2"></i>
                        How to Use
                    </h5>
                </div>
                <div class="finance-card-body">
                    <ol class="ps-3 mb-0">
                        <li class="mb-2">Search and select the student</li>
                        <li class="mb-2">Select one or more outstanding invoices</li>
                        <li class="mb-2">Choose which parent(s) to notify</li>
                        <li class="mb-2">Select communication channels (SMS/Email/WhatsApp)</li>
                        <li class="mb-2">Click "Generate & Send Payment Link"</li>
                    </ol>
                </div>
            </div>

            <!-- Student Info Card -->
            <div class="finance-card finance-animate" id="studentInfoCard" style="display: none;">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-person me-2"></i>
                        Student Info
                    </h5>
                </div>
                <div class="finance-card-body" id="studentInfoBody">
                    <!-- Will be populated by JavaScript -->
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    let selectedInvoices = [];
    let studentData = null;

    // Watch for student selection from live search
    $(document).on('studentSelected', function(e, student) {
        if (student && student.id) {
            loadStudentData(student.id);
        }
    });

    // If student is pre-selected, trigger load
    @if($student)
        loadStudentData({{ $student->id }});
    @endif

    // Load student data function
    function loadStudentData(studentId) {
        $('#invoiceSelectionSection').hide();
        $('#parentSelectionSection').hide();
        $('#channelSelectionSection').hide();
        $('#optionsSection').hide();
        $('#studentInfoCard').show();

        $.get('/api/students/' + studentId, function(student) {
            studentData = student;
            
            // Update student info card
            let infoHtml = `
                <div class="mb-2"><strong>Name:</strong> <span class="text-muted">${student.first_name} ${student.last_name}</span></div>
                <div class="mb-2"><strong>Admission No:</strong> <span class="text-muted">${student.admission_number}</span></div>
                <div class="mb-2"><strong>Class:</strong> <span class="text-muted">${student.classroom?.name || 'N/A'}</span></div>
            `;
            if (student.family) {
                if (student.family.phone) {
                    infoHtml += `<div class="mb-2"><strong>Parent Phone:</strong> <span class="text-muted">${student.family.phone}</span></div>`;
                }
                if (student.family.email) {
                    infoHtml += `<div class="mb-0"><strong>Parent Email:</strong> <span class="text-muted">${student.family.email}</span></div>`;
                }
            }
            $('#studentInfoBody').html(infoHtml);

            // Load invoices
            loadInvoices(studentId);
            // Load parents
            loadParents(student);
        });
    }

    // Load invoices with checkboxes
    function loadInvoices(studentId) {
        $.get('/api/students/' + studentId + '/invoices', function(invoices) {
            let unpaidInvoices = invoices.filter(inv => inv.balance > 0);
            
            if (unpaidInvoices.length === 0) {
                $('#invoicesList').html(`
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-check-circle fs-2 text-success"></i>
                        <p class="mb-0">No outstanding invoices</p>
                    </div>
                `);
                $('#invoiceSelectionSection').show();
                return;
            }

            let html = '<div class="list-group list-group-flush">';
            unpaidInvoices.forEach(function(invoice) {
                html += `
                    <div class="list-group-item p-3">
                        <div class="form-check">
                            <input class="form-check-input invoice-checkbox" type="checkbox" 
                                   value="${invoice.id}" 
                                   data-amount="${invoice.balance}"
                                   id="invoice_${invoice.id}">
                            <label class="form-check-label w-100" for="invoice_${invoice.id}">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong>${invoice.invoice_number}</strong>
                                        <br><small class="text-muted">Due: ${invoice.due_date || 'N/A'}</small>
                                    </div>
                                    <div class="text-end">
                                        <strong class="text-primary">KES ${parseFloat(invoice.balance).toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            $('#invoicesList').html(html);
            $('#invoiceSelectionSection').show();
            
            // Bind checkbox change event
            $('.invoice-checkbox').on('change', updateTotal);
        });
    }

    // Load parents
    function loadParents(student) {
        if (!student.family) {
            $('#parentsList').html('<div class="text-center text-muted py-2">No parent information available</div>');
            return;
        }

        let html = '';
        let hasParents = false;

        // Father
        if (student.family.father_name || student.family.father_phone) {
            hasParents = true;
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input parent-checkbox" type="checkbox" name="parents[]" value="father" 
                           id="parent_father" checked>
                    <label class="form-check-label" for="parent_father">
                        <strong>Father${student.family.father_name ? ': ' + student.family.father_name : ''}</strong>
                        ${student.family.father_phone ? '<br><small class="text-muted">Phone: ' + student.family.father_phone + '</small>' : ''}
                        ${student.family.father_email ? '<br><small class="text-muted">Email: ' + student.family.father_email + '</small>' : ''}
                    </label>
                </div>
            `;
        }

        // Mother
        if (student.family.mother_name || student.family.mother_phone) {
            hasParents = true;
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input parent-checkbox" type="checkbox" name="parents[]" value="mother" 
                           id="parent_mother" checked>
                    <label class="form-check-label" for="parent_mother">
                        <strong>Mother${student.family.mother_name ? ': ' + student.family.mother_name : ''}</strong>
                        ${student.family.mother_phone ? '<br><small class="text-muted">Phone: ' + student.family.mother_phone + '</small>' : ''}
                        ${student.family.mother_email ? '<br><small class="text-muted">Email: ' + student.family.mother_email + '</small>' : ''}
                    </label>
                </div>
            `;
        }

        // Primary contact if different
        if (student.family.phone && 
            student.family.phone != student.family.father_phone && 
            student.family.phone != student.family.mother_phone) {
            hasParents = true;
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input parent-checkbox" type="checkbox" name="parents[]" value="primary" 
                           id="parent_primary" checked>
                    <label class="form-check-label" for="parent_primary">
                        <strong>Primary Contact</strong>
                        <br><small class="text-muted">Phone: ${student.family.phone}</small>
                        ${student.family.email ? '<br><small class="text-muted">Email: ' + student.family.email + '</small>' : ''}
                    </label>
                </div>
            `;
        }

        if (!hasParents) {
            html = '<div class="text-center text-muted py-2">No parent contact information available</div>';
        }

        $('#parentsList').html(html);
        $('#parentSelectionSection').show();
        $('#channelSelectionSection').show();
        $('#optionsSection').show();
    }

    // Update total amount
    function updateTotal() {
        selectedInvoices = [];
        let total = 0;
        
        $('.invoice-checkbox:checked').each(function() {
            let amount = parseFloat($(this).data('amount'));
            let invoiceId = $(this).val();
            total += amount;
            selectedInvoices.push(invoiceId);
        });
        
        $('#totalAmount').text('KES ' + total.toLocaleString('en-KE', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#amount').val(total.toFixed(2));
        $('#selected_invoices').val(selectedInvoices.join(','));
        
        // Enable submit button if at least one invoice and one parent and one channel selected
        updateSubmitButton();
    }

    // Update submit button state
    function updateSubmitButton() {
        let hasInvoices = $('.invoice-checkbox:checked').length > 0;
        let hasParents = $('.parent-checkbox:checked').length > 0;
        let hasChannels = $('input[name="send_channels[]"]:checked').length > 0;
        
        $('#submitBtn').prop('disabled', !(hasInvoices && hasParents && hasChannels));
    }

    // Listen to parent and channel changes
    $(document).on('change', '.parent-checkbox, input[name="send_channels[]"]', updateSubmitButton);

    // Form submission
    $('#createLinkForm').on('submit', function(e) {
        // Validate
        if ($('.invoice-checkbox:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one invoice.');
            return false;
        }
        
        if ($('.parent-checkbox:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one parent to notify.');
            return false;
        }
        
        if ($('input[name="send_channels[]"]:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one channel to send the payment link.');
            return false;
        }

        let btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        btn.html('<i class="bi bi-hourglass-split"></i> Creating & Sending...');
    });
});
</script>
@endsection
