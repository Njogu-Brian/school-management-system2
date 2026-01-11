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
                                <strong>Payment links</strong> can be sent via SMS or Email to parents. They provide an easy way for parents to pay fees at their convenience.
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('finance.mpesa.links.store') }}" method="POST" id="createLinkForm">
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
                            <small class="text-muted">Link payment to a specific invoice</small>
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

                        <!-- Description -->
                        <div class="mb-4">
                            <label for="description" class="finance-form-label">Description</label>
                            <input type="text" name="description" id="description" class="finance-form-control" 
                                   placeholder="e.g., School Fee Payment - Term 1"
                                   value="{{ old('description', 'School Fee Payment') }}">
                            <small class="text-muted">This will be shown to the parent</small>
                        </div>

                        <div class="row g-3">
                            <!-- Expiry -->
                            <div class="col-md-6">
                                <label for="expires_in_days" class="finance-form-label">Link Expires In (Days)</label>
                                <input type="number" name="expires_in_days" id="expires_in_days" 
                                       class="finance-form-control" min="1" max="365" placeholder="7"
                                       value="{{ old('expires_in_days', 7) }}">
                                <small class="text-muted">Leave blank for no expiry</small>
                            </div>

                            <!-- Max Uses -->
                            <div class="col-md-6">
                                <label for="max_uses" class="finance-form-label">Maximum Uses</label>
                                <input type="number" name="max_uses" id="max_uses" 
                                       class="finance-form-control" min="1" max="100"
                                       value="{{ old('max_uses', 1) }}">
                                <small class="text-muted">How many times the link can be used</small>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="finance-card-footer mt-4">
                            <button type="submit" class="btn btn-finance btn-finance-primary btn-lg">
                                <i class="bi bi-link-45deg"></i> Generate Payment Link
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
                        <li class="mb-2">Optionally select an invoice</li>
                        <li class="mb-2">Enter the payment amount</li>
                        <li class="mb-2">Set link expiry and usage limits</li>
                        <li class="mb-2">Click "Generate Payment Link"</li>
                    </ol>
                    <hr class="my-3">
                    <h6 class="mb-2">
                        <i class="bi bi-share me-2"></i>
                        After Creating:
                    </h6>
                    <p class="small text-muted mb-0">
                        You can send the payment link to parents via SMS or Email. Parents can click the link and pay directly using M-PESA.
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
                        <div class="mb-2">
                            <strong>Parent Phone:</strong>
                            <span class="text-muted">{{ $student->family->phone ?? 'N/A' }}</span>
                        </div>
                        <div class="mb-0">
                            <strong>Parent Email:</strong>
                            <span class="text-muted">{{ $student->family->email ?? 'N/A' }}</span>
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
        placeholder: 'Type to search student...',
        theme: 'bootstrap-5'
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

    // Form submission
    $('#createLinkForm').on('submit', function(e) {
        var btn = $(this).find('button[type="submit"]');
        btn.prop('disabled', true);
        btn.html('<i class="bi bi-hourglass-split"></i> Creating...');
    });
});
</script>
@endsection
