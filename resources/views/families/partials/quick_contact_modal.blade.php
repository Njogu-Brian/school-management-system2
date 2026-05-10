@push('styles')
<style>
  .modal-quick-contact .modal-content {
    border: 1px solid color-mix(in srgb, var(--settings-primary) 18%, rgba(var(--bs-body-color-rgb), 0.18) 82%);
  }
  .modal-quick-contact .modal-header {
    background: linear-gradient(135deg, var(--settings-primary) 0%, var(--settings-accent) 100%);
    color: #fff;
    border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  }
  .modal-quick-contact .modal-header .text-muted {
    color: rgba(255, 255, 255, 0.85) !important;
  }
  .modal-quick-contact .modal-header .btn-close {
    filter: invert(1) grayscale(100%);
    opacity: 0.9;
  }
  .modal-quick-contact .modal-footer {
    background: color-mix(in srgb, var(--settings-primary) 4%, var(--bs-body-bg) 96%);
    border-top: 1px solid var(--settings-border);
    gap: 0.5rem;
  }
  .theme-dark .modal-quick-contact .modal-footer {
    background: rgba(var(--bs-body-color-rgb), 0.06);
  }
  .modal-quick-contact .btn-save-quick {
    background: linear-gradient(135deg, var(--settings-primary), var(--settings-accent));
    border: none;
    color: #fff;
    font-weight: 600;
    min-width: 8rem;
    box-shadow: 0 12px 24px rgba(20, 184, 166, 0.25);
  }
  .modal-quick-contact .btn-save-quick:hover,
  .modal-quick-contact .btn-save-quick:focus {
    filter: brightness(1.08);
    color: #fff;
  }
  .modal-quick-contact .quick-slot-card {
    border-radius: 0.65rem;
    border: 1px solid var(--settings-border);
    padding: 0.85rem 1rem;
    background: color-mix(in srgb, var(--settings-primary) 4%, var(--bs-body-bg) 96%);
  }
  .theme-dark .modal-quick-contact .quick-slot-card {
    background: rgba(var(--bs-body-color-rgb), 0.06);
  }
  .modal-quick-contact [data-quick-row].quick-row-filled {
    opacity: 0.72;
  }
  .modal-quick-contact [data-quick-row].quick-row-filled .form-control,
  .modal-quick-contact [data-quick-row].quick-row-filled .form-select {
    background-color: color-mix(in srgb, var(--settings-primary) 3%, var(--bs-body-bg) 97%);
  }
</style>
@endpush

