@extends('adminlte::page')

@section('title', 'Create Payment Link')

@section('content_header')
    <h1><i class="fas fa-link text-primary"></i> Generate Payment Link</h1>
@stop

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title">Create Secure Payment Link</h3>
            </div>
            <form action="{{ route('finance.mpesa.links.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Payment links</strong> can be sent via SMS or Email to parents. They provide an easy way for parents to pay fees at their convenience.
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
                        <small class="form-text text-muted">Link payment to a specific invoice</small>
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

                    <!-- Description -->
                    <div class="form-group">
                        <label for="description">Description</label>
                        <input type="text" name="description" id="description" class="form-control" 
                               placeholder="e.g., School Fee Payment - Term 1"
                               value="{{ old('description', 'School Fee Payment') }}">
                        <small class="form-text text-muted">This will be shown to the parent</small>
                    </div>

                    <div class="row">
                        <!-- Expiry -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expires_in_days">Link Expires In (Days)</label>
                                <input type="number" name="expires_in_days" id="expires_in_days" 
                                       class="form-control" min="1" max="365" placeholder="7"
                                       value="{{ old('expires_in_days', 7) }}">
                                <small class="form-text text-muted">Leave blank for no expiry</small>
                            </div>
                        </div>

                        <!-- Max Uses -->
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="max_uses">Maximum Uses</label>
                                <input type="number" name="max_uses" id="max_uses" 
                                       class="form-control" min="1" max="100"
                                       value="{{ old('max_uses', 1) }}">
                                <small class="form-text text-muted">How many times the link can be used</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-link"></i> Generate Payment Link
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
                    <li>Optionally select an invoice</li>
                    <li>Enter the payment amount</li>
                    <li>Set link expiry and usage limits</li>
                    <li>Click "Generate Payment Link"</li>
                </ol>
                <hr>
                <h6><i class="fas fa-share-alt"></i> After Creating:</h6>
                <p><small>You can send the payment link to parents via SMS or Email. Parents can click the link and pay directly using M-PESA.</small></p>
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
                    <p><strong>Parent Email:</strong> {{ $student->family->primary_email ?? 'N/A' }}</p>
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
    // Initialize select2 for student search
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
});
</script>
@stop

