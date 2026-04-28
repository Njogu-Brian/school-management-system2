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
            <div class="alert alert-light border d-flex align-items-start gap-2 mb-3">
                <div class="mt-1">
                    <i class="bi bi-info-circle"></i>
                </div>
                <div>
                    <div class="fw-semibold">Tip</div>
                    <div class="small text-muted">Use the student search to pull all outstanding family invoices. The system will compute the combined total and you can adjust the schedule (monthly / weekly / custom).</div>
                </div>
            </div>
            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <div class="fw-semibold mb-1">Could not create payment plan:</div>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div id="payment_plan_js_error" class="alert alert-danger d-none">
                <div class="fw-semibold mb-1">Page error</div>
                <div class="small" id="payment_plan_js_error_msg"></div>
            </div>
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
                        <div class="alert border-0 mb-0"
                             style="background: color-mix(in srgb, var(--brand-accent, #14b8a6) 14%, transparent);">
                            <div class="d-flex align-items-start gap-2">
                                <div class="mt-1"><i class="bi bi-people"></i></div>
                                <div>
                                    <div class="fw-semibold">Family plan behavior</div>
                                    <div class="small text-muted mb-0">When the student has siblings, the system builds <span class="fw-semibold">one combined payment plan</span> for the whole family by combining all outstanding invoices.</div>
                                </div>
                            </div>
                        </div>
                        <div id="siblings_preview" class="mt-3 d-none"></div>
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
                    <div id="custom_installments_notice" class="alert alert-warning py-2 small d-none mb-2"></div>
                    @error('installments')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror
                    @error('installments.*.due_date')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror
                    @error('installments.*.amount')
                        <div class="text-danger small mb-2">{{ $message }}</div>
                    @enderror
                    <div id="custom_installments_list"></div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add_custom_installment"><i class="bi bi-plus"></i> Add installment</button>
                    <small class="text-muted d-block mt-2">Tip: enter amounts for the first rows; the last row will auto-adjust to ensure the full total is covered.</small>
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
    const showJsError = (msg) => {
        const wrap = document.getElementById('payment_plan_js_error');
        const el = document.getElementById('payment_plan_js_error_msg');
        if (wrap && el) {
            el.textContent = msg;
            wrap.classList.remove('d-none');
        }
    };

    // Catch any runtime JS errors and show them on the page (prevents "silent" failures).
    window.addEventListener('error', function(ev) {
        try {
            const msg = ev?.message ? `Payment plan page error: ${ev.message}` : 'Payment plan page error. Please refresh and try again.';
            console.error('Payment plan window error:', ev?.error || ev);
            showJsError(msg);
        } catch (_) {}
    });
    window.addEventListener('unhandledrejection', function(ev) {
        try {
            const reason = ev?.reason;
            const msg = reason?.message
                ? `Payment plan page error: ${reason.message}`
                : 'Payment plan page error. Please refresh and try again.';
            console.error('Payment plan unhandled rejection:', reason);
            showJsError(msg);
        } catch (_) {}
    });

    try {
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
        const customNotice = document.getElementById('custom_installments_notice');
        const form = document.querySelector('form[action="{{ route('finance.fee-payment-plans.store') }}"]') || document.querySelector('form');
        const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

        const required = [
            ['start_date', startDateInput],
            ['installment_count', installmentCountInput],
            ['end_date', endDateInput],
            ['schedule_type', scheduleTypeSelect],
            ['installment_schedule_block', installmentScheduleBlock],
            ['custom_installments_block', customInstallmentsBlock],
            ['custom_installments_list', customInstallmentsList],
            ['add_custom_installment', addCustomInstallmentBtn],
            ['student_id', studentIdInput],
            ['invoice_id', invoiceSelect],
            ['siblings_preview', siblingsPreview],
            ['total_amount', totalAmountInput],
            ['custom_installments_notice', customNotice],
            ['form', form],
        ];
        const missing = required.filter(([, el]) => !el).map(([id]) => id);
        if (missing.length) {
            const msg = `Payment plan page failed to initialize (missing: ${missing.join(', ')}). Please refresh; if it persists contact support.`;
            console.error(msg);
            showJsError(msg);
            return;
        }

        const studentInvoicesUrlTemplate = '{{ route("finance.fee-payment-plans.student-invoices", ["student" => 0]) }}';
        function studentInvoicesUrl(id) { return studentInvoicesUrlTemplate.replace(/\/0$/, '/' + id); }

        const setSubmittingUi = (isSubmitting) => {
            if (!submitBtn) return;
            if (isSubmitting) {
                submitBtn.dataset.originalText = submitBtn.dataset.originalText || submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Creating...';
            } else {
                submitBtn.disabled = false;
                if (submitBtn.dataset.originalText) submitBtn.textContent = submitBtn.dataset.originalText;
            }
        };

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
        row.innerHTML = '<div class="col-md-5"><input type="date" required name="installments[' + customInstallmentIndex + '][due_date]" class="form-control form-control-sm" value="' + (dueDate || '') + '"></div>' +
            '<div class="col-md-5"><input type="number" required step="0.01" min="0" name="installments[' + customInstallmentIndex + '][amount]" class="form-control form-control-sm installment-amount" placeholder="Amount" value="' + (amount || '') + '"></div>' +
            '<div class="col-md-2"><button type="button" class="btn btn-sm btn-outline-danger remove-custom-installment"><i class="bi bi-dash"></i></button></div>';
        customInstallmentsList.appendChild(row);
        customInstallmentIndex++;
        row.querySelector('.remove-custom-installment').addEventListener('click', function() {
            row.remove();
            recalcCustomInstallments(true);
        });
        row.querySelector('input.installment-amount').addEventListener('input', function() {
            recalcCustomInstallments(true);
        });
        recalcCustomInstallments(true);
    }
    addCustomInstallmentBtn.addEventListener('click', function() { addCustomInstallmentRow('', ''); });
    scheduleTypeSelect.addEventListener('change', refreshScheduleVisibility);
    refreshScheduleVisibility();

    function getTotalAmount() {
        const v = parseFloat(totalAmountInput.value || '0');
        return isNaN(v) ? 0 : v;
    }

    function recalcCustomInstallments(autoAdjustLast) {
        if (scheduleTypeSelect.value !== 'custom') return;

        const rows = Array.from(customInstallmentsList.querySelectorAll('.custom-installment-row'));
        const total = getTotalAmount();
        if (rows.length === 0) return;

        const amounts = rows.map(r => {
            const input = r.querySelector('input.installment-amount');
            const val = parseFloat(input?.value || '0');
            return isNaN(val) ? 0 : val;
        });

        const sumExceptLast = amounts.slice(0, -1).reduce((a, b) => a + b, 0);
        const lastInput = rows[rows.length - 1].querySelector('input.installment-amount');
        if (autoAdjustLast && lastInput) {
            const remainder = Math.max(0, Math.round((total - sumExceptLast) * 100) / 100);
            lastInput.value = remainder.toFixed(2);
            amounts[amounts.length - 1] = remainder;
        }

        const sum = amounts.reduce((a, b) => a + b, 0);
        const diff = Math.round((total - sum) * 100) / 100;

        if (total > 0 && Math.abs(diff) > 0.009) {
            customNotice.classList.remove('d-none');
            customNotice.classList.remove('alert-success');
            customNotice.classList.add('alert-warning');
            customNotice.textContent = `Custom installments total KES ${sum.toFixed(2)}. Difference: KES ${diff.toFixed(2)}. Adjust amounts so the total equals KES ${total.toFixed(2)}.`;
        } else {
            customNotice.classList.remove('d-none');
            customNotice.classList.remove('alert-warning');
            customNotice.classList.add('alert-success');
            customNotice.textContent = `Custom installments cover the full amount: KES ${sum.toFixed(2)}.`;
        }
    }

    totalAmountInput.addEventListener('input', function() {
        if (scheduleTypeSelect.value === 'custom') {
            recalcCustomInstallments(true);
        }
    });

    form.addEventListener('submit', function(e) {
        setSubmittingUi(true);
        if (scheduleTypeSelect.value === 'custom') {
            // Re-index fields, then force last row to cover remainder.
            customInstallmentsList.querySelectorAll('.custom-installment-row').forEach(function(row, i) {
                const due = row.querySelector('input[name*="[due_date]"]');
                const amt = row.querySelector('input[name*="[amount]"]');
                if (due) due.name = 'installments[' + i + '][due_date]';
                if (amt) amt.name = 'installments[' + i + '][amount]';
            });

            recalcCustomInstallments(true);

            // Validate equality after adjustment.
            const total = getTotalAmount();
            const amounts = Array.from(customInstallmentsList.querySelectorAll('input.installment-amount'))
                .map(x => parseFloat(x.value || '0'))
                .map(x => isNaN(x) ? 0 : x);
            const sum = amounts.reduce((a, b) => a + b, 0);
            const diff = Math.round((total - sum) * 100) / 100;
            if (total > 0 && Math.abs(diff) > 0.009) {
                e.preventDefault();
                customNotice.classList.remove('d-none');
                customNotice.classList.remove('alert-success');
                customNotice.classList.add('alert-warning');
                customNotice.textContent = `Cannot submit: custom installments must equal the Total Amount (KES ${total.toFixed(2)}). Current sum: KES ${sum.toFixed(2)}.`;
            }
        }

        // If client-side validation blocked submit, restore the button.
        if (e.defaultPrevented) {
            setSubmittingUi(false);
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

                // Grouped select: invoices grouped by student (siblings).
                const familyStudents = data.family_students || [];
                if (familyStudents.length > 0) {
                    familyStudents.forEach(stu => {
                        const og = document.createElement('optgroup');
                        const adm = stu.admission_number ? ` (${stu.admission_number})` : '';
                        const outstanding = parseFloat(stu.total_outstanding || 0).toFixed(2);
                        og.label = `${stu.student_name || 'Student'}${adm} — Outstanding: KES ${outstanding}`;
                        (stu.invoices || []).forEach(inv => {
                            const opt = document.createElement('option');
                            opt.value = inv.id;
                            opt.textContent = `${inv.invoice_number || 'Inv'} • total KES ${parseFloat(inv.total || 0).toFixed(2)} • balance KES ${parseFloat(inv.balance || 0).toFixed(2)}`;
                            og.appendChild(opt);
                        });
                        invoiceSelect.appendChild(og);
                    });
                } else {
                    (data.invoices || []).forEach(inv => {
                        const opt = document.createElement('option');
                        opt.value = inv.id;
                        opt.textContent = `${inv.invoice_number || 'Inv'} - KES ${parseFloat(inv.total || 0).toFixed(2)} (balance: ${parseFloat(inv.balance || 0).toFixed(2)})`;
                        invoiceSelect.appendChild(opt);
                    });
                }

                if (data.combined_total != null && data.combined_total > 0) {
                    totalAmountInput.value = parseFloat(data.combined_total).toFixed(2);
                    recalcCustomInstallments(true);
                }

                if (familyStudents.length > 0) {
                    siblingsPreview.classList.remove('d-none');
                    const combined = parseFloat(data.combined_total || 0).toFixed(2);

                        const cards = familyStudents.map(stu => {
                        const adm = stu.admission_number ? `<span class="text-muted">(${stu.admission_number})</span>` : '';
                        const total = parseFloat(stu.total_invoice_amount || 0).toFixed(2);
                        const out = parseFloat(stu.total_outstanding || 0).toFixed(2);
                        const invBadges = (stu.invoices || []).map(inv => {
                            const invNo = (inv.invoice_number || 'Inv');
                            const invTotal = parseFloat(inv.total || 0).toFixed(2);
                            const invBal = parseFloat(inv.balance || 0).toFixed(2);
                            return `<span class="badge bg-light text-dark border me-1 mb-1">${invNo}: total ${invTotal}, bal ${invBal}</span>`;
                        }).join('');

                        return `
                          <div class="col-lg-6">
                            <div class="p-3 rounded-3 h-100"
                                 style="background: var(--brand-surface, #fff); border: 1px solid var(--brand-border, #e5e7eb); box-shadow: 0 10px 30px rgba(0,0,0,.06);">
                                <div class="d-flex justify-content-between align-items-start gap-2">
                                  <div>
                                    <div class="fw-semibold">${stu.student_name || 'Student'} ${adm}</div>
                                    <div class="small text-muted">Outstanding invoices total: KES ${total} · Outstanding balance: KES ${out}</div>
                                  </div>
                                  <span class="badge"
                                        style="background: color-mix(in srgb, var(--brand-primary, #0f766e) 12%, transparent); color: var(--brand-primary, #0f766e); border: 1px solid color-mix(in srgb, var(--brand-primary, #0f766e) 25%, transparent);">
                                    Student
                                  </span>
                                </div>
                                <div class="mt-2 small">${invBadges || '<span class="text-muted">No outstanding invoices for this student</span>'}</div>
                            </div>
                          </div>
                        `;
                    }).join('');

                    siblingsPreview.innerHTML = `
                      <div class="p-3 rounded-3"
                           style="background: var(--brand-surface, #fff); border: 1px solid var(--brand-border, #e5e7eb); box-shadow: 0 10px 30px rgba(0,0,0,.06);">
                          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <div>
                              <div class="fw-semibold">Family invoices included in this plan</div>
                              <div class="small text-muted">The total amount will be set to the combined outstanding balance.</div>
                            </div>
                            <div class="text-end">
                              <div class="small text-muted">Combined outstanding</div>
                              <div class="h5 mb-0" style="color: var(--brand-primary, #0f766e);">KES ${combined}</div>
                            </div>
                          </div>
                          <div class="row g-3 mt-1">
                            ${cards}
                          </div>
                      </div>
                    `;
                } else {
                    siblingsPreview.classList.add('d-none');
                    siblingsPreview.innerHTML = '';
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

    // If server validation failed and user was on custom schedule, show the custom block immediately.
    if (scheduleTypeSelect.value === 'custom') {
        customNotice.classList.remove('d-none');
        recalcCustomInstallments(true);
    }
    } catch (err) {
        console.error('Payment plan create page JS crashed:', err);
        showJsError(err?.message ? `Payment plan page error: ${err.message}` : 'Payment plan page error. Please refresh and try again.');
    }
});
</script>
@endpush
@endsection

