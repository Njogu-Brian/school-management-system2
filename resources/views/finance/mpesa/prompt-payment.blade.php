@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Prompt Parent to Pay (STK Push)',
        'icon' => 'bi bi-phone',
        'subtitle' => 'Send M-PESA STK Push payment request to parents',
        'actions' => '<a href="' . route('finance.mpesa.dashboard') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>'
    ])

    <div class="row g-4">
        <!-- Main Form -->
        <div class="col-lg-8">
            <div class="finance-card finance-animate">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-send me-2"></i>
                        Initiate M-PESA STK Push Payment
                    </h5>
                </div>
                <div class="finance-card-body">
                    <!-- Info Alert -->
                    <div class="alert alert-info border-0 mb-4">
                        <div class="d-flex align-items-start">
                            <i class="bi bi-info-circle fs-4 me-3"></i>
                            <div>
                                <strong>How it works:</strong> When you submit this form, the parent will receive an STK push prompt on their phone to enter their M-PESA PIN and complete the payment.
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('finance.mpesa.prompt-payment') }}" method="POST" id="promptPaymentForm">
                        @csrf
                        
                        <!-- Student Selection -->
                        <div class="mb-4">
                            <label for="student_id" class="finance-form-label">
                                Student <span class="text-danger">*</span>
                            </label>
                            <select name="student_id" id="student_id" class="finance-form-select" required>
                                <option value="">-- Select Student --</option>
                                @if($student)
                                    <option value="{{ $student->id }}" selected>
                                        {{ $student->first_name }} {{ $student->last_name }} - {{ $student->admission_number }}
                                    </option>
                                @endif
                            </select>
                            @error('student_id')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Phone Number Selection -->
                        <div class="mb-4">
                            <label for="phone_source" class="finance-form-label">
                                Select Phone Number <span class="text-danger">*</span>
                            </label>
                            <select name="phone_source" id="phone_source" class="finance-form-select">
                                <option value="">-- Select Phone Number --</option>
                                @if($student && $student->family)
                                    @if($student->family->father_phone)
                                        <option value="father" data-phone="{{ $student->family->father_phone }}">
                                            Father's Phone - {{ $student->family->father_phone }}
                                            @if($student->family->father_name)
                                                ({{ $student->family->father_name }})
                                            @endif
                                        </option>
                                    @endif
                                    @if($student->family->mother_phone)
                                        <option value="mother" data-phone="{{ $student->family->mother_phone }}">
                                            Mother's Phone - {{ $student->family->mother_phone }}
                                            @if($student->family->mother_name)
                                                ({{ $student->family->mother_name }})
                                            @endif
                                        </option>
                                    @endif
                                    @if($student->family->phone && $student->family->phone != $student->family->father_phone && $student->family->phone != $student->family->mother_phone)
                                        <option value="primary" data-phone="{{ $student->family->phone }}">
                                            Primary Phone - {{ $student->family->phone }}
                                        </option>
                                    @endif
                                @endif
                                <option value="custom">Enter Different Number</option>
                            </select>
                            <small class="text-muted">Select whose phone number to send payment request to</small>
                        </div>

                        <!-- Phone Number Input -->
                        <div class="mb-4" id="phone_number_group">
                            <label for="phone_number" class="finance-form-label">
                                Phone Number <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text finance-input-group-text">
                                    <i class="bi bi-phone"></i>
                                </span>
                                <input type="text" name="phone_number" id="phone_number" class="finance-form-control" 
                                       placeholder="e.g., 0712345678 or 254712345678" required>
                            </div>
                            <small class="text-muted">Kenyan mobile number (Safaricom, Airtel, etc.)</small>
                            @error('phone_number')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Invoice Selection (Optional) -->
                        <div class="mb-4">
                            <label for="invoice_id" class="finance-form-label">Invoice (Optional)</label>
                            <select name="invoice_id" id="invoice_id" class="finance-form-select">
                                <option value="">-- Select Invoice (or leave blank) --</option>
                                @if($invoice)
                                    <option value="{{ $invoice->id }}" selected>
                                        {{ $invoice->invoice_number }} - Balance: KES {{ number_format($invoice->balance, 2) }}
                                    </option>
                                @endif
                            </select>
                            <small class="text-muted">If selected, payment will be allocated to this invoice</small>
                        </div>

                        <!-- Amount -->
                        <div class="mb-4">
                            <label for="amount" class="finance-form-label">
                                Amount (KES) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text finance-input-group-text">KES</span>
                                <input type="number" name="amount" id="amount" class="finance-form-control" 
                                       step="0.01" min="1" placeholder="0.00" required
                                       value="{{ $invoice ? $invoice->balance : old('amount') }}">
                            </div>
                            @error('amount')
                                <div class="finance-form-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="notes" class="finance-form-label">Notes (Internal)</label>
                            <textarea name="notes" id="notes" class="finance-form-control" rows="3" 
                                      placeholder="Optional notes for reference">{{ old('notes') }}</textarea>
                            <small class="text-muted">These notes are for internal tracking only</small>
                        </div>

                        <!-- Form Actions -->
                        <div class="finance-card-footer">
                            <button type="submit" class="btn btn-finance btn-finance-primary btn-lg">
                                <i class="bi bi-send"></i> Send STK Push
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
                    <ol class="ps-3 mb-3">
                        <li class="mb-2">Select the student</li>
                        <li class="mb-2">Choose parent's M-PESA phone number</li>
                        <li class="mb-2">Optionally select an invoice</li>
                        <li class="mb-2">Enter the amount to collect</li>
                        <li class="mb-2">Click "Send STK Push"</li>
                    </ol>
                    <hr class="my-3">
                    <h6 class="mb-2">
                        <i class="bi bi-phone me-2"></i>
                        What happens next?
                    </h6>
                    <p class="small text-muted mb-0">
                        The parent will receive a pop-up on their phone asking them to enter their M-PESA PIN to authorize the payment. Once authorized, the payment will be processed automatically.
                    </p>
                </div>
            </div>

            <!-- Student Info Card -->
            @if($student)
            <div class="finance-card finance-animate">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-person me-2"></i>
                        Student Info
                    </h5>
                </div>
                <div class="finance-card-body">
                    <div class="mb-2">
                        <strong>Name:</strong> 
                        <span class="text-muted">{{ $student->first_name }} {{ $student->last_name }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Admission No:</strong>
                        <span class="text-muted">{{ $student->admission_number }}</span>
                    </div>
                    <div class="mb-2">
                        <strong>Class:</strong>
                        <span class="text-muted">{{ $student->classroom->name ?? 'N/A' }}</span>
                    </div>
                    @if($student->family)
                        <div class="mb-0">
                            <strong>Parent Phone:</strong>
                            <span class="text-muted">{{ $student->family->phone ?? 'N/A' }}</span>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@section('js')
<script>
$(document).ready(function() {
    // Handle phone source selection
    $('#phone_source').on('change', function() {
        var selectedOption = $(this).find('option:selected');
        var phone = selectedOption.data('phone');
        
        if ($(this).val() === 'custom' || $(this).val() === '') {
            $('#phone_number').val('').prop('readonly', false).focus();
        } else if (phone) {
            $('#phone_number').val(phone).prop('readonly', false);
        }
    });

    // Initialize select2
    $('#student_id').select2({
        ajax: {
            url: '{{ route("students.search") }}',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return {
                    q: params.term,
                    active_only: 1
                };
            },
            processResults: function (data) {
                return {
                    results: data.map(function(student) {
                        return {
                            id: student.id,
                            text: student.first_name + ' ' + student.last_name + ' - ' + student.admission_number
                        };
                    })
                };
            },
            cache: true
        },
        minimumInputLength: 2,
        placeholder: 'Type to search student...',
        theme: 'bootstrap-5'
    });

    // Load invoices when student is selected
    $('#student_id').on('change', function() {
        var studentId = $(this).val();
        if (studentId) {
            // Load unpaid invoices
            $.get('{{ url("/api/students") }}/' + studentId + '/invoices', function(invoices) {
                var invoiceSelect = $('#invoice_id');
                invoiceSelect.empty();
                invoiceSelect.append('<option value="">-- Select Invoice (or leave blank) --</option>');
                
                invoices.forEach(function(invoice) {
                    if (invoice.balance > 0) {
                        invoiceSelect.append(
                            '<option value="' + invoice.id + '" data-balance="' + invoice.balance + '">' +
                            invoice.invoice_number + ' - Balance: KES ' + parseFloat(invoice.balance).toLocaleString() +
                            '</option>'
                        );
                    }
                });
            });

            // Load parent phones
            $.get('{{ url("/api/students") }}/' + studentId, function(student) {
                var phoneSelect = $('#phone_source');
                phoneSelect.empty();
                phoneSelect.append('<option value="">-- Select Phone Number --</option>');
                
                if (student.family) {
                    if (student.family.father_phone) {
                        var fatherName = student.family.father_name ? ' (' + student.family.father_name + ')' : '';
                        phoneSelect.append(
                            '<option value="father" data-phone="' + student.family.father_phone + '">' +
                            'Father\'s Phone - ' + student.family.father_phone + fatherName +
                            '</option>'
                        );
                    }
                    if (student.family.mother_phone) {
                        var motherName = student.family.mother_name ? ' (' + student.family.mother_name + ')' : '';
                        phoneSelect.append(
                            '<option value="mother" data-phone="' + student.family.mother_phone + '">' +
                            'Mother\'s Phone - ' + student.family.mother_phone + motherName +
                            '</option>'
                        );
                    }
                    if (student.family.phone && 
                        student.family.phone != student.family.father_phone && 
                        student.family.phone != student.family.mother_phone) {
                        phoneSelect.append(
                            '<option value="primary" data-phone="' + student.family.phone + '">' +
                            'Primary Phone - ' + student.family.phone +
                            '</option>'
                        );
                    }
                }
                
                phoneSelect.append('<option value="custom">Enter Different Number</option>');
                
                // Auto-select first available phone
                if (phoneSelect.find('option').length > 2) {
                    phoneSelect.find('option:eq(1)').prop('selected', true).trigger('change');
                }
            });
        }
    });

    // Auto-fill amount when invoice is selected
    $('#invoice_id').on('change', function() {
        var selected = $(this).find('option:selected');
        var balance = selected.data('balance');
        if (balance) {
            $('#amount').val(parseFloat(balance).toFixed(2));
        }
    });

    // Form submission
    $('#promptPaymentForm').on('submit', function(e) {
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        btn.html('<i class="bi bi-hourglass-split"></i> Sending...');
    });
});
</script>
@endsection
