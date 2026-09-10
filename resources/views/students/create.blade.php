@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page ds-pilot">
  <div class="settings-shell">
    <x-nav.breadcrumb :items="[
      'Home' => Route::has('dashboard') ? route('dashboard') : (Route::has('home') ? route('home') : url('/')),
      'Students' => route('students.index'),
      'Create' => null,
    ]" class="mb-3" />

    <x-page-header eyebrow="Students" title="Student Admission" description="Add a new learner with profile and placement details." class="mb-3">
      <x-slot:actions>
        <a href="{{ url()->previous() ?: route('students.index') }}" class="btn btn-ghost-strong">
          <i class="bi bi-arrow-left" aria-hidden="true"></i> Back
        </a>
      </x-slot:actions>
    </x-page-header>

    <x-feedback.flash />
    @if($errors->any())
      <x-feedback.alert type="danger" title="Please fix the errors below">
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
      </x-feedback.alert>
    @endif

    <div class="admission-wizard" id="admissionWizard" aria-label="Student admission progress">
      <div class="admission-wizard-toolbar">
        <div>
          <span class="admission-wizard-kicker">Admission workflow</span>
          <p class="mb-0 text-muted" id="admissionStepStatus" aria-live="polite">Step 1 of 5: Student Information</p>
        </div>
        <button type="button" class="btn btn-ghost-strong btn-sm" id="saveAdmissionDraft">
          <i class="bi bi-save2" aria-hidden="true"></i> Save draft
        </button>
      </div>
      <ol class="admission-stepper" role="list">
        @foreach(['Student Information', 'Parent / Guardian', 'Academic Information', 'Additional Information', 'Review & Submit'] as $stepIndex => $stepLabel)
          <li class="admission-step" data-step="{{ $stepIndex + 1 }}">
            <button type="button" class="admission-step-button" aria-label="Go to {{ $stepLabel }}" aria-current="{{ $stepIndex === 0 ? 'step' : 'false' }}">
              <span class="admission-step-number">{{ $stepIndex + 1 }}</span>
              <span class="admission-step-label">{{ $stepLabel }}</span>
            </button>
          </li>
        @endforeach
      </ol>
      <div class="admission-draft-status" id="admissionDraftStatus" role="status" aria-live="polite"></div>
    </div>

    <form action="{{ route('students.store') }}" method="POST" enctype="multipart/form-data" class="settings-card admission-wizard-form" id="studentAdmissionForm">
      <div class="admission-review d-none" id="admissionWizardReview" aria-labelledby="admissionReviewTitle">
        <div class="admission-review-header">
          <div>
            <span class="admission-wizard-kicker">Final check</span>
            <h2 class="h5 mb-1" id="admissionReviewTitle">Review admission details</h2>
            <p class="text-muted mb-0">Confirm the information below before creating the student record.</p>
          </div>
          <i class="bi bi-clipboard-check" aria-hidden="true"></i>
        </div>
        <div class="admission-review-grid" id="admissionReviewContent"></div>
      </div>
      @include('students.partials.form', [
        'mode' => 'create',
        'countryCodes' => $countryCodes ?? [],
        // controller should pass these:
        // 'classrooms'=>$classrooms, 'streams'=>$streams, 'categories'=>$categories, 'trips'=>$trips, 'dropOffPoints'=>$dropOffPoints
      ])
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const wizard = document.getElementById('admissionWizard');
  const form = document.getElementById('studentAdmissionForm');
  if (!wizard || !form) return;

  const stepNames = ['Student Information', 'Parent / Guardian', 'Academic Information', 'Additional Information', 'Review & Submit'];
  const draftKey = 'school-erp.student-admission.draft.v1';
  const sections = [];
  let currentStep = 1;

  function stepForHeading(text) {
    const value = text.toLowerCase();
    if (value.includes('parent / guardian')) return 2;
    if (value.includes('class & category') || value.includes('status & lifecycle')) return 3;
    if (value.includes('medical') || value.includes('special needs') || value.includes('transport') || value.includes('documents')) return 4;
    return 1;
  }

  function buildSections() {
    const body = form.querySelector('.card-body');
    const headings = body ? Array.from(body.querySelectorAll(':scope > h6')) : [];
    headings.forEach((heading) => {
      const section = document.createElement('section');
      const step = stepForHeading(heading.textContent || '');
      section.className = 'admission-step-panel';
      section.dataset.step = String(step);
      section.setAttribute('aria-labelledby', `admission-section-${sections.length}`);
      heading.id = `admission-section-${sections.length}`;
      heading.classList.add('admission-section-title');
      heading.parentNode.insertBefore(section, heading);
      section.appendChild(heading);
      let next = section.nextElementSibling;
      while (next && !next.matches('h6, .card-footer')) {
        const move = next;
        next = next.nextElementSibling;
        section.appendChild(move);
      }
      sections.push(section);
    });
    body?.querySelectorAll(':scope > hr').forEach((divider) => divider.remove());
  }

  function controlsFor(step) {
    return sections.filter((section) => Number(section.dataset.step) === step)
      .flatMap((section) => Array.from(section.querySelectorAll('input, select, textarea')))
      .filter((control) => !control.disabled && control.type !== 'hidden' && control.type !== 'file');
  }

  function markStepErrors() {
    const errorNames = @json($errors->keys());
    errorNames.forEach((name) => {
      form.querySelectorAll(`[name="${CSS.escape(name)}"]`).forEach((control) => {
        control.classList.add('is-invalid');
        control.setAttribute('aria-invalid', 'true');
      });
    });
    const firstError = sections.find((section) => section.querySelector('.is-invalid'));
    if (firstError) currentStep = Number(firstError.dataset.step);
  }

  function valueFor(control) {
    if (control.type === 'checkbox' || control.type === 'radio') return control.checked ? 'Yes' : '';
    if (control.tagName === 'SELECT') return control.selectedOptions[0]?.textContent?.trim() || 'Not selected';
    return control.value.trim() || 'Not provided';
  }

  function renderReview() {
    const review = document.getElementById('admissionReviewContent');
    if (!review) return;
    review.innerHTML = '';
    [1, 2, 3, 4].forEach((step) => {
      const card = document.createElement('section');
      card.className = 'admission-review-card';
      card.innerHTML = `<h3 class="h6">${stepNames[step - 1]}</h3>`;
      const list = document.createElement('dl');
      list.className = 'admission-review-list';
      const seen = new Set();
      controlsFor(step).forEach((control) => {
        if (!control.name || seen.has(control.name) || (control.type === 'radio' && !control.checked) || (control.type === 'checkbox' && !control.checked)) return;
        seen.add(control.name);
        const label = form.querySelector(`label[for="${CSS.escape(control.id)}"]`) || control.closest('[class*="col-"]')?.querySelector('label');
        const term = document.createElement('dt');
        const detail = document.createElement('dd');
        term.textContent = label?.textContent?.replace('*', '').trim() || control.name.replaceAll('_', ' ');
        detail.textContent = valueFor(control);
        list.append(term, detail);
      });
      card.appendChild(list);
      review.appendChild(card);
    });
  }

  function renderStep() {
    sections.forEach((section) => {
      const visible = Number(section.dataset.step) === currentStep && currentStep < 5;
      section.hidden = !visible;
    });
    const review = document.getElementById('admissionWizardReview');
    review?.classList.toggle('d-none', currentStep !== 5);
    if (currentStep === 5) renderReview();
    wizard.querySelectorAll('.admission-step').forEach((item) => {
      const step = Number(item.dataset.step);
      item.classList.toggle('is-active', step === currentStep);
      item.classList.toggle('is-complete', step < currentStep);
      const button = item.querySelector('button');
      button?.setAttribute('aria-current', step === currentStep ? 'step' : 'false');
    });
    const status = document.getElementById('admissionStepStatus');
    if (status) status.textContent = `Step ${currentStep} of 5: ${stepNames[currentStep - 1]}`;
    form.querySelector('[data-wizard-previous]')?.toggleAttribute('hidden', currentStep <= 1);
    form.querySelectorAll('[data-wizard-next]').forEach((button) => button.toggleAttribute('hidden', currentStep >= 5));
    form.querySelectorAll('[data-wizard-submit], [data-wizard-save-another]').forEach((button) => button.toggleAttribute('hidden', currentStep !== 5));
  }

  function validateStep(step) {
    let valid = true;
    controlsFor(step).forEach((control) => {
      control.classList.remove('is-invalid');
      if (!control.checkValidity()) {
        valid = false;
        control.classList.add('is-invalid');
        control.setAttribute('aria-invalid', 'true');
      }
    });
    if (!valid) sections.find((section) => Number(section.dataset.step) === step)?.querySelector('.is-invalid')?.focus();
    return valid;
  }

  function saveDraft() {
    const data = {};
    Array.from(form.elements).forEach((control) => {
      if (!control.name || control.type === 'file' || control.type === 'submit' || control.type === 'button' || control.name === '_token') return;
      if ((control.type === 'checkbox' || control.type === 'radio') && !control.checked) return;
      data[control.name] = control.value;
    });
    sessionStorage.setItem(draftKey, JSON.stringify(data));
    const status = document.getElementById('admissionDraftStatus');
    if (status) status.textContent = 'Draft saved in this browser tab. Uploaded files must be selected again before submitting.';
  }

  function restoreDraft() {
    try {
      const data = JSON.parse(sessionStorage.getItem(draftKey) || 'null');
      if (!data) return;
      Object.entries(data).forEach(([name, value]) => {
        form.querySelectorAll(`[name="${CSS.escape(name)}"]`).forEach((control) => {
          if (control.type === 'checkbox' || control.type === 'radio') control.checked = control.value === value;
          else control.value = value;
        });
      });
      document.getElementById('admissionDraftStatus').textContent = 'A saved draft was restored. Review it before submitting.';
    } catch (error) {
      sessionStorage.removeItem(draftKey);
    }
  }

  buildSections();
  markStepErrors();
  restoreDraft();
  renderStep();
  wizard.querySelectorAll('.admission-step-button').forEach((button) => button.addEventListener('click', () => {
    const target = Number(button.closest('.admission-step')?.dataset.step);
    if (target < currentStep || (target === currentStep + 1 && validateStep(currentStep))) {
      currentStep = target;
      renderStep();
    }
  }));
  document.getElementById('saveAdmissionDraft')?.addEventListener('click', saveDraft);
  form.querySelector('[data-wizard-previous]')?.addEventListener('click', () => { currentStep = Math.max(1, currentStep - 1); renderStep(); });
  form.querySelectorAll('[data-wizard-next]').forEach((button) => button.addEventListener('click', () => {
    if (validateStep(currentStep)) { currentStep = Math.min(5, currentStep + 1); renderStep(); }
  }));
  form.addEventListener('submit', (event) => {
    if (currentStep !== 5) { event.preventDefault(); return; }
    if (!form.checkValidity()) {
      event.preventDefault();
      const firstInvalid = form.querySelector(':invalid');
      const owner = firstInvalid?.closest('.admission-step-panel');
      if (owner) currentStep = Number(owner.dataset.step);
      firstInvalid?.classList.add('is-invalid');
      renderStep();
      firstInvalid?.focus();
      return;
    }
    sessionStorage.removeItem(draftKey);
    const submitButton = form.querySelector('[data-wizard-submit]');
    if (submitButton) {
      submitButton.setAttribute('data-loading', 'true');
      submitButton.setAttribute('aria-busy', 'true');
      submitButton.disabled = true;
      submitButton.insertAdjacentHTML('afterbegin', '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>');
      submitButton.append(' Submitting...');
    }
  });
})();

