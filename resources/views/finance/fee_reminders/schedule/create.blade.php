@extends('layouts.app')

@section('content')
@php $classes = $classrooms; @endphp
@include('communication.partials.student-selector-modal')

<div class="schedule-page">
  <div class="schedule-shell">
    <div class="schedule-hero">
      <div class="schedule-hero-content">
        <h1 class="schedule-hero-title">
          <i class="bi bi-calendar-event"></i>
          Schedule Fee Communication
        </h1>
        <p class="schedule-hero-subtitle">Target parents, set filters, and schedule automatic fee reminders. Balances are checked fresh at send time.</p>
      </div>
      <div class="schedule-hero-actions">
        <a href="{{ route('finance.fee-reminders.schedule.index') }}" class="schedule-btn schedule-btn-ghost">
          <i class="bi bi-arrow-left"></i> Back
        </a>
      </div>
    </div>

    <form action="{{ route('finance.fee-reminders.schedule.store') }}" method="POST" id="scheduleFeeForm" class="schedule-form">
      @csrf

      <div class="schedule-grid">
        {{-- Main --}}
        <div class="schedule-main">
          <div class="schedule-card schedule-card-glow">
            <div class="schedule-card-header">
              <span class="schedule-card-icon"><i class="bi bi-people-fill"></i></span>
              <h2>Recipients</h2>
            </div>
            <div class="schedule-card-body">
              <label class="schedule-label">Target <span class="text-danger">*</span></label>
              <div class="schedule-radio-group">
                <label class="schedule-radio">
                  <input type="radio" name="target" value="one_parent" {{ old('target') == 'one_parent' ? 'checked' : '' }}>
                  <span>One parent</span>
                </label>
                <label class="schedule-radio">
                  <input type="radio" name="target" value="specific_students" {{ old('target') == 'specific_students' ? 'checked' : '' }}>
                  <span>Specific students</span>
                </label>
                <label class="schedule-radio">
                  <input type="radio" name="target" value="class" {{ old('target') == 'class' ? 'checked' : '' }}>
                  <span>Class(es)</span>
                </label>
                <label class="schedule-radio">
                  <input type="radio" name="target" value="all" {{ old('target', 'all') == 'all' ? 'checked' : '' }}>
                  <span>All parents</span>
                </label>
              </div>

              <div class="schedule-field target-field target-one_parent d-none">
                <label class="schedule-label">Student <span class="text-danger">*</span></label>
                @include('partials.student_live_search', [
                  'hiddenInputId' => 'student_id',
                  'displayInputId' => 'scheduleStudentSearch',
                  'resultsId' => 'scheduleStudentResults',
                  'placeholder' => 'Type name or admission #',
                  'inputClass' => 'schedule-input' . ($errors->has('student_id') ? ' is-invalid' : ''),
                  'initialLabel' => old('student_id') ? optional(\App\Models\Student::find(old('student_id')))->search_display : '',
                ])
                @error('student_id')<div class="schedule-error">{{ $message }}</div>@enderror
              </div>

              <div class="schedule-field target-field target-specific_students d-none">
                <label class="schedule-label">Select Students <span class="text-danger">*</span></label>
                <button type="button" class="schedule-btn schedule-btn-outline w-100" data-bs-toggle="modal" data-bs-target="#studentSelectorModal">
                  <i class="bi bi-people-fill"></i> Open Student Selector
                </button>
                <input type="hidden" name="selected_student_ids" id="selectedStudentIds" value="{{ old('selected_student_ids') }}">
                <div id="selectedStudentsDisplay" class="mt-2 d-none">
                  <small class="schedule-muted">Selected: </small>
                  <span class="schedule-badge" id="selectedStudentsBadge">0</span>
                  <div id="selectedStudentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
                </div>
                @error('selected_student_ids')<div class="schedule-error">{{ $message }}</div>@enderror
              </div>

              <div class="schedule-field target-field target-class d-none">
                <label class="schedule-label">Classroom(s) <span class="text-danger">*</span></label>
                <select name="classroom_ids[]" class="schedule-select" multiple size="5">
                  @foreach($classrooms as $c)
                    <option value="{{ $c->id }}" {{ in_array($c->id, old('classroom_ids', [])) ? 'selected' : '' }}>{{ $c->name }}</option>
                  @endforeach
                </select>
                <small class="schedule-muted">Hold Ctrl/Cmd to select multiple</small>
                @error('classroom_ids')<div class="schedule-error">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>

          <div class="schedule-card schedule-card-glow">
            <div class="schedule-card-header">
              <span class="schedule-card-icon"><i class="bi bi-funnel"></i></span>
              <h2>Filter by fee type</h2>
            </div>
            <div class="schedule-card-body">
              <div class="schedule-radio-group">
                <label class="schedule-radio">
                  <input type="radio" name="filter_type" value="all" {{ old('filter_type', 'all') == 'all' ? 'checked' : '' }}>
                  <span>All</span>
                </label>
                <label class="schedule-radio">
                  <input type="radio" name="filter_type" value="outstanding_fees" {{ old('filter_type') == 'outstanding_fees' ? 'checked' : '' }}>
                  <span>Outstanding fees only</span>
                </label>
                <label class="schedule-radio">
                  <input type="radio" name="filter_type" value="upcoming_invoices" {{ old('filter_type') == 'upcoming_invoices' ? 'checked' : '' }}>
                  <span>Upcoming invoices only</span>
                </label>
                <label class="schedule-radio">
                  <input type="radio" name="filter_type" value="swimming_balance" {{ old('filter_type') == 'swimming_balance' ? 'checked' : '' }}>
                  <span>Swimming balances only</span>
                </label>
              </div>

              <div class="schedule-row balance-criteria balance-outstanding balance-swimming d-none">
                <div class="schedule-field">
                  <label class="schedule-label">Balance &gt;= KES</label>
                  <input type="number" name="balance_min" class="schedule-input" value="{{ old('balance_min') }}" min="0" step="0.01" placeholder="Optional">
                </div>
                <div class="schedule-field balance-outstanding balance-percent d-none">
                  <label class="schedule-label">Unpaid % of term fees &gt;=</label>
                  <input type="number" name="balance_percent_min" class="schedule-input" value="{{ old('balance_percent_min') }}" min="0" max="100" step="0.01" placeholder="Optional">
                </div>
              </div>
            </div>
          </div>

          <div class="schedule-card schedule-card-glow">
            <div class="schedule-card-header">
              <span class="schedule-card-icon"><i class="bi bi-broadcast"></i></span>
              <h2>Channels <span class="text-danger">*</span></h2>
            </div>
            <div class="schedule-card-body">
              <div class="schedule-check-group">
                <label class="schedule-check">
                  <input type="checkbox" name="channels[]" value="sms" {{ in_array('sms', old('channels', [])) ? 'checked' : '' }}>
                  <span><i class="bi bi-chat-dots"></i> SMS</span>
                </label>
                <label class="schedule-check">
                  <input type="checkbox" name="channels[]" value="email" {{ in_array('email', old('channels', [])) ? 'checked' : '' }}>
                  <span><i class="bi bi-envelope"></i> Email</span>
                </label>
                <label class="schedule-check">
                  <input type="checkbox" name="channels[]" value="whatsapp" {{ in_array('whatsapp', old('channels', [])) ? 'checked' : '' }}>
                  <span><i class="bi bi-whatsapp"></i> WhatsApp</span>
                </label>
              </div>
              @error('channels')<div class="schedule-error">{{ $message }}</div>@enderror
            </div>
          </div>

          <div class="schedule-card schedule-card-glow">
            <div class="schedule-card-header">
              <span class="schedule-card-icon"><i class="bi bi-chat-text"></i></span>
              <h2>Message</h2>
            </div>
            <div class="schedule-card-body">
              <div class="schedule-field">
                <label class="schedule-label">Template (optional)</label>
                <select name="template_id" id="template_id" class="schedule-select">
                  <option value="">-- Custom message --</option>
                  @foreach($templates as $tpl)
                    <option value="{{ $tpl->id }}" data-content="{{ e($tpl->content ?: '') }}" {{ old('template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->title }}</option>
                  @endforeach
                </select>
              </div>
              <div class="schedule-field">
                <label class="schedule-label">Custom Message <span class="text-danger">*</span></label>
                <textarea name="custom_message" id="custom_message" class="schedule-input schedule-textarea" rows="5" placeholder="Leave empty to use template content">{{ old('custom_message') }}</textarea>
                @error('custom_message')<div class="schedule-error">{{ $message }}</div>@enderror
              </div>
            </div>
          </div>
        </div>

        {{-- Sidebar --}}
        <div class="schedule-sidebar">
          <div class="schedule-card schedule-card-glow schedule-card-sticky">
            <div class="schedule-card-header">
              <span class="schedule-card-icon"><i class="bi bi-clock-history"></i></span>
              <h2>Schedule</h2>
            </div>
            <div class="schedule-card-body">
              <div class="schedule-field">
                <label class="schedule-label">Repeat <span class="text-danger">*</span></label>
                <div class="schedule-radio-group schedule-radio-stack">
                  <label class="schedule-radio">
                    <input type="radio" name="recurrence_type" value="once" {{ old('recurrence_type', 'once') == 'once' ? 'checked' : '' }}>
                    <span>Once</span>
                  </label>
                  <label class="schedule-radio">
                    <input type="radio" name="recurrence_type" value="daily" {{ old('recurrence_type') == 'daily' ? 'checked' : '' }}>
                    <span>Daily</span>
                  </label>
                  <label class="schedule-radio">
                    <input type="radio" name="recurrence_type" value="weekly" {{ old('recurrence_type') == 'weekly' ? 'checked' : '' }}>
                    <span>Weekly</span>
                  </label>
                  <label class="schedule-radio">
                    <input type="radio" name="recurrence_type" value="times_per_day" {{ old('recurrence_type') == 'times_per_day' ? 'checked' : '' }}>
                    <span>Multiple times per day</span>
                  </label>
                </div>
              </div>

              <div class="schedule-field recurrence-field recurrence-once">
                <label class="schedule-label">Date &amp; Time <span class="text-danger">*</span></label>
                <input type="datetime-local" name="send_at" id="send_at" class="schedule-input" value="{{ old('send_at') }}" min="{{ now()->addMinute()->format('Y-m-d\TH:i') }}">
                @error('send_at')<div class="schedule-error">{{ $message }}</div>@enderror
              </div>

              <div class="schedule-field recurrence-field recurrence-recurring d-none">
                <label class="schedule-label">Start date <span class="text-danger">*</span></label>
                <input type="date" name="recurrence_start_at" id="recurrence_start_at" class="schedule-input" value="{{ old('recurrence_start_at', now()->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}">
                @error('recurrence_start_at')<div class="schedule-error">{{ $message }}</div>@enderror
              </div>

              <div class="schedule-field recurrence-field recurrence-recurring d-none">
                <label class="schedule-label">End date (optional)</label>
                <input type="date" name="recurrence_end_at" id="recurrence_end_at" class="schedule-input" value="{{ old('recurrence_end_at') }}" placeholder="No end">
              </div>

              <div class="schedule-field recurrence-field recurrence-times d-none">
                <label class="schedule-label">Send times <span class="text-danger">*</span></label>
                <div id="recurrenceTimesContainer">
                  @foreach(old('recurrence_times', ['09:00']) as $t)
                    <div class="schedule-time-row">
                      <input type="time" name="recurrence_times[]" class="schedule-input" value="{{ $t }}">
                      <button type="button" class="schedule-btn schedule-btn-icon schedule-time-remove"><i class="bi bi-dash-lg"></i></button>
                    </div>
                  @endforeach
                </div>
                <button type="button" class="schedule-btn schedule-btn-ghost schedule-btn-sm mt-1" id="addRecurrenceTime">
                  <i class="bi bi-plus"></i> Add time
                </button>
              </div>

              <div class="schedule-field recurrence-field recurrence-weekly d-none">
                <label class="schedule-label">Days of week <span class="text-danger">*</span></label>
                <div class="schedule-check-group schedule-week-days">
                  @foreach(['Sun'=>0,'Mon'=>1,'Tue'=>2,'Wed'=>3,'Thu'=>4,'Fri'=>5,'Sat'=>6] as $label => $val)
                    <label class="schedule-check">
                      <input type="checkbox" name="recurrence_week_days[]" value="{{ $val }}" {{ in_array($val, old('recurrence_week_days', [1])) ? 'checked' : '' }}>
                      <span>{{ $label }}</span>
                    </label>
                  @endforeach
                </div>
                @error('recurrence_week_days')<div class="schedule-error">{{ $message }}</div>@enderror
              </div>

              <div class="schedule-info-box mt-3">
                <i class="bi bi-info-circle"></i>
                <span>Balances are checked fresh at each send. Parents who have paid will not receive the message.</span>
              </div>

              <div class="schedule-field mt-3">
                <button type="button" class="schedule-btn schedule-btn-ghost schedule-btn-sm" id="previewCountBtn">
                  <i class="bi bi-eye"></i> Preview recipient count
                </button>
                <span id="previewCountResult" class="schedule-muted ms-2"></span>
              </div>

              <div class="schedule-form-actions mt-4">
                <a href="{{ route('finance.fee-reminders.schedule.index') }}" class="schedule-btn schedule-btn-ghost">Cancel</a>
                <button type="submit" class="schedule-btn schedule-btn-primary">
                  <i class="bi bi-calendar-check"></i> Schedule
                </button>
              </div>
            </div>
          </div>

          <div class="schedule-card schedule-card-glow">
            <div class="schedule-card-header">
              <span class="schedule-card-icon"><i class="bi bi-tags"></i></span>
              <h2>Placeholders</h2>
            </div>
            <div class="schedule-card-body">
              @include('communication.templates.partials.placeholder-selector', [
                'systemPlaceholders' => $systemPlaceholders ?? [],
                'customPlaceholders' => $customPlaceholders ?? collect(),
                'targetField' => 'custom_message'
              ])
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/schedule.css') }}">
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const targetRadios = document.querySelectorAll('input[name="target"]');
  const filterRadios = document.querySelectorAll('input[name="filter_type"]');
  const recurrenceRadios = document.querySelectorAll('input[name="recurrence_type"]');

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

  function toggleRecurrenceFields() {
    const type = document.querySelector('input[name="recurrence_type"]:checked')?.value || 'once';
    document.querySelectorAll('.recurrence-field').forEach(el => el.classList.add('d-none'));
    document.querySelectorAll('.recurrence-once').forEach(el => el.classList.toggle('d-none', type !== 'once'));
    document.querySelectorAll('.recurrence-recurring').forEach(el => el.classList.toggle('d-none', type === 'once'));
    document.querySelectorAll('.recurrence-times').forEach(el => el.classList.toggle('d-none', type === 'once'));
    document.querySelectorAll('.recurrence-weekly').forEach(el => el.classList.toggle('d-none', type !== 'weekly'));
    document.getElementById('send_at').required = type === 'once';
    document.getElementById('recurrence_start_at').required = type !== 'once';
  }

  targetRadios.forEach(r => r.addEventListener('change', toggleTargetFields));
  filterRadios.forEach(r => r.addEventListener('change', toggleBalanceFields));
  recurrenceRadios.forEach(r => r.addEventListener('change', toggleRecurrenceFields));
  toggleTargetFields();
  toggleBalanceFields();
  toggleRecurrenceFields();

  document.getElementById('template_id').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const msg = document.getElementById('custom_message');
    if (opt?.dataset?.content && !msg.value) msg.value = opt.dataset.content;
  });

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
          span.className = 'schedule-badge';
          span.textContent = name;
          list.appendChild(span);
        }
      });
    } else if (display) display.classList.add('d-none');
  });

  document.getElementById('addRecurrenceTime').addEventListener('click', function() {
    const row = document.createElement('div');
    row.className = 'schedule-time-row';
    row.innerHTML = '<input type="time" name="recurrence_times[]" class="schedule-input" value="09:00">' +
      '<button type="button" class="schedule-btn schedule-btn-icon schedule-time-remove"><i class="bi bi-dash-lg"></i></button>';
    document.getElementById('recurrenceTimesContainer').appendChild(row);
  });

  document.getElementById('recurrenceTimesContainer').addEventListener('click', function(e) {
    if (e.target.closest('.schedule-time-remove')) {
      const rows = this.querySelectorAll('.schedule-time-row');
      if (rows.length > 1) e.target.closest('.schedule-time-row').remove();
    }
  });

  document.getElementById('previewCountBtn').addEventListener('click', function() {
    const form = document.getElementById('scheduleFeeForm');
    const formData = new FormData(form);
    fetch('{{ route("finance.fee-reminders.schedule.preview-count") }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
      body: JSON.stringify({
        target: formData.get('target') || 'all',
        student_id: formData.get('student_id'),
        selected_student_ids: formData.get('selected_student_ids'),
        classroom_ids: formData.getAll('classroom_ids[]'),
        filter_type: formData.get('filter_type') || 'all',
        balance_min: formData.get('balance_min'),
        balance_percent_min: formData.get('balance_percent_min'),
        _token: '{{ csrf_token() }}'
      })
    })
    .then(r => r.json())
    .then(res => {
      document.getElementById('previewCountResult').textContent = '~' + (res.count || 0) + ' parent(s)';
    })
    .catch(() => {
      document.getElementById('previewCountResult').textContent = 'Could not preview';
    });
  });
});
</script>
@endpush
@endsection
