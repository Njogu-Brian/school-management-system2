@extends('layouts.app')

@section('content')
@php $classes = $classrooms; @endphp
@include('communication.partials.student-selector-modal')
@include('communication.partials.exclude-student-modal')
@push('styles')
@include('finance.partials.styles')
@endpush

<div class="finance-page schedule-page">
  <div class="finance-shell schedule-shell">
    @include('finance.partials.header', [
        'title' => 'Send or Schedule Fee Communication',
        'icon' => 'bi bi-send-plus',
        'subtitle' => 'Target parents, set filters, and send now or schedule. Balances are checked fresh at send time.',
        'actions' => '<a href="' . route('finance.fee-reminders.schedule.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <strong><i class="bi bi-exclamation-triangle"></i></strong> {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
            <strong><i class="bi bi-exclamation-triangle"></i> Please fix the following:</strong>
            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('finance.fee-reminders.schedule.store') }}" method="POST" id="scheduleFeeForm" class="schedule-form" onsubmit="return scheduleFormSubmit(this)">
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

              <div class="schedule-field target-field target-all d-none mt-3">
                <label class="schedule-label">Exclusions</label>
                <div class="schedule-check-group">
                  <label class="schedule-check">
                    <input type="hidden" name="exclude_staff" value="0">
                    <input type="checkbox" name="exclude_staff" value="1" {{ old('exclude_staff', '1') !== '0' ? 'checked' : '' }}>
                    <span>Exclude staff children</span>
                  </label>
                </div>
                <small class="schedule-muted d-block mt-1">Do not send to parents of students in the Staff category.</small>
                <div class="mt-2">
                  <button type="button" class="schedule-btn schedule-btn-outline" data-bs-toggle="modal" data-bs-target="#excludeStudentSelectorModal">
                    <i class="bi bi-person-x"></i> Exclude specific students
                  </button>
                  <input type="hidden" name="exclude_student_ids" id="excludeStudentIds" value="{{ old('exclude_student_ids') }}">
                  <div id="excludeStudentsDisplay" class="mt-2 d-none">
                    <small class="schedule-muted">Excluded: </small>
                    <span class="schedule-badge" id="excludeStudentsBadge">0</span>
                  </div>
                </div>
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
                    <option value="{{ $tpl->id }}" data-type="{{ $tpl->type ?? 'email' }}" data-content="{{ e($tpl->content ?: '') }}" {{ old('template_id') == $tpl->id ? 'selected' : '' }}>{{ $tpl->title }} ({{ ucfirst($tpl->type ?? 'email') }})</option>
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
              <div class="schedule-field mb-3">
                <label class="schedule-check" style="display: flex; align-items: center; gap: 0.5rem;">
                  <input type="checkbox" name="send_now" id="send_now" value="1" {{ old('send_now') ? 'checked' : '' }}>
                  <span class="fw-semibold">Send immediately</span>
                </label>
                <small class="schedule-muted d-block mt-1">When checked, message is sent now. When unchecked, schedule for later.</small>
              </div>
              <div class="schedule-field recurrence-section">
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
                <button type="button" class="schedule-btn schedule-btn-ghost schedule-btn-sm" id="previewRecipientsBtn">
                  <i class="bi bi-eye"></i> Preview recipients
                </button>
                <span id="previewCountResult" class="schedule-muted ms-2"></span>
              </div>

              <div class="schedule-form-actions mt-4">
                <a href="{{ route('finance.fee-reminders.schedule.index') }}" class="btn btn-finance btn-finance-outline">Cancel</a>
                <button type="submit" class="btn btn-finance btn-finance-primary" id="scheduleSubmitBtn">
                  <span id="scheduleSubmitIcon"><i class="bi bi-calendar-check"></i></span> <span id="scheduleSubmitText">Schedule</span>
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

{{-- Preview Recipients Modal --}}
<div class="modal fade" id="previewRecipientsModal" tabindex="-1" aria-labelledby="previewRecipientsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="previewRecipientsModalLabel">
          <i class="bi bi-people"></i> Recipients Preview
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="previewRecipientsLoading" class="text-center py-5 d-none">
          <div class="spinner-border text-primary" role="status"></div>
          <p class="mt-2 text-muted">Loading recipients...</p>
        </div>
        <div id="previewRecipientsContent" class="d-none">
          <p class="text-muted mb-3"><strong id="previewRecipientsCount">0</strong> parent(s) will receive this communication.</p>
          <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
            <table class="table table-sm table-striped">
              <thead class="table-light sticky-top">
                <tr>
                  <th>#</th>
                  <th>Student Name</th>
                  <th>Admission #</th>
                  <th>Parent Contact</th>
                  <th class="text-end">Fee Balance (KES)</th>
                </tr>
              </thead>
              <tbody id="previewRecipientsTableBody">
              </tbody>
            </table>
          </div>
        </div>
        <div id="previewRecipientsEmpty" class="text-center py-5 text-muted d-none">
          <i class="bi bi-inbox" style="font-size: 2rem;"></i>
          <p class="mt-2">No recipients match the current filters.</p>
        </div>
        <div id="previewRecipientsError" class="alert alert-danger d-none">
          Could not load recipients. Please try again.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-finance btn-finance-primary" data-bs-dismiss="modal" onclick="document.getElementById('scheduleSubmitBtn').focus()">
          <i class="bi bi-calendar-check"></i> Continue to Schedule
        </button>
      </div>
    </div>
  </div>
</div>

@push('styles')
<link rel="stylesheet" href="{{ asset('css/schedule.css') }}">
@endpush

@push('scripts')
<script>
function scheduleFormSubmit(form) {
  // Client-side validation: require message or template
  const templateSelect = document.getElementById('template_id');
  const msgBox = document.getElementById('custom_message');
  const hasTemplate = templateSelect && templateSelect.value && templateSelect.value !== '';
  const hasMessage = msgBox && msgBox.value && msgBox.value.trim().length > 0;
  if (!hasTemplate && !hasMessage) {
    alert('Please provide a message or select a template.');
    const firstInvalid = msgBox || templateSelect;
    if (firstInvalid) firstInvalid.focus();
    return false;
  }
  // Validate at least one channel
  const channels = form.querySelectorAll('input[name="channels[]"]:checked');
  if (!channels.length) {
    alert('Please select at least one channel (SMS, Email, or WhatsApp).');
    return false;
  }
  const btn = document.getElementById('scheduleSubmitBtn');
  const icon = document.getElementById('scheduleSubmitIcon');
  const text = document.getElementById('scheduleSubmitText');
  if (btn && btn.disabled) return false;
  if (btn) {
    btn.disabled = true;
    if (icon) icon.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
    if (text) text.textContent = text.textContent === 'Send Now' ? 'Sending…' : 'Saving…';
  }
  // Submit via fetch to avoid 301 POST->GET data loss (browsers convert POST to GET on 301)
  const formData = new FormData(form);
  fetch(form.action, {
    method: 'POST',
    body: formData,
    redirect: 'manual',
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
  }).then(function(res) {
    if (res.status === 301) {
      var loc = res.headers.get('Location');
      if (loc) {
        var fullLoc = loc.startsWith('http') ? loc : new URL(loc, window.location.origin).href;
        // 301 from server (e.g. HTTP->HTTPS): retry POST to the new URL to preserve form data
        return fetch(fullLoc, { method: 'POST', body: formData, redirect: 'manual', credentials: 'same-origin' })
          .then(function(r) { if (r.status === 302 && r.headers.get('Location')) window.location.href = r.headers.get('Location'); return r; });
      }
    }
    if (res.status === 302 || res.type === 'opaqueredirect') {
      var loc = res.headers.get('Location');
      if (loc) window.location.href = loc;
      return;
    }
    if (res.status === 422) {
      return res.json().then(function(data) {
        var msg = (data.errors && Object.values(data.errors).flat()).filter(Boolean) || [data.message || 'Validation failed'];
        alert(msg.join('\n'));
      });
    }
    if (!res.ok) {
      return res.text().then(function() { alert('Server error: ' + res.status + '. Ensure storage permissions are correct and Laravel can write logs.'); });
    }
  }).catch(function(err) {
    alert('Request failed. Check your connection.');
    console.error(err);
  }).finally(function() {
    if (btn) { btn.disabled = false; if (icon) icon.innerHTML = '<i class="bi bi-calendar-check"></i>'; if (text) text.textContent = (document.getElementById('send_now')?.checked ? 'Send Now' : 'Schedule'); }
  });
  return false; // prevent default form submit
}
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
    const sendNow = document.getElementById('send_now')?.checked;
    const type = document.querySelector('input[name="recurrence_type"]:checked')?.value || 'once';
    const recurrenceSection = document.querySelector('.recurrence-section');
    if (recurrenceSection) recurrenceSection.style.display = sendNow ? 'none' : '';
    document.querySelectorAll('.recurrence-field').forEach(el => el.classList.add('d-none'));
    if (!sendNow) {
      document.querySelectorAll('.recurrence-once').forEach(el => el.classList.toggle('d-none', type !== 'once'));
      document.querySelectorAll('.recurrence-recurring').forEach(el => el.classList.toggle('d-none', type === 'once'));
      document.querySelectorAll('.recurrence-times').forEach(el => el.classList.toggle('d-none', type === 'once'));
      document.querySelectorAll('.recurrence-weekly').forEach(el => el.classList.toggle('d-none', type !== 'weekly'));
    }
    const sendAt = document.getElementById('send_at');
    const recStart = document.getElementById('recurrence_start_at');
    if (sendAt) sendAt.required = !sendNow && type === 'once';
    if (recStart) recStart.required = !sendNow && type !== 'once';
    const submitText = document.getElementById('scheduleSubmitText');
    if (submitText) submitText.textContent = sendNow ? 'Send Now' : 'Schedule';
  }

  targetRadios.forEach(r => r.addEventListener('change', toggleTargetFields));
  filterRadios.forEach(r => r.addEventListener('change', toggleBalanceFields));
  recurrenceRadios.forEach(r => r.addEventListener('change', toggleRecurrenceFields));
  document.getElementById('send_now')?.addEventListener('change', toggleRecurrenceFields);
  toggleTargetFields();
  toggleBalanceFields();
  toggleRecurrenceFields();

  // Scroll to first error on validation failure
  @if($errors->any())
  const firstError = document.querySelector('.schedule-error, .is-invalid, .alert-danger');
  if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
  @endif

  // Keep send_at min in the future (avoids "must be after now" validation)
  const sendAtInput = document.getElementById('send_at');
  if (sendAtInput) {
    function updateSendAtMin() {
      const now = new Date();
      now.setMinutes(now.getMinutes() + 1);
      sendAtInput.min = now.toISOString().slice(0, 16);
    }
    updateSendAtMin();
    setInterval(updateSendAtMin, 60000);
  }

  // Template selector: update message box when switching templates
  document.getElementById('template_id').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    const msg = document.getElementById('custom_message');
    if (!msg) return;
    if (opt?.value && opt?.dataset?.content !== undefined) {
      msg.value = opt.dataset.content || '';
    } else if (!opt?.value) {
      msg.value = ''; // Clear when switching to "Custom message"
    }
  });

  // Filter templates by selected channels
  function filterTemplatesByChannels() {
    const channels = Array.from(document.querySelectorAll('input[name="channels[]"]:checked')).map(c => c.value);
    const templateSelect = document.getElementById('template_id');
    if (!templateSelect) return;
    const selectedOpt = templateSelect.options[templateSelect.selectedIndex];
    const options = templateSelect.querySelectorAll('option[value]');
    options.forEach(opt => {
      const type = (opt.dataset.type || 'email').toLowerCase();
      const matches = channels.length === 0 || channels.some(ch => {
        if (ch === 'whatsapp') return type === 'whatsapp' || type === 'sms';
        return type === ch;
      });
      opt.style.display = matches ? '' : 'none';
      opt.disabled = !matches;
    });
    // If selected template no longer matches channels, switch to custom
    if (selectedOpt && selectedOpt.value && selectedOpt.disabled) {
      templateSelect.value = '';
      document.getElementById('custom_message').value = '';
    }
  }
  document.querySelectorAll('input[name="channels[]"]').forEach(c => c.addEventListener('change', filterTemplatesByChannels));
  filterTemplatesByChannels();

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

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  document.getElementById('previewRecipientsBtn').addEventListener('click', function() {
    const form = document.getElementById('scheduleFeeForm');
    const formData = new FormData(form);
    const modal = new bootstrap.Modal(document.getElementById('previewRecipientsModal'));
    const loading = document.getElementById('previewRecipientsLoading');
    const content = document.getElementById('previewRecipientsContent');
    const empty = document.getElementById('previewRecipientsEmpty');
    const err = document.getElementById('previewRecipientsError');
    const tbody = document.getElementById('previewRecipientsTableBody');
    const countEl = document.getElementById('previewRecipientsCount');

    loading.classList.remove('d-none');
    content.classList.add('d-none');
    empty.classList.add('d-none');
    err.classList.add('d-none');
    modal.show();

    const payload = {
      target: formData.get('target') || 'all',
      student_id: formData.get('student_id'),
      selected_student_ids: formData.get('selected_student_ids'),
      classroom_ids: formData.getAll('classroom_ids[]'),
      channels: formData.getAll('channels[]'),
      filter_type: formData.get('filter_type') || 'all',
      balance_min: formData.get('balance_min'),
      balance_percent_min: formData.get('balance_percent_min'),
      exclude_staff: formData.get('exclude_staff') !== '0',
      exclude_student_ids: formData.get('exclude_student_ids'),
      _token: '{{ csrf_token() }}'
    };

    fetch('{{ route("finance.fee-reminders.schedule.preview-recipients") }}', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
      loading.classList.add('d-none');
      document.getElementById('previewCountResult').textContent = res.count + ' parent(s)';
      if (res.recipients && res.recipients.length > 0) {
        countEl.textContent = res.count;
        tbody.innerHTML = res.recipients.map((r, i) =>
          '<tr><td>' + (i + 1) + '</td><td>' + escapeHtml(r.student_name || '-') + '</td><td>' + escapeHtml(r.admission_number || '-') + '</td><td>' + escapeHtml(r.parent_contact || '-') + '</td><td class="text-end">' + escapeHtml(r.fee_balance || '0.00') + '</td></tr>'
        ).join('');
        content.classList.remove('d-none');
      } else {
        empty.classList.remove('d-none');
      }
    })
    .catch(() => {
      loading.classList.add('d-none');
      err.classList.remove('d-none');
      document.getElementById('previewCountResult').textContent = 'Preview failed';
    });
  });
});
</script>
@endpush
@endsection
