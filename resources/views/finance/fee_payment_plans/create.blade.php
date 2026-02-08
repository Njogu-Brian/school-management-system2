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
                            'initialLabel' => old('student_id') ? optional(\App\Models\Student::find(old('student_id')))->search_display : ''
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
                        <p class="text-muted small mb-0">When the student has siblings, one combined payment plan is created for the family (all siblings’ invoices). Total amount = combined outstanding balance.</p>
                        <div id="siblings_preview" class="mt-2 small text-muted d-none"></div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Total Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="total_amount" id="total_amount" class="form-control @error('total_amount') is-invalid @enderror" 
                               value="{{ old('total_amount') }}" required>
                        <small class="text-muted">Combined outstanding for selected student; when they have siblings, this is the family total.</small>
                        @error('total_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Schedule <span class="text-danger">*</span></label>
                        <select name="schedule_type" id="schedule_type" class="form-select @error('schedule_type') is-invalid @enderror" required>
                            <option value="one_time" {{ old('schedule_type', 'monthly') === 'one_time' ? 'selected' : '' }}>One time</option>
                            <option value="weekly" {{ old('schedule_type') === 'weekly' ? 'selected' : '' }}>Weekly</option>
                            <option value="monthly" {{ old('schedule_type', 'monthly') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                            <option value="custom" {{ old('schedule_type') === 'custom' ? 'selected' : '' }}>Custom (set each date and amount)</option>
                        </select>
                        @error('schedule_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div id="installment_schedule_block" class="row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Number of Installments <span class="text-danger">*</span></label>
                        <input type="number" name="installment_count" id="installment_count" class="form-control @error('installment_count') is-invalid @enderror" 
                               value="{{ old('installment_count', 3) }}" min="1" max="24">
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
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" 
                               value="{{ old('end_date') }}">
                        <small class="form-text text-muted">Auto-calculated for weekly/monthly</small>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div id="custom_installments_block" class="mb-3 d-none">
                    <label class="form-label">Custom installments (date and amount per installment)</label>
                    <div id="custom_installments_list"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add_custom_installment"><i class="bi bi-plus"></i> Add installment</button>
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
    const installmentCountInput = document.getElementById('installment_count');
    const endDateInput = document.getElementById('end_date');
    const scheduleTypeSelect = document.getElementById('schedule_type');
    const installmentScheduleBlock = document.getElementById('installment_schedule_block');
    const customInstallmentsBlock = document.getElementById('custom_installments_block');
    const customInstallmentsList = document.getElementById('custom_installments_list');
    const addCustomInstallmentBtn = document.getElementById('add_custom_installment');
    const studentIdInput = document.getElementById('student_id');
    const invoiceSelect = document.getElementById('invoice_id');
    const siblingsPreview = document.getElementById('siblings_preview');
    const totalAmountInput = document.getElementById('total_amount');
    const studentInvoicesUrlTemplate = '{{ route("finance.fee-payment-plans.student-invoices", ["student" => 0]) }}';
    function studentInvoicesUrl(id) { return studentInvoicesUrlTemplate.replace(/\/0$/, '/' + id); }

    function calculateEndDate() {
        const startDate = startDateInput.value;
        const scheduleType = scheduleTypeSelect.value;
        const installmentCount = Math.max(1, parseInt(installmentCountInput.value) || 1);
        if (!startDate) return;
        const start = new Date(startDate);
        const end = new Date(start);
        if (scheduleType === 'weekly') {
            end.setDate(end.getDate() + 7 * (installmentCount - 1));
        } else if (scheduleType === 'monthly') {
            end.setMonth(end.getMonth() + (installmentCount - 1));
        } else if (scheduleType !== 'custom') {
            end.setMonth(end.getMonth() + (installmentCount - 1));
        }
        const y = end.getFullYear(), m = String(end.getMonth() + 1).padStart(2, '0'), d = String(end.getDate()).padStart(2, '0');
        endDateInput.value = y + '-' + m + '-' + d;
    }

    function refreshScheduleVisibility() {
        const scheduleType = scheduleTypeSelect.value;
        if (scheduleType === 'custom') {
            installmentScheduleBlock.classList.add('d-none');
            customInstallmentsBlock.classList.remove('d-none');
            if (installmentCountInput) installmentCountInput.removeAttribute('required');
            if (customInstallmentsList.children.length === 0) addCustomInstallmentRow('', '');
        } else {
            installmentScheduleBlock.classList.remove('d-none');
            customInstallmentsBlock.classList.add('d-none');
            if (installmentCountInput) {
                installmentCountInput.setAttribute('required', 'required');
                if (scheduleType === 'one_time') {
                    installmentCountInput.value = '1';
                    installmentCountInput.min = '1';
                } else {
                    installmentCountInput.min = '2';
                }
            }
            calculateEndDate();
        }
    }

    let customInstallmentIndex = 0;
    function addCustomInstallmentRow(dueDate, amount) {
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2 align-items-center custom-installment-row';
        row.innerHTML = '<div class="col-md-5"><input type="date" name="installments[' + customInstallmentIndex + '][due_date]" class="form-control form-control-sm" value="' + (dueDate || '') + '"></div>' +
            '<div class="col-md-5"><input type="number" step="0.01" min="0" name="installments[' + customInstallmentIndex + '][amount]" class="form-control form-control-sm" placeholder="Amount" value="' + (amount || '') + '"></div>' +
            '<div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger remove-custom-installment"><i class="bi bi-dash"></i></button></div>';
        customInstallmentsList.appendChild(row);
        customInstallmentIndex++;
        row.querySelector('.remove-custom-installment').addEventListener('click', function() { row.remove(); });
    }
    addCustomInstallmentBtn.addEventListener('click', function() { addCustomInstallmentRow('', ''); });
    scheduleTypeSelect.addEventListener('change', refreshScheduleVisibility);
    refreshScheduleVisibility();

    document.querySelector('form').addEventListener('submit', function() {
        if (scheduleTypeSelect.value === 'custom') {
            customInstallmentsList.querySelectorAll('.custom-installment-row').forEach(function(row, i) {
                row.querySelector('input[name*="[due_date]"]').name = 'installments[' + i + '][due_date]';
                row.querySelector('input[name*="[amount]"]').name = 'installments[' + i + '][amount]';
            });
        }
    });

    function loadInvoicesAndSiblings(studentId) {
        if (!studentId) {
            invoiceSelect.innerHTML = '<option value="">-- Select student first --</option>';
            siblingsPreview.classList.add('d-none');
            return;
        }
        invoiceSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(studentInvoicesUrl(studentId))
            .then(r => r.json())
            .then(data => {
                invoiceSelect.innerHTML = '<option value="">-- No invoice link --</option>';
                (data.invoices || []).forEach(inv => {
                    const opt = document.createElement('option');
                    opt.value = inv.id;
                    opt.textContent = `${inv.invoice_number || 'Inv'} - KES ${parseFloat(inv.total || 0).toFixed(2)} (balance: ${parseFloat(inv.balance || 0).toFixed(2)})`;
                    invoiceSelect.appendChild(opt);
                });
                if (data.combined_total != null && data.combined_total > 0) {
                    totalAmountInput.value = parseFloat(data.combined_total).toFixed(2);
                }
                if (data.siblings && data.siblings.length > 0) {
                    siblingsPreview.classList.remove('d-none');
                    siblingsPreview.innerHTML = 'One combined plan for family: ' + data.siblings.map(s => s.name + ' (KES ' + (s.total_outstanding || 0).toFixed(2) + ')').join(', ') + '. Total amount set above.';
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
    startDateInput.addEventListener('change', calculateEndDate);
    if (installmentCountInput) installmentCountInput.addEventListener('input', calculateEndDate);
    if (startDateInput.value) calculateEndDate();
    if (studentIdInput.value) loadInvoicesAndSiblings(studentIdInput.value);
});
</script>
@endpush
@endsection

