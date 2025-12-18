<div class="tab-pane fade" id="tab-placeholders" role="tabpanel">
    <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Communication Placeholders</h5>
                <div class="section-note">Merge fields available in email, SMS, and receipts.</div>
            </div>
            <span class="pill-badge"><i class="bi bi-braces"></i> Dynamic</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('settings.placeholders.store') }}" class="row g-3 mb-4">
                @csrf
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Placeholder Key</label>
                    <input type="text" name="key" class="form-control" placeholder="e.g. principal_name" required>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-semibold">Example Value</label>
                    <input type="text" name="value" class="form-control" placeholder="e.g. Mr. Brian Murathime" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-settings-primary w-100"><i class="bi bi-plus-circle"></i> Add</button>
                </div>
            </form>

            @php
                $groupedPlaceholders = collect($systemPlaceholders)->groupBy(function($ph) {
                    if (in_array($ph['key'], ['school_name', 'school_phone', 'date'])) return 'general';
                    if (in_array($ph['key'], ['student_name', 'admission_number', 'class_name', 'parent_name', 'father_name'])) return 'student';
                    if (in_array($ph['key'], ['staff_name'])) return 'staff';
                    if (in_array($ph['key'], ['receipt_number', 'transaction_code', 'payment_date', 'amount', 'receipt_link'])) return 'receipts';
                    if (in_array($ph['key'], ['invoice_number', 'total_amount', 'due_date', 'outstanding_amount', 'status', 'invoice_link', 'days_overdue'])) return 'invoices';
                    if (in_array($ph['key'], ['installment_count', 'installment_amount', 'installment_number', 'payment_plan_link', 'start_date', 'end_date', 'remaining_installments'])) return 'payment_plans';
                    if (in_array($ph['key'], ['custom_message', 'custom_subject'])) return 'custom';
                    return 'other';
                });
            @endphp

            @foreach(['general' => 'General', 'student' => 'Student & Parent', 'staff' => 'Staff', 'receipts' => 'Receipts', 'invoices' => 'Invoices & Reminders', 'payment_plans' => 'Payment Plans', 'custom' => 'Custom Finance', 'other' => 'Other'] as $category => $categoryName)
                @if($groupedPlaceholders->has($category) && $groupedPlaceholders[$category]->count() > 0)
                    <div class="mb-4 settings-card">
                        <div class="card-header d-flex align-items-center gap-2">
                            <h6 class="mb-0">{{ $categoryName }}</h6>
                            <span class="input-chip">{{ $groupedPlaceholders[$category]->count() }} placeholders</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-modern placeholder-table mb-0">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">#</th>
                                            <th>Placeholder</th>
                                            <th>Example Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($groupedPlaceholders[$category] as $i => $ph)
                                            @php $placeholder = '{{' . $ph['key'] . '}}'; @endphp
                                            <tr>
                                                <td>{{ $i+1 }}</td>
                                                <td><code>{{ $placeholder }}</code></td>
                                                <td>{{ $ph['value'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach

            <div class="settings-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Custom Placeholders</h6>
                    <span class="input-chip">{{ $customPlaceholders->count() }} active</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-modern mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Placeholder</th>
                                    <th>Value</th>
                                    <th>Example Usage</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($customPlaceholders as $i => $p)
                                    @php $placeholder = '{{' . $p->key . '}}'; @endphp
                                    <tr>
                                        <td>{{ $i+1 }}</td>
                                        <td><code>{{ $placeholder }}</code></td>
                                        <td>{{ $p->value }}</td>
                                        <td><code>{{ $placeholder }}</code></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted py-4">No custom placeholders added yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
