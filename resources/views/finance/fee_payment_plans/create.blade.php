@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    <div class="finance-card finance-animate mb-3 d-flex justify-content-between align-items-center p-3">
        <h1 class="h4 mb-0">Create Payment Plan</h1>
        <a href="{{ route('finance.fee-payment-plans.index') }}" class="btn btn-finance btn-finance-outline">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="finance-card finance-animate">
        <div class="finance-card-body">
            <form action="{{ route('finance.fee-payment-plans.store') }}" method="POST">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Student <span class="text-danger">*</span></label>
                        @include('partials.student_live_search', [
                            'hiddenInputId' => 'student_id',
                            'displayInputId' => 'studentLiveSearchFPP',
                            'resultsId' => 'studentLiveResultsFPP',
                            'placeholder' => 'Type name or admission #',
                            'initialLabel' => old('student_id') ? (optional(\App\Models\Student::find(old('student_id')))->full_name . ' (' . optional(\App\Models\Student::find(old('student_id')))->admission_number . ')') : ''
                        ])
                        @error('student_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Link to invoice (optional)</label>
                        <select name="invoice_id" id="invoice_id" class="form-select">
                            <option value="">-- Select student first --</option>
                        </select>
                        <small class="text-muted">Select a student to see their invoices. Linking helps track which invoice this plan covers.</small>
                        @error('invoice_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="form-check">
                            <input type="checkbox" name="apply_to_siblings" id="apply_to_siblings" value="1" class="form-check-input" {{ old('apply_to_siblings') ? 'checked' : '' }}>
                            <label class="form-check-label" for="apply_to_siblings">Create same schedule for all siblings (one plan per sibling, same dates)</label>
                        </div>
                        <small class="text-muted">Money from one source: one plan per sibling with the same installment dates. Each sibling's plan total = their outstanding balance.</small>
                        <div id="siblings_preview" class="mt-2 small text-muted d-none"></div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Total Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control @error('total_amount') is-invalid @enderror" 
                               value="{{ old('total_amount') }}" required>
                        <small class="text-muted">For the selected student. Siblings will use their own outstanding balance.</small>
                        @error('total_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Number of Installments <span class="text-danger">*</span></label>
                        <input type="number" name="installment_count" class="form-control @error('installment_count') is-invalid @enderror" 
                               value="{{ old('installment_count', 3) }}" min="2" max="12" required>
                        @error('installment_count')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" 
                               value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                               value="{{ old('end_date') }}" required readonly>
                        <small class="form-text text-muted">Automatically calculated based on start date and installments</small>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="3">{{ old('notes') }}</textarea>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('finance.fee-payment-plans.index') }}" class="btn btn-finance btn-finance-outline">Cancel</a>
                    <button type="submit" class="btn btn-finance btn-finance-primary">Create Payment Plan</button>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDateInput = document.getElementById('start_date');
    const installmentCountInput = document.querySelector('input[name="installment_count"]');
    const endDateInput = document.getElementById('end_date');
    const studentIdInput = document.getElementById('student_id');
    const invoiceSelect = document.getElementById('invoice_id');
    const applyToSiblingsCheck = document.getElementById('apply_to_siblings');
    const siblingsPreview = document.getElementById('siblings_preview');
    const baseUrl = '{{ url("/") }}';
    const financePrefix = '{{ request()->routeIs("finance.*") ? "" : "" }}';

    function calculateEndDate() {
        const startDate = startDateInput.value;
        const installmentCount = parseInt(installmentCountInput.value) || 3;
        if (startDate && installmentCount >= 2) {
            const start = new Date(startDate);
            const end = new Date(start);
            end.setMonth(end.getMonth() + (installmentCount - 1));
            const year = end.getFullYear();
            const month = String(end.getMonth() + 1).padStart(2, '0');
            const day = String(end.getDate()).padStart(2, '0');
            endDateInput.value = `${year}-${month}-${day}`;
        }
    }

    function loadInvoicesAndSiblings(studentId) {
        if (!studentId) {
            invoiceSelect.innerHTML = '<option value="">-- Select student first --</option>';
            siblingsPreview.classList.add('d-none');
            return;
        }
        invoiceSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`${baseUrl}/finance/fee-payment-plans/student-invoices/${studentId}`)
            .then(r => r.json())
            .then(data => {
                invoiceSelect.innerHTML = '<option value="">-- No invoice link --</option>';
                (data.invoices || []).forEach(inv => {
                    const opt = document.createElement('option');
                    opt.value = inv.id;
                    opt.textContent = `${inv.invoice_number || 'Inv'} - KES ${parseFloat(inv.total || 0).toFixed(2)} (balance: ${parseFloat(inv.balance || 0).toFixed(2)})`;
                    invoiceSelect.appendChild(opt);
                });
                if (data.siblings && data.siblings.length > 0) {
                    applyToSiblingsCheck.closest('.row').querySelector('.form-check').classList.remove('d-none');
                    if (applyToSiblingsCheck.checked) {
                        siblingsPreview.classList.remove('d-none');
                        siblingsPreview.innerHTML = 'Plans will also be created for: ' + data.siblings.map(s => `${s.name} (KES ${s.total_outstanding})`).join(', ');
                    }
                } else {
                    siblingsPreview.classList.add('d-none');
                }
            })
            .catch(() => {
                invoiceSelect.innerHTML = '<option value="">-- Error loading --</option>';
            });
    }

    studentIdInput.addEventListener('change', function() {
        loadInvoicesAndSiblings(this.value);
    });
    window.addEventListener('student-selected', function(e) {
        if (e.detail && e.detail.id) {
            loadInvoicesAndSiblings(e.detail.id);
        }
    });
    applyToSiblingsCheck.addEventListener('change', function() {
        const sid = studentIdInput.value;
        if (!sid || !this.checked) {
            siblingsPreview.classList.add('d-none');
            return;
        }
        fetch(`${baseUrl}/finance/fee-payment-plans/student-invoices/${sid}`)
            .then(r => r.json())
            .then(data => {
                if (data.siblings && data.siblings.length > 0) {
                    siblingsPreview.classList.remove('d-none');
                    siblingsPreview.innerHTML = 'Plans will also be created for: ' + data.siblings.map(s => `${s.name} (KES ${s.total_outstanding})`).join(', ');
                } else {
                    siblingsPreview.classList.add('d-none');
                }
            });
    });

    startDateInput.addEventListener('change', calculateEndDate);
    installmentCountInput.addEventListener('input', calculateEndDate);
    if (startDateInput.value) calculateEndDate();
    if (studentIdInput.value) loadInvoicesAndSiblings(studentIdInput.value);
});
</script>
@endpush
@endsection