(function () {
  const wrap = document.getElementById('duplicate-confirm-wrap');
  const list = document.getElementById('duplicate-match-list');
  const message = document.getElementById('duplicate-confirm-message');
  if (!wrap || !list) return;

  const checkUrl = @json(route('students.duplicate-check'));
  const form = document.getElementById('studentAdmissionForm');
  let timer = null;

  const field = (name) => form?.querySelector(`[name="${name}"]`);

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (ch) => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
  }

  function renderMatches(matches) {
    if (!matches.length) {
      list.innerHTML = '';
      wrap.classList.add('d-none');
      return;
    }
    wrap.classList.remove('d-none');
    list.innerHTML = '<ul class="list-unstyled mb-0">' + matches.map((match) => {
      const extra = match.admission_number
        ? ` (${escapeHtml(match.admission_number)})`
        : (match.application_no ? ` (${escapeHtml(match.application_no)})` : '');
      const badgeClass = match.confidence === 'high' ? 'bg-danger' : 'bg-warning text-dark';
      const open = match.url
        ? `<a href="${escapeHtml(match.url)}" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener">Open</a>`
        : '';
      return `<li class="d-flex flex-wrap align-items-start justify-content-between gap-2 py-2 border-bottom">
        <div>
          <div class="fw-semibold">${escapeHtml(match.full_name)}${extra}</div>
          <div class="small text-muted">${escapeHtml(match.source_label || '')}${match.status ? ' · ' + escapeHtml(match.status) : ''}${match.classroom ? ' · ' + escapeHtml(match.classroom) : ''}</div>
          <div class="small"><span class="badge ${badgeClass}">${escapeHtml(match.reason_label || '')}</span></div>
        </div>
        ${open}
      </li>`;
    }).join('') + '</ul>';
  }

  function runCheck() {
    const first = (field('first_name')?.value || '').trim();
    const last = (field('last_name')?.value || '').trim();
    const dob = (field('dob')?.value || '').trim();
    const nemis = (field('nemis_number')?.value || '').trim();
    const knec = (field('knec_assessment_number')?.value || '').trim();
    if ((!first || !last || !dob) && !nemis && !knec) {
      if (!@json(!empty(session('duplicate_matches')))) {
        renderMatches([]);
      }
      return;
    }
    const params = new URLSearchParams({
      first_name: first,
      middle_name: field('middle_name')?.value || '',
      last_name: last,
      dob,
      gender: field('gender')?.value || '',
      nemis_number: nemis,
      knec_assessment_number: knec,
      admission_number: field('admission_number')?.value || '',
    });
    fetch(checkUrl + '?' + params.toString(), {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
      .then((r) => r.json())
      .then((data) => {
        if (data.message) message.textContent = data.message;
        renderMatches(data.matches || []);
      })
      .catch(() => {});
  }

  ['first_name', 'middle_name', 'last_name', 'dob', 'gender', 'nemis_number', 'knec_assessment_number', 'admission_number']
    .forEach((name) => {
      const el = field(name);
      if (!el) return;
      el.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(runCheck, 450);
      });
      el.addEventListener('change', () => {
        clearTimeout(timer);
        timer = setTimeout(runCheck, 150);
      });
    });
})();
</script>
@endpush
