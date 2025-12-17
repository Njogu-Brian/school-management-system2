<div class="tab-pane fade show" id="placeholders" role="tabpanel">
    <h5 class="mb-3">📍 Communication Placeholders</h5>

    <form method="POST" action="{{ route('settings.placeholders.store') }}" class="row g-3 mb-4">
        @csrf
        <div class="col-md-5">
            <input type="text" name="key" class="form-control" placeholder="e.g. principal_name" required>
        </div>
        <div class="col-md-5">
            <input type="text" name="value" class="form-control" placeholder="e.g. Mr. Brian Murathime" required>
        </div>
        <div class="col-md-2">
            <button class="btn btn-success w-100"><i class="bi bi-plus-circle"></i> Add</button>
        </div>
    </form>

    <h6 class="mt-4">Built-in Placeholders</h6>
    
    @php
        $groupedPlaceholders = collect($systemPlaceholders)->groupBy(function($ph) {
            // Group by category
            if (in_array($ph['key'], ['school_name', 'school_phone', 'date'])) {
                return 'general';
            } elseif (in_array($ph['key'], ['student_name', 'admission_number', 'class_name', 'parent_name', 'father_name'])) {
                return 'student';
            } elseif (in_array($ph['key'], ['staff_name'])) {
                return 'staff';
            } elseif (in_array($ph['key'], ['receipt_number', 'transaction_code', 'payment_date', 'amount', 'receipt_link'])) {
                return 'receipts';
            } elseif (in_array($ph['key'], ['invoice_number', 'total_amount', 'due_date', 'outstanding_amount', 'status', 'invoice_link', 'days_overdue'])) {
                return 'invoices';
            } elseif (in_array($ph['key'], ['installment_count', 'installment_amount', 'installment_number', 'payment_plan_link', 'start_date', 'end_date', 'remaining_installments'])) {
                return 'payment_plans';
            } elseif (in_array($ph['key'], ['custom_message', 'custom_subject'])) {
                return 'custom';
            }
            return 'other';
        });
    @endphp

    @foreach(['general' => 'General', 'student' => 'Student & Parent', 'staff' => 'Staff', 'receipts' => 'Receipts', 'invoices' => 'Invoices & Reminders', 'payment_plans' => 'Payment Plans', 'custom' => 'Custom Finance', 'other' => 'Other'] as $category => $categoryName)
        @if($groupedPlaceholders->has($category) && $groupedPlaceholders[$category]->count() > 0)
            <div class="mb-4">
                <h6 class="text-primary mb-2">
                    @if($category === 'general') 📌
                    @elseif($category === 'student') 👤
                    @elseif($category === 'staff') 👨‍💼
                    @elseif($category === 'receipts') 🧾
                    @elseif($category === 'invoices') 📄
                    @elseif($category === 'payment_plans') 💳
                    @elseif($category === 'custom') ✉️
                    @else 📋
                    @endif
                    {{ $categoryName }}
                </h6>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th style="width: 50px;">#</th>
                            <th>Placeholder</th>
                            <th>Example Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($groupedPlaceholders[$category] as $i => $ph)
                            @php
                                $placeholder = '{{' . $ph['key'] . '}}';
                            @endphp
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td><code>{{ $placeholder }}</code></td>
                                <td>{{ $ph['value'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @endforeach

    <h6 class="mt-4">Custom Placeholders</h6>
    <table class="table table-bordered">
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
                @php
                    $placeholder = '{{' . $p->key . '}}';
                @endphp
                <tr>
                    <td>{{ $i+1 }}</td>
                    <td><code>{{ $placeholder }}</code></td>
                    <td>{{ $p->value }}</td>
                    <td><code>{{ $placeholder }}</code></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted">No custom placeholders added yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
