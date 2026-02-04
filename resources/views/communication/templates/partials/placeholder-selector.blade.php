@php
    $groupedPlaceholders = collect($systemPlaceholders)->groupBy(function($ph) {
        if (in_array($ph['key'], ['school_name', 'school_phone', 'date'])) {
            return 'general';
        } elseif (in_array($ph['key'], ['student_name', 'admission_number', 'class_name', 'class', 'parent_name', 'father_name'])) {
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

<div class="card border-primary">
    <div class="card-body">
        <h6 class="card-title mb-3">
            <i class="bi bi-tags"></i> Available Placeholders
            <small class="text-muted">(Click to insert)</small>
        </h6>
        
        <div class="row g-2">
            @foreach(['general' => 'General', 'student' => 'Student & Parent', 'staff' => 'Staff', 'receipts' => 'Receipts', 'invoices' => 'Invoices & Reminders', 'payment_plans' => 'Payment Plans', 'custom' => 'Custom Finance', 'other' => 'Other'] as $category => $categoryName)
                @if($groupedPlaceholders->has($category) && $groupedPlaceholders[$category]->count() > 0)
                    <div class="col-md-6 mb-3">
                        <strong class="text-primary small">
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
                        </strong>
                        <div class="d-flex flex-wrap gap-1 mt-1">
                            @foreach($groupedPlaceholders[$category] as $ph)
                                @php
                                    $placeholder = '{{' . $ph['key'] . '}}';
                                @endphp
                                <button type="button" 
                                        class="btn btn-sm btn-outline-secondary placeholder-btn" 
                                        data-placeholder="{{ $placeholder }}"
                                        data-target="{{ $targetField }}"
                                        title="{{ $ph['value'] ?? $ph['key'] }}">
                                    <code>{{ $placeholder }}</code>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        @if(isset($customPlaceholders) && $customPlaceholders->count() > 0)
            <hr>
            <div class="mb-2">
                <strong class="text-success small">⭐ Custom Placeholders</strong>
                <div class="d-flex flex-wrap gap-1 mt-1">
                    @foreach($customPlaceholders as $p)
                        @php
                            $placeholder = '{{' . $p->key . '}}';
                        @endphp
                        <button type="button" 
                                class="btn btn-sm btn-outline-success placeholder-btn" 
                                data-placeholder="{{ $placeholder }}"
                                data-target="{{ $targetField }}"
                                title="{{ $p->value }}">
                            <code>{{ $placeholder }}</code>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle placeholder button clicks
    document.querySelectorAll('.placeholder-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const placeholder = this.getAttribute('data-placeholder');
            const targetField = this.getAttribute('data-target');
            const field = document.getElementById(targetField);
            
            if (field) {
                // For textarea (content field)
                if (field.tagName === 'TEXTAREA') {
                    const start = field.selectionStart;
                    const end = field.selectionEnd;
                    const text = field.value;
                    const before = text.substring(0, start);
                    const after = text.substring(end, text.length);
                    field.value = before + placeholder + after;
                    // Set cursor position after inserted placeholder
                    field.selectionStart = field.selectionEnd = start + placeholder.length;
                } else {
                    // For input (subject field)
                    const start = field.selectionStart;
                    const end = field.selectionEnd;
                    const text = field.value;
                    const before = text.substring(0, start);
                    const after = text.substring(end, text.length);
                    field.value = before + placeholder + after;
                    // Set cursor position after inserted placeholder
                    field.selectionStart = field.selectionEnd = start + placeholder.length;
                }
                
                // Focus the field
                field.focus();
                
                // Visual feedback
                btn.classList.add('btn-primary');
                btn.classList.remove('btn-outline-secondary', 'btn-outline-success');
                setTimeout(function() {
                    btn.classList.remove('btn-primary');
                    btn.classList.add(btn.getAttribute('data-placeholder').includes('custom') ? 'btn-outline-success' : 'btn-outline-secondary');
                }, 300);
            }
        });
    });
});
</script>

<style>
.placeholder-btn {
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
    transition: all 0.2s;
}

.placeholder-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.placeholder-btn code {
    background: transparent;
    padding: 0;
    font-size: 0.9em;
}
</style>

