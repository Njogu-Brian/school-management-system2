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
                                <strong>Payment links</strong> — Select student, parent(s), and channel(s) (SMS/Email/WhatsApp). All fields are shown at once. The form is sent only when student, at least one parent, and at least one channel are filled. You can set link expiry or "Never expire".
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
                                'initialLabel' => $student ? $student->search_display : '',
                                'initialStudentId' => $student ? $student->id : null,
                            ])
                            @error('student_id')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>
                    <div id="studentDataAlertLink" class="alert alert-warning border-0 d-none" role="alert"></div>

                        <!-- Step 2: Select Parent(s) – always visible -->
                        <div class="mb-4" id="parentSelectionSection">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">2</span>
                                Select Parent(s) to Send Link To <span class="text-danger">*</span>
                            </label>
                            <div id="parentsList" class="border rounded p-3">
                                <div class="text-center text-muted py-2">
                                    Select a student above to load parent contacts
                                </div>
                            </div>
                            @error('parents')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Step 3: Select Channel(s) – always visible -->
                        <div class="mb-4" id="channelSelectionSection">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">3</span>
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

                        <!-- Step 4: Payment Type -->
                        <div class="mb-4" id="paymentTypeSection" style="display: none;">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">4</span>
                                Payment Type
                            </label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_swimming" id="linkPaymentTypeFees" value="0" checked>
                                    <label class="form-check-label" for="linkPaymentTypeFees">
                                        <i class="bi bi-book"></i> School Fees
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="is_swimming" id="linkPaymentTypeSwimming" value="1">
                                    <label class="form-check-label" for="linkPaymentTypeSwimming">
                                        <i class="bi bi-water"></i> Swimming Fees
                                    </label>
                                </div>
                            </div>
                            <small class="text-muted">Account reference: <span id="linkAccountReferencePreview">-</span></small>
                        </div>

                        <!-- Step 5: Select Invoices (for school fees) – always visible -->
                        <div class="mb-4" id="invoiceSelectionSection">
                            <label class="finance-form-label">
                                <span class="badge bg-primary me-2">5</span>
                                Select Invoices to Pay
                            </label>
                            <div id="invoicesList" class="border rounded p-3 bg-light">
                                <div class="text-center text-muted py-3">
                                    Select a student to load invoices
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

                        <!-- Additional Options – always visible -->
                        <div class="mb-4" id="optionsSection">
                            <label class="finance-form-label">Additional Options</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="never_expire" id="never_expire" value="1">
                                        <label class="form-check-label" for="never_expire">Never expire</label>
                                    </div>
                                    <label for="expires_in_days" class="form-label small">Link Expires In (Days)</label>
                                    <input type="number" name="expires_in_days" id="expires_in_days" 
                                           class="finance-form-control" min="0" max="365" placeholder="7"
                                           value="7">
                                    <small class="text-muted">Leave empty or check "Never expire" for no expiry</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="max_uses" class="form-label small">Maximum Uses</label>
                                    <input type="number" name="max_uses" id="max_uses" 
                                           class="finance-form-control" min="1" max="100"
                                           value="1">
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions: button always active; submit runs only when student + parent + channel are filled -->
                        <div class="finance-card-footer mt-4">
                            <button type="submit" class="btn btn-finance btn-finance-primary btn-lg" id="submitBtn">
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
                        <li class="mb-2">Select which parent(s) to send the link to</li>
                        <li class="mb-2">Select channel(s) — SMS, Email, and/or WhatsApp</li>
                        <li class="mb-2">Choose payment type and invoices (for school fees)</li>
                        <li class="mb-2">Click "Generate & Send Payment Link" (runs only when student, parent, and channel are filled)</li>
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

