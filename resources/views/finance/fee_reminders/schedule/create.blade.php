@extends('layouts.app')

@section('content')
@php $classes = $classrooms; @endphp
@include('communication.partials.student-selector-modal')

<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Schedule Custom Fee Communication',
        'icon' => 'bi bi-calendar-event',
        'subtitle' => 'Target parents, set filters, and schedule automatic fee reminders',
        'actions' => '<a href="' . route('finance.fee-reminders.schedule.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="finance-card finance-animate">
        <div class="finance-card-header">
            <i class="bi bi-send"></i> Schedule Settings
        </div>
        <div class="finance-card-body">
            <form action="{{ route('finance.fee-reminders.schedule.store') }}" method="POST" id="scheduleFeeForm">
                @csrf

                {{-- Recipients --}}
                <div class="form-section mb-4">
                    <h6 class="form-section-header mb-3">
                        <i class="bi bi-people-fill me-2"></i> Recipients
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="finance-form-label">Target <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="target" id="target_one_parent" value="one_parent" {{ old('target') == 'one_parent' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="target_one_parent">One parent</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="target" id="target_specific_students" value="specific_students" {{ old('target') == 'specific_students' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="target_specific_students">Specific students</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="target" id="target_class" value="class" {{ old('target') == 'class' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="target_class">Class(es)</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="target" id="target_all" value="all" {{ old('target', 'all') == 'all' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="target_all">All parents</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 target-field target-one_parent d-none mt-2">
                        <div class="col-md-6">
                            <label class="finance-form-label">Student <span class="text-danger">*</span></label>
                            @include('partials.student_live_search', [
                                'hiddenInputId' => 'student_id',
                                'displayInputId' => 'scheduleStudentSearch',
                                'resultsId' => 'scheduleStudentResults',
                                'placeholder' => 'Type name or admission #',
                                'inputClass' => 'finance-form-control' . ($errors->has('student_id') ? ' is-invalid' : ''),
                                'initialLabel' => old('student_id') ? optional(\App\Models\Student::find(old('student_id')))->search_display : '',
                            ])
                            @error('student_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 target-field target-specific_students d-none mt-2">
                        <div class="col-md-12">
                            <label class="finance-form-label">Select Students <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-finance btn-finance-outline w-100" data-bs-toggle="modal" data-bs-target="#studentSelectorModal">
                                <i class="bi bi-people-fill"></i> Open Student Selector
                            </button>
                            <input type="hidden" name="selected_student_ids" id="selectedStudentIds" value="{{ old('selected_student_ids') }}">
                            <div id="selectedStudentsDisplay" class="mt-2 d-none">
                                <small class="finance-muted fw-semibold">Selected: </small>
                                <span class="finance-badge" id="selectedStudentsBadge">0</span>
                                <div id="selectedStudentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
                            </div>
                            @error('selected_student_ids')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 target-field target-class d-none mt-2">
                        <div class="col-md-6">
                            <label class="finance-form-label">Classroom(s) <span class="text-danger">*</span></label>
                            <select name="classroom_ids[]" class="finance-form-select" multiple size="5">
                                @foreach($classrooms as $c)
                                    <option value="{{ $c->id }}" {{ in_array($c->id, old('classroom_ids', [])) ? 'selected' : '' }}>{{ $c->name }}</option>
                                @endforeach
                            </select>
                            <small class="finance-muted">Hold Ctrl/Cmd to select multiple</small>
                            @error('classroom_ids')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Filter by fee type --}}
                <div class="form-section mb-4">
                    <h6 class="form-section-header mb-3">
                        <i class="bi bi-funnel me-2"></i> Filter by fee type
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_all" value="all" {{ old('filter_type', 'all') == 'all' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="filter_all">All</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_outstanding" value="outstanding_fees" {{ old('filter_type') == 'outstanding_fees' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="filter_outstanding">Outstanding fees only</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_upcoming" value="upcoming_invoices" {{ old('filter_type') == 'upcoming_invoices' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="filter_upcoming">Upcoming invoices only</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_swimming" value="swimming_balance" {{ old('filter_type') == 'swimming_balance' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="filter_swimming">Swimming balances only</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 balance-criteria balance-outstanding balance-swimming d-none mt-2">
                        <div class="col-md-4">
                            <label class="finance-form-label">Balance &gt;= KES</label>
                            <input type="number" name="balance_min" class="finance-form-control" value="{{ old('balance_min') }}" min="0" step="0.01" placeholder="Optional">
                        </div>
                        <div class="col-md-4 balance-outstanding balance-percent d-none">
                            <label class="finance-form-label">Unpaid % of term fees &gt;=</label>
                            <input type="number" name="balance_percent_min" class="finance-form-control" value="{{ old('balance_percent_min') }}" min="0" max="100" step="0.01" placeholder="Optional">
                        </div>
                    </div>
                </div>

                {{-- Channels --}}
                <div class="form-section mb-4">
                    <h6 class="form-section-header mb-3">
                        <i class="bi bi-broadcast me-2"></i> Channels <span class="text-danger">*</span>
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="d-flex gap-3 flex-wrap">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="channels[]" id="ch_sms" value="sms" {{ in_array('sms', old('channels', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ch_sms">SMS</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="channels[]" id="ch_email" value="email" {{ in_array('email', old('channels', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ch_email">Email</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="channels[]" id="ch_whatsapp" value="whatsapp" {{ in_array('whatsapp', old('channels', [])) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ch_whatsapp">WhatsApp</label>
                                </div>
                            </div>
                            @error('channels')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Message --}}
                <div class="form-section mb-4">
                    <h6 class="form-section-header mb-3">
                        <i class="bi bi-chat-text me-2"></i> Message
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="finance-form-label">Template (optional)</label>
                            <select name="template_id" id="template_id" class="finance-form-select">
                                <option value="">-- Custom message --</option>
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}" data-content="{{ e($tpl->content ?: '') }}" {{ old('template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-lg-8">
                            <label class="finance-form-label">Custom Message <span class="text-danger">*</span></label>
                            <textarea name="custom_message" id="custom_message" class="finance-form-control" rows="5" placeholder="Leave empty to use template content">{{ old('custom_message') }}</textarea>
                            @error('custom_message')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-4">
                            @include('communication.templates.partials.placeholder-selector', [
                                'systemPlaceholders' => $systemPlaceholders ?? [],
                                'customPlaceholders' => $customPlaceholders ?? collect(),
                                'targetField' => 'custom_message'
                            ])
                        </div>
                    </div>
                </div>

                {{-- Schedule --}}
                <div class="form-section mb-4">
                    <h6 class="form-section-header mb-3">
                        <i class="bi bi-clock me-2"></i> Schedule
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="finance-form-label">Date &amp; Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="send_at" class="finance-form-control" value="{{ old('send_at') }}" required min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}">
                            <small class="finance-muted">Message will be sent automatically at the scheduled time.</small>
                            @error('send_at')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Preview --}}
                <div class="form-section mb-4">
                    <button type="button" class="btn btn-finance btn-finance-outline" id="previewCountBtn">
                        <i class="bi bi-eye"></i> Preview recipient count
                    </button>
                    <span id="previewCountResult" class="ms-2 finance-muted"></span>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <a href="{{ route('finance.fee-reminders.schedule.index') }}" class="btn btn-finance btn-finance-outline">Cancel</a>
                    <button type="submit" class="btn btn-finance btn-finance-primary">
                        <i class="bi bi-calendar-check"></i> Schedule Communication
                    </button>
                </div>
            </form>
        </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const targetRadios = document.querySelectorAll('input[name="target"]');
    const filterRadios = document.querySelectorAll('input[name="filter_type"]');

    function toggleTargetFields() {
        const target = document.querySelector('input[name="target"]:checked')?.value || 'all';
        document.querySelectorAll('.target-field').forEach(el => el.classList.add('d-none'));
        const show = document.querySelector('.target-' + target);
        if (show) show.classList.remove('d-none');
    }

    function toggleBalanceFields() {
        const filter = document.querySelector('input[name="filter_type"]:checked')?.value || 'all';
        document.querySelectorAll('.balance-criteria').forEach(el => el.classList.add('d-none'));
        if (filter === 'outstanding_fees') {
            document.querySelectorAll('.balance-outstanding').forEach(el => el.classList.remove('d-none'));
        } else if (filter === 'swimming_balance') {
            document.querySelectorAll('.balance-swimming').forEach(el => el.classList.remove('d-none'));
        }
    }

    targetRadios.forEach(r => r.addEventListener('change', toggleTargetFields));
    filterRadios.forEach(r => r.addEventListener('change', toggleBalanceFields));
    toggleTargetFields();
    toggleBalanceFields();

    // Template change
    document.getElementById('template_id').addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const msg = document.getElementById('custom_message');
        if (opt?.dataset?.content && !msg.value) {
            msg.value = opt.dataset.content;
        }
    });

    // Student selector modal
    document.addEventListener('studentsSelected', function(event) {
        const studentIds = event.detail.studentIds;
        const input = document.getElementById('selectedStudentIds');
        const display = document.getElementById('selectedStudentsDisplay');
        const badge = document.getElementById('selectedStudentsBadge');
        const list = document.getElementById('selectedStudentsList');
        if (input) input.value = studentIds.join(',');
        if (studentIds.length > 0 && display) {
            display.classList.remove('d-none');
            badge.textContent = studentIds.length;
            list.innerHTML = '';
            studentIds.forEach(id => {
                const checkbox = document.querySelector('#studentSelectorModal #student_' + id);
                if (checkbox) {
                    const label = checkbox.closest('.student-item');
                    const name = label?.querySelector('.fw-semibold')?.textContent?.trim() || 'Student ' + id;
                    const span = document.createElement('span');
                    span.className = 'badge bg-primary';
                    span.textContent = name;
                    list.appendChild(span);
                }
            });
        } else if (display) {
            display.classList.add('d-none');
        }
    });

    // Preview count
    document.getElementById('previewCountBtn').addEventListener('click', function() {
        const form = document.getElementById('scheduleFeeForm');
        const formData = new FormData(form);
        const data = {
            target: formData.get('target') || 'all',
            student_id: formData.get('student_id'),
            selected_student_ids: formData.get('selected_student_ids'),
            classroom_ids: formData.getAll('classroom_ids[]'),
            filter_type: formData.get('filter_type') || 'all',
            balance_min: formData.get('balance_min'),
            balance_percent_min: formData.get('balance_percent_min'),
            _token: '{{ csrf_token() }}'
        };

        fetch('{{ route("finance.fee-reminders.schedule.preview-count") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            document.getElementById('previewCountResult').textContent = 'Approximately ' + (res.count || 0) + ' parent(s) will receive this.';
        })
        .catch(() => {
            document.getElementById('previewCountResult').textContent = 'Could not preview.';
        });
    });
});
</script>
@endpush
@endsection