@if(Route::has('families.integrity-report.quick-parent-phones'))
<div class="modal fade modal-quick-contact" id="quickContactModal" tabindex="-1" aria-labelledby="quickContactModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
    <div class="modal-content shadow">
      <div class="modal-header">
        <div>
          <h5 class="modal-title h6 mb-0" id="quickContactModalLabel"><i class="bi bi-pencil-square me-2"></i>Quick edit contact</h5>
          <p class="text-muted small mb-0 mt-1" id="quickContactStudentLabel"></p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form method="POST" action="{{ route('families.integrity-report.quick-parent-phones') }}" id="quickContactForm">
        @csrf
        <input type="hidden" name="student_id" id="quick_contact_student_id" value="">
        <input type="hidden" name="return_route" id="quick_return_route" value="families.integrity-report.missing-contacts">
        <input type="hidden" name="ret_dup_limit" id="quick_ret_dup_limit" value="">
        <input type="hidden" name="ret_both_page" id="quick_ret_both_page" value="">
        <input type="hidden" name="ret_one_page" id="quick_ret_one_page" value="">
        <input type="hidden" name="ret_per_both" id="quick_ret_per_both" value="">
        <input type="hidden" name="ret_per_one" id="quick_ret_per_one" value="">
        <input type="hidden" name="ret_q" id="quick_ret_q" value="">
        <div class="modal-body">
          <p class="small text-muted mb-2">Only <strong>blank</strong> fields on this parent record are saved (same rules as full student edit). Use country code + local digits for phones and WhatsApp.</p>
          <div class="alert alert-soft border py-2 small mb-3 mb-md-4">
            <i class="bi bi-info-circle me-1"></i>
            <strong>Siblings:</strong> Changes apply to <strong>this student’s parent row only</strong> (see Parent row # on the student profile). Linked siblings see the update automatically only if they share that same parent row. If each sibling still has a separate parent record, edit each row—or link/consolidate from the duplicate report—until they share one.
          </div>

          <fieldset class="quick-slot-card mb-3" id="quick_father_fs">
            <legend class="float-none w-auto px-0 mb-2 fs-6 fw-semibold text-uppercase small text-muted">Father</legend>
            <div class="mb-2" data-quick-row="father_name">
              <label class="form-label small mb-0">Name</label>
              <input type="text" name="father_name" id="quick_father_name" class="form-control form-control-sm" maxlength="255" placeholder="Full name" autocomplete="name">
            </div>
            <div class="row g-2 mb-2" data-quick-row="father_phone">
              <div class="col-sm-4">
                <label class="form-label small mb-0">Phone country</label>
                <select id="quick_father_cc" class="form-select form-select-sm">
                  @foreach($ccOpts as $cc)
                    <option value="{{ $cc['code'] }}">{{ $cc['label'] }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-8">
                <label class="form-label small mb-0">Phone (digits)</label>
                <input type="text" name="father_phone" id="quick_father_phone" class="form-control form-control-sm" placeholder="Local number" inputmode="numeric" autocomplete="tel-national">
              </div>
            </div>
            <div class="row g-2 mb-2" data-quick-row="father_whatsapp">
              <div class="col-sm-4">
                <label class="form-label small mb-0">WhatsApp country</label>
                <select id="quick_father_wa_cc" class="form-select form-select-sm">
                  @foreach($ccOpts as $cc)
                    <option value="{{ $cc['code'] }}">{{ $cc['label'] }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-8">
                <label class="form-label small mb-0">WhatsApp (digits)</label>
                <input type="text" name="father_whatsapp" id="quick_father_whatsapp" class="form-control form-control-sm" placeholder="Local number" inputmode="numeric">
              </div>
            </div>
            <div class="mb-0" data-quick-row="father_email">
              <label class="form-label small mb-0">Email</label>
              <input type="email" name="father_email" id="quick_father_email" class="form-control form-control-sm" placeholder="email@example.com" autocomplete="email">
            </div>
          </fieldset>

          <fieldset class="quick-slot-card mb-0" id="quick_mother_fs">
            <legend class="float-none w-auto px-0 mb-2 fs-6 fw-semibold text-uppercase small text-muted">Mother</legend>
            <div class="mb-2" data-quick-row="mother_name">
              <label class="form-label small mb-0">Name</label>
              <input type="text" name="mother_name" id="quick_mother_name" class="form-control form-control-sm" maxlength="255" placeholder="Full name" autocomplete="name">
            </div>
            <div class="row g-2 mb-2" data-quick-row="mother_phone">
              <div class="col-sm-4">
                <label class="form-label small mb-0">Phone country</label>
                <select id="quick_mother_cc" class="form-select form-select-sm">
                  @foreach($ccOpts as $cc)
                    <option value="{{ $cc['code'] }}">{{ $cc['label'] }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-8">
                <label class="form-label small mb-0">Phone (digits)</label>
                <input type="text" name="mother_phone" id="quick_mother_phone" class="form-control form-control-sm" placeholder="Local number" inputmode="numeric" autocomplete="tel-national">
              </div>
            </div>
            <div class="row g-2 mb-2" data-quick-row="mother_whatsapp">
              <div class="col-sm-4">
                <label class="form-label small mb-0">WhatsApp country</label>
                <select id="quick_mother_wa_cc" class="form-select form-select-sm">
                  @foreach($ccOpts as $cc)
                    <option value="{{ $cc['code'] }}">{{ $cc['label'] }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-sm-8">
                <label class="form-label small mb-0">WhatsApp (digits)</label>
                <input type="text" name="mother_whatsapp" id="quick_mother_whatsapp" class="form-control form-control-sm" placeholder="Local number" inputmode="numeric">
              </div>
            </div>
            <div class="mb-0" data-quick-row="mother_email">
              <label class="form-label small mb-0">Email</label>
              <input type="email" name="mother_email" id="quick_mother_email" class="form-control form-control-sm" placeholder="email@example.com" autocomplete="email">
            </div>
          </fieldset>
        </div>
        <div class="modal-footer flex-wrap justify-content-between">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-save-quick"><i class="bi bi-check2-circle me-1"></i>Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endif

@push('scripts')
<script>
(function () {
  const modalEl = document.getElementById('quickContactModal');
  if (!modalEl || typeof bootstrap === 'undefined') return;
  const modal = new bootstrap.Modal(modalEl);
  const form = document.getElementById('quickContactForm');
  const sid = document.getElementById('quick_contact_student_id');
  const lbl = document.getElementById('quickContactStudentLabel');
  const fatherFs = document.getElementById('quick_father_fs');
  const motherFs = document.getElementById('quick_mother_fs');
  /** Survives form.reset() / modal lifecycle so POST always includes student_id */
  let pendingQuickStudentId = null;

  const sel = {
    father_cc: document.getElementById('quick_father_cc'),
    father_wa_cc: document.getElementById('quick_father_wa_cc'),
    mother_cc: document.getElementById('quick_mother_cc'),
    mother_wa_cc: document.getElementById('quick_mother_wa_cc'),
  };

  function setSelectValue(el, code) {
    if (!el) return;
    const opt = Array.from(el.options).find(function (o) { return o.value === code; });
    el.value = opt ? code : (el.options[0] ? el.options[0].value : '');
  }

  function setHidden(id, v) {
    const el = document.getElementById(id);
    if (el) el.value = v !== undefined && v !== null ? String(v) : '';
  }

  var selectNames = {
    quick_father_cc: 'father_phone_country_code',
    quick_father_wa_cc: 'father_whatsapp_country_code',
    quick_mother_cc: 'mother_phone_country_code',
    quick_mother_wa_cc: 'mother_whatsapp_country_code',
  };

  function rowEditable(rowEl, editable) {
    if (!rowEl) return;
    rowEl.style.display = '';
    rowEl.classList.toggle('quick-row-filled', !editable);
    rowEl.querySelectorAll('input,select,textarea').forEach(function (node) {
      node.disabled = !editable;
      if (node.tagName === 'SELECT') {
        if (editable && selectNames[node.id]) node.setAttribute('name', selectNames[node.id]);
        else node.removeAttribute('name');
      }
    });
  }

  function sectionVisible(fs, show) {
    if (!fs) return;
    fs.style.display = show ? '' : 'none';
    fs.disabled = !show;
  }

  if (form && sid) {
    form.addEventListener('submit', function () {
      if (pendingQuickStudentId !== null && pendingQuickStudentId !== '') {
        sid.value = pendingQuickStudentId;
      }
      sid.removeAttribute('disabled');
    });
  }

  document.querySelectorAll('.quick-contact-open').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!sid || !form || !lbl) return;
      var payload = {};
      try {
        payload = JSON.parse(btn.getAttribute('data-payload') || '{}');
      } catch (e) {
        payload = {};
      }
      if (!payload || typeof payload !== 'object') payload = {};

      var fallbackId = btn.getAttribute('data-student-id');
      var rawId = payload.student_id !== undefined && payload.student_id !== null ? payload.student_id : fallbackId;
      pendingQuickStudentId = rawId !== undefined && rawId !== null && rawId !== '' ? String(rawId) : null;

      sid.value = pendingQuickStudentId || '';
      lbl.textContent = payload.label || '';

      var parseFailed = Object.keys(payload).length === 0 && !fallbackId;
      var forceAll = parseFailed || payload.force_all === true;

      setHidden('quick_return_route', payload.return_route || 'families.integrity-report.missing-contacts');
      setHidden('quick_ret_dup_limit', payload.ret_dup_limit);
      setHidden('quick_ret_both_page', payload.ret_both_page);
      setHidden('quick_ret_one_page', payload.ret_one_page);
      setHidden('quick_ret_per_both', payload.ret_per_both);
      setHidden('quick_ret_per_one', payload.ret_per_one);
      setHidden('quick_ret_q', payload.ret_q);

      function empt(k) { return forceAll || payload[k] === true; }

      // Prefill existing values (if present) and disable rows that already have data,
      // while keeping them visible so staff can confirm what's already set.
      var fields = [
        { row: 'father_name', editable: empt('father_name_empty'), el: 'quick_father_name', val: payload.father_name || '' },
        { row: 'father_phone', editable: empt('father_phone_empty'), el: 'quick_father_phone', val: payload.father_phone_local || '' },
        { row: 'father_whatsapp', editable: empt('father_whatsapp_empty'), el: 'quick_father_whatsapp', val: payload.father_whatsapp_local || '' },
        { row: 'father_email', editable: empt('father_email_empty'), el: 'quick_father_email', val: payload.father_email || '' },
        { row: 'mother_name', editable: empt('mother_name_empty'), el: 'quick_mother_name', val: payload.mother_name || '' },
        { row: 'mother_phone', editable: empt('mother_phone_empty'), el: 'quick_mother_phone', val: payload.mother_phone_local || '' },
        { row: 'mother_whatsapp', editable: empt('mother_whatsapp_empty'), el: 'quick_mother_whatsapp', val: payload.mother_whatsapp_local || '' },
        { row: 'mother_email', editable: empt('mother_email_empty'), el: 'quick_mother_email', val: payload.mother_email || '' },
      ];

      fields.forEach(function (f) {
        var rowEl = document.querySelector('[data-quick-row="' + f.row + '"]');
        rowEditable(rowEl, !!f.editable);
        var input = document.getElementById(f.el);
        if (input) input.value = f.val || '';
      });

      // Always show both sections for verification (even if only one side has blanks).
      sectionVisible(fatherFs, true);
      sectionVisible(motherFs, true);

      setSelectValue(sel.father_cc, payload.father_cc || '+254');
      setSelectValue(sel.father_wa_cc, payload.father_wa_cc || payload.father_cc || '+254');
      setSelectValue(sel.mother_cc, payload.mother_cc || '+254');
      setSelectValue(sel.mother_wa_cc, payload.mother_wa_cc || payload.mother_cc || '+254');

      modal.show();
    });
  });

  modalEl.addEventListener('hidden.bs.modal', function () {
    if (!form) return;
    pendingQuickStudentId = null;
    if (sid) sid.value = '';
    form.reset();
    sectionVisible(fatherFs, true);
    sectionVisible(motherFs, true);
    ['father_name','father_phone','father_whatsapp','father_email'].forEach(function (key) {
      rowEditable(document.querySelector('#quick_father_fs [data-quick-row="' + key + '"]'), true);
    });
    ['mother_name','mother_phone','mother_whatsapp','mother_email'].forEach(function (key) {
      rowEditable(document.querySelector('#quick_mother_fs [data-quick-row="' + key + '"]'), true);
    });
  });
})();
</script>
@endpush
