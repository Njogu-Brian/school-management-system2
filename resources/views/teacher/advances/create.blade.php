@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('teacher.advances.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Back to requests
        </a>
        <h1 class="h4 mb-0">Request Salary Advance</h1>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('teacher.advances.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Amount (KES) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" step="0.01" min="0.01" class="form-control" value="{{ old('amount') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Advance Date <span class="text-danger">*</span></label>
                        <input type="date" name="advance_date" class="form-control" value="{{ old('advance_date', now()->toDateString()) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Repayment Method <span class="text-danger">*</span></label>
                        <select name="repayment_method" class="form-select" required id="repaymentMethodSelect">
                            @foreach(['lump_sum' => 'Lump Sum', 'installments' => 'Fixed Installments', 'monthly_deduction' => 'Monthly Deduction'] as $key => $label)
                                <option value="{{ $key }}" @selected(old('repayment_method') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 repayment-field" data-method="installments">
                        <label class="form-label">Number of Installments</label>
                        <input type="number" name="installment_count" class="form-control" min="1" value="{{ old('installment_count') }}">
                        <small class="text-muted">Only required if paying via fixed installments.</small>
                    </div>
                    <div class="col-md-6 repayment-field" data-method="monthly_deduction">
                        <label class="form-label">Monthly Deduction Amount</label>
                        <input type="number" name="monthly_deduction_amount" class="form-control" step="0.01" min="0.01" value="{{ old('monthly_deduction_amount') }}">
                        <small class="text-muted">Specify the amount to deduct from each payroll.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expected Completion Date</label>
                        <input type="date" name="expected_completion_date" class="form-control" value="{{ old('expected_completion_date') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Purpose</label>
                        <input type="text" name="purpose" class="form-control" value="{{ old('purpose') }}" placeholder="Reason for the advance">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Additional Details</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optional details">{{ old('description') }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes to Finance Team</label>
                        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('teacher.advances.index') }}" class="btn btn-light">Cancel</a>
                    <button class="btn btn-primary">
                        <i class="bi bi-send"></i> Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('repaymentMethodSelect');
        const toggleFields = () => {
            document.querySelectorAll('.repayment-field').forEach(field => {
                const method = field.getAttribute('data-method');
                field.style.display = (select.value === method) ? 'block' : 'none';
            });
        };
        select.addEventListener('change', toggleFields);
        toggleFields();
    });
</script>
@endpush
@endsection