@push('scripts')
<script>
$(document).ready(function() {
    let selectedInvoices = [];
    let studentData = null;

    // Watch for student selection from live search (vanilla + jQuery)
    // Prefer hidden input value (set by live search) so selection always triggers load
    function onStudentSelected(e, studentFromJq) {
        var student = studentFromJq != null ? studentFromJq : (e && e.detail) ? e.detail : null;
        var id = ($('#student_id').val() || (student && student.id) || '').toString().trim();
        if (id) {
            loadStudentData(id);
        }
    }
    window.addEventListener('student-selected', function(e) {
        onStudentSelected(e, null);
    });
    $(document).on('studentSelected', function(e, student) {
        onStudentSelected(e, student);
    });

    // If student is pre-selected (from URL or controller), trigger load
    @if($student)
        loadStudentData({{ $student->id }});
    @else
        // Fallback: if hidden input has value (e.g. from query string) but parents not loaded, load now
        var initialId = $('#student_id').val();
        if (initialId && $('#parentsList').text().indexOf('load parent') !== -1) {
            loadStudentData(initialId);
        }
    @endif

    // Fallback: react to hidden input changes
    $('#student_id').on('change', function() {
        const id = $(this).val();
        if (id) {
            loadStudentData(id);
        }
    });

    // Load student data (sections 2–5 are always visible; we just populate them)
    function loadStudentData(studentId) {
        $('#studentInfoCard').show();
        $('#invoicesList').html('<div class="text-center text-muted py-3"><i class="bi bi-hourglass-split"></i> Loading invoices...</div>');
        $('#parentsList').html('<div class="text-center text-muted py-2">Loading parent information...</div>');
        $('#studentDataAlertLink').addClass('d-none').text('');

        $.get('/api/students/' + studentId)
            .done(function(student) {
                console.log('Student data loaded:', student);
                studentData = student;
                
                // Update student info card
                let infoHtml = `
                    <div class="mb-2"><strong>Name:</strong> <span class="text-muted">${student.full_name}</span></div>
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

                loadParents(student);
                updateAccountReference(student);
                loadInvoices(studentId).then(function() {
                    updateSubmitButton();
                }).catch(function(error) {
                    console.error('Error loading invoices:', error);
                    updateSubmitButton();
                });
                if (!student.family) {
                    $('#studentDataAlertLink')
                        .removeClass('d-none')
                        .text('Parent contacts missing: no Family/ParentInfo data returned for this student.');
                }
            })
            .fail(function(xhr, status, error) {
                console.error('Failed to load student data:', xhr, status, error);
                let errorMsg = 'Failed to load student data. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMsg = 'Student not found. Please search again.';
                } else if (xhr.status === 403) {
                    errorMsg = 'You do not have permission to access this student.';
                }
                alert(errorMsg);
                $('#studentDataAlertLink').removeClass('d-none').text(errorMsg);
                $('#studentInfoBody').html('<div class="text-danger">' + errorMsg + '</div>');
            });
    }

    // Update account reference preview
    function updateAccountReference(student) {
        if (!student || !student.admission_number) {
            $('#linkAccountReferencePreview').text('-');
            return;
        }
        
        const isSwimming = $('input[name="is_swimming"]:checked').val() === '1';
        const accountRef = isSwimming ? `SWIM-${student.admission_number}` : student.admission_number;
        $('#linkAccountReferencePreview').text(accountRef);
    }

    // Listen to payment type changes
    $(document).on('change', 'input[name="is_swimming"]', function() {
        if (studentData) {
            updateAccountReference(studentData);
            // Show/hide invoice selection based on payment type
            const isSwimming = $(this).val() === '1';
            if (isSwimming) {
                $('#invoiceSelectionSection').hide();
                // For swimming, we don't need invoices, so enable submit if parents and channels are selected
                updateSubmitButton();
            } else {
                // For fees, show invoices if they exist
                if ($('.invoice-checkbox').length > 0) {
                    $('#invoiceSelectionSection').show();
                }
                updateSubmitButton();
            }
        }
    });

    // Load invoices with checkboxes
    function loadInvoices(studentId) {
        return $.get('/api/students/' + studentId + '/invoices')
            .done(function(invoices) {
                console.log('Invoices loaded:', invoices);
                let unpaidInvoices = invoices.filter(inv => inv.balance > 0);
                
                if (unpaidInvoices.length === 0) {
                    $('#invoicesList').html(`
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-check-circle fs-2 text-success"></i>
                            <p class="mb-0">No outstanding invoices</p>
                        </div>
                    `);
                    $('#invoiceSelectionSection').show();
                    updateSubmitButton();
                    $('#studentDataAlertLink')
                        .removeClass('d-none')
                        .text('No invoices returned for this student. Check invoice status or API response.');
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
                $(document).off('change', '.invoice-checkbox').on('change', '.invoice-checkbox', function() {
                    updateTotal();
                    updateSubmitButton();
                });
                
                updateSubmitButton();
            })
            .fail(function(xhr, status, error) {
                console.error('Failed to load invoices:', xhr, status, error);
                let errorMsg = 'Failed to load invoices. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMsg = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMsg = 'Student not found.';
                } else if (xhr.status === 403) {
                    errorMsg = 'You do not have permission to access invoices.';
                }
                $('#invoicesList').html(`
                    <div class="text-center text-danger py-3">
                        <i class="bi bi-exclamation-circle fs-2"></i>
                        <p class="mb-0">${errorMsg}</p>
                    </div>
                `);
                $('#invoiceSelectionSection').show();
                updateSubmitButton();
                $('#studentDataAlertLink').removeClass('d-none').text(errorMsg);
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

        // Father (check for phone or WhatsApp)
        let fatherPhone = student.family.father_whatsapp || student.family.father_phone;
        if (student.family.father_name || fatherPhone) {
            hasParents = true;
            let phoneDisplay = '';
            if (student.family.father_whatsapp) {
                phoneDisplay = `<br><small class="text-muted">WhatsApp: ${student.family.father_whatsapp}</small>`;
                if (student.family.father_phone && student.family.father_phone !== student.family.father_whatsapp) {
                    phoneDisplay += `<br><small class="text-muted">Phone: ${student.family.father_phone}</small>`;
                }
            } else if (student.family.father_phone) {
                phoneDisplay = `<br><small class="text-muted">Phone: ${student.family.father_phone}</small>`;
            }
            
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input parent-checkbox" type="checkbox" name="parents[]" value="father" 
                           id="parent_father" checked>
                    <label class="form-check-label" for="parent_father">
                        <strong>Father${student.family.father_name ? ': ' + student.family.father_name : ''}</strong>
                        ${phoneDisplay}
                        ${student.family.father_email ? '<br><small class="text-muted">Email: ' + student.family.father_email + '</small>' : ''}
                    </label>
                </div>
            `;
        }

        // Mother (check for phone or WhatsApp)
        let motherPhone = student.family.mother_whatsapp || student.family.mother_phone;
        if (student.family.mother_name || motherPhone) {
            hasParents = true;
            let phoneDisplay = '';
            if (student.family.mother_whatsapp) {
                phoneDisplay = `<br><small class="text-muted">WhatsApp: ${student.family.mother_whatsapp}</small>`;
                if (student.family.mother_phone && student.family.mother_phone !== student.family.mother_whatsapp) {
                    phoneDisplay += `<br><small class="text-muted">Phone: ${student.family.mother_phone}</small>`;
                }
            } else if (student.family.mother_phone) {
                phoneDisplay = `<br><small class="text-muted">Phone: ${student.family.mother_phone}</small>`;
            }
            
            html += `
                <div class="form-check mb-2">
                    <input class="form-check-input parent-checkbox" type="checkbox" name="parents[]" value="mother" 
                           id="parent_mother" checked>
                    <label class="form-check-label" for="parent_mother">
                        <strong>Mother${student.family.mother_name ? ': ' + student.family.mother_name : ''}</strong>
                        ${phoneDisplay}
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
        
        // Bind parent checkbox change event
        $(document).off('change', '.parent-checkbox').on('change', '.parent-checkbox', function() {
            console.log('Parent checkbox changed');
            updateSubmitButton();
        });
        
        updateSubmitButton();
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
    }

    // Update submit button state (button stays active; validation happens on submit)
    function updateSubmitButton() {
        // Button is always enabled; no-op here unless you add visual hints later
    }

    // Listen to channel changes
    $(document).on('change', 'input[name="send_channels[]"]', function() {
        updateSubmitButton();
    });

    // Never expire: when checked, clear expires_in_days so link never expires
    $('#never_expire').on('change', function() {
        if ($(this).is(':checked')) {
            $('#expires_in_days').val('').prop('readonly', true);
        } else {
            $('#expires_in_days').val('7').prop('readonly', false);
        }
    });
    
    setTimeout(function() {
        updateSubmitButton();
    }, 1000);

    // Form submission: button always active; execute only when student + parent + channel are filled
    $('#createLinkForm').on('submit', function(e) {
        var studentId = $('#student_id').val();
        var hasParents = $('.parent-checkbox:checked').length > 0;
        var hasChannels = $('input[name="send_channels[]"]:checked').length > 0;

        if (!studentId) {
            e.preventDefault();
            alert('Please select a student.');
            return false;
        }
        if (!hasParents) {
            e.preventDefault();
            alert('Please select at least one parent to send the link to.');
            return false;
        }
        if (!hasChannels) {
            e.preventDefault();
            alert('Please select at least one channel (SMS, Email, or WhatsApp).');
            return false;
        }

        var isSwimming = $('input[name="is_swimming"]:checked').val() === '1';
        if (!isSwimming && $('.invoice-checkbox:checked').length === 0) {
            e.preventDefault();
            alert('Please select at least one invoice for school fees payment.');
            return false;
        }

        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        btn.html('<i class="bi bi-hourglass-split"></i> Creating & Sending...');
    });
});
</script>
@endpush
