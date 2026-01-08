@extends('adminlte::page')

@section('title', 'Prompt Parent to Pay')

@section('content_header')
    <h1><i class="fas fa-mobile-alt text-success"></i> Prompt Parent to Pay (STK Push)</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Initiate M-PESA STK Push Payment</h3>
            </div>
            <form action="{{ route('finance.mpesa.prompt-payment') }}" method="POST" id="promptPaymentForm">
                @csrf
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>How it works:</strong> When you submit this form, the parent will receive an STK push prompt on their phone to enter their M-PESA PIN and complete the payment.
                    </div>

                    <!-- Student Selection -->
                    <div class="form-group">
                        <label for="student_id">Student <span class="text-danger">*</span></label>
                        <select name="student_id" id="student_id" class="form-control select2" required>
                            <option value="">-- Select Student --</option>
                            @if($student)
                                <option value="{{ $student->id }}" selected>
                                    {{ $student->first_name }} {{ $student->last_name }} - {{ $student->admission_number }}
                                </option>
                            @endif
                        </select>
                        @error('student_id')
                            <span class="text-danger"><small>{{ $message }}</small></span>
                        @enderror
                    </div>

                    <!-- Phone Number Selection -->
                    <div class="form-group">
                        <label for="phone_source">Select Phone Number <span class="text-danger">*</span></label>
                        <select name="phone_source" id="phone_source" class="form-control">
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
                        <small class="form-text text-muted">Select whose phone number to send payment request to</small>
                    </div>

                    <!-- Phone Number Input -->
                    <div class="form-group" id="phone_number_group">
                        <label for="phone_number">Phone Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            </div>
                            <input type="text" name="phone_number" id="phone_number" class="form-control" 
                                   placeholder="e.g., 0712345678 or 254712345678" required>
                        </div>
                        <small class="form-text text-muted">Kenyan mobile number (Safaricom, Airtel, etc.)</small>
                        @error('phone_number')
                            <span class="text-danger"><small>{{ $message }}</small></span>
                        @enderror
                    </div>

                    <!-- Invoice Selection (Optional) -->
                    <div class="form-group">
                        <label for="invoice_id">Invoice (Optional)</label>
                        <select name="invoice_id" id="invoice_id" class="form-control">
                            <option value="">-- Select Invoice (or leave blank) --</option>
                            @if($invoice)
                                <option value="{{ $invoice->id }}" selected>
                                    {{ $invoice->invoice_number }} - Balance: KES {{ number_format($invoice->balance, 2) }}
                                </option>
                            @endif
                        </select>
                        <small class="form-text text-muted">If selected, payment will be allocated to this invoice</small>
                    </div>

                    <!-- Amount -->
                    <div class="form-group">
                        <label for="amount">Amount (KES) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="amount" class="form-control" 
                               step="0.01" min="1" placeholder="0.00" required
                               value="{{ $invoice ? $invoice->balance : old('amount') }}">
                        @error('amount')
                            <span class="text-danger"><small>{{ $message }}</small></span>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div class="form-group">
                        <label for="notes">Notes (Internal)</label>
                        <textarea name="notes" id="notes" class="form-control" rows="3" 
                                  placeholder="Optional notes for reference">{{ old('notes') }}</textarea>
                        <small class="form-text text-muted">These notes are for internal tracking only</small>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-paper-plane"></i> Send STK Push
                    </button>
                    <a href="{{ route('finance.mpesa.dashboard') }}" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-question-circle"></i> How to Use</h3>
            </div>
            <div class="card-body">
                <ol class="pl-3">
                    <li>Select the student</li>
                    <li>Enter parent's M-PESA phone number</li>
                    <li>Optionally select an invoice</li>
                    <li>Enter the amount to collect</li>
                    <li>Click "Send STK Push"</li>
                </ol>
                <hr>
                <h6><i class="fas fa-mobile-alt"></i> What happens next?</h6>
                <p><small>The parent will receive a pop-up on their phone asking them to enter their M-PESA PIN to authorize the payment. Once authorized, the payment will be processed automatically.</small></p>
            </div>
        </div>

        @if($student)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-user"></i> Student Info</h3>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> {{ $student->first_name }} {{ $student->last_name }}</p>
                <p><strong>Admission No:</strong> {{ $student->admission_number }}</p>
                <p><strong>Class:</strong> {{ $student->classroom->name ?? 'N/A' }}</p>
                @if($student->family)
                    <p><strong>Parent Phone:</strong> {{ $student->family->primary_phone ?? 'N/A' }}</p>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>
@stop

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
        placeholder: 'Type to search student...'
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
        btn.html('<i class="fas fa-spinner fa-spin"></i> Sending...');
    });
});
</script>
@stop

