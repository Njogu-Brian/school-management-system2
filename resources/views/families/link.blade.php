@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Families</div>
        <h1 class="mb-1">Link Students as Siblings</h1>
        <p class="text-muted mb-0">Create or extend a family by linking 2–4 students (2 required).</p>
      </div>
      <a href="{{ route('families.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @include('students.partials.alerts')

    <div class="row g-3">
      <div class="col-lg-7">
        <div class="settings-card">
          <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="fw-bold">1. Select Students (2–4)</span>
            <span class="pill-badge pill-secondary">Max 4</span>
          </div>
          <div class="card-body">
            <div class="mb-2">
              <label class="form-label mb-1">Search by name or admission number</label>
              <input type="text" id="studentSearch" class="form-control" placeholder="Type to search (min 2 characters)...">
              <div class="form-note mt-1">Tip: select 2 students to enable linking; you may add up to 2 more (max 4).</div>
            </div>

            <div id="studentResults" class="list-group mt-3"></div>

            <div class="mt-3">
              <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="fw-semibold">Selected</div>
                <button type="button" class="btn btn-sm btn-ghost-strong" id="clearSelectedBtn">
                  <i class="bi bi-x-circle"></i> Clear
                </button>
              </div>
              <div id="selectedStudents" class="vstack gap-2"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <form action="{{ route('families.link.store') }}" method="POST" id="linkForm" class="settings-card">
          @csrf
          <div class="card-header fw-bold">2. Link</div>
          <div class="card-body">
            <div id="selectedInputs"></div>
            <button type="submit" class="btn btn-settings-primary w-100" id="linkBtn" disabled>
              <i class="bi bi-link-45deg"></i> Link Students as Siblings
            </button>
            <div class="form-note mt-2" id="linkHint">Select at least 2 students to enable linking.</div>
          </div>
        </form>

        <div class="alert alert-soft border-0 mt-3 mb-0">
          <i class="bi bi-info-circle"></i> Linking students enables family-level billing, sibling discounts, and unified family communication. Guardian details are pulled from student parent records and can be edited later from the family page.
        </div>
      </div>
    </div>

  </div>
</div>

@include('partials.student_search_modal')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const MAX = 4;
    let selected = [];
    let searchTimeout = null;

    const searchInput = document.getElementById('studentSearch');
    const resultsEl = document.getElementById('studentResults');
    const selectedEl = document.getElementById('selectedStudents');
    const selectedInputsEl = document.getElementById('selectedInputs');
    const linkBtn = document.getElementById('linkBtn');
    const linkHint = document.getElementById('linkHint');
    const clearBtn = document.getElementById('clearSelectedBtn');

    const renderSearching = () => { resultsEl.innerHTML = '<div class="list-group-item text-center">Searching...</div>'; };
    const renderEmpty = (msg = 'No students found.') => { resultsEl.innerHTML = `<div class="list-group-item text-center text-muted">${msg}</div>`; };

    const isSelected = (id) => selected.some(s => String(s.id) === String(id));

    function sync() {
        // Selected list
        if (selected.length === 0) {
            selectedEl.innerHTML = '<div class="text-muted">No students selected yet.</div>';
        } else {
            selectedEl.innerHTML = selected.map((s, idx) => `
                <div class="d-flex align-items-center justify-content-between border rounded p-2">
                    <div>
                        <div class="fw-semibold">${s.adm || '—'} — ${s.name || '—'}</div>
                        ${s.classroom ? `<div class="text-muted small">${s.classroom}</div>` : ''}
                    </div>
                    <button type="button" class="btn btn-sm btn-ghost-strong" data-remove="${idx}">
                        <i class="bi bi-x-circle"></i> Remove
                    </button>
                </div>
            `).join('');
        }

        // Hidden inputs for submit
        selectedInputsEl.innerHTML = selected.map(s => `<input type="hidden" name="student_ids[]" value="${s.id}">`).join('');

        // Button state
        const ok = selected.length >= 2 && selected.length <= MAX;
        linkBtn.disabled = !ok;
        if (selected.length < 2) {
            linkHint.innerText = 'Select at least 2 students to enable linking.';
        } else if (selected.length > MAX) {
            linkHint.innerText = `You can select at most ${MAX} students.`;
        } else {
            linkHint.innerText = `Ready: ${selected.length} students selected.`;
        }

        // Wire remove handlers
        selectedEl.querySelectorAll('[data-remove]').forEach(btn => {
            btn.addEventListener('click', () => {
                const i = parseInt(btn.getAttribute('data-remove'), 10);
                if (!Number.isNaN(i)) {
                    selected.splice(i, 1);
                    sync();
                }
            });
        });
    }

    async function doSearch(query) {
        if (query.length < 2) { resultsEl.innerHTML = ''; return; }
        renderSearching();
        try {
            const res = await fetch(`{{ route('api.students.search') }}?q=${encodeURIComponent(query)}`, {
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (!Array.isArray(data) || data.length === 0) { renderEmpty(); return; }

            const isFull = selected.length >= MAX;
            resultsEl.innerHTML = data.map(stu => {
                const already = isSelected(stu.id);
                const disabled = already || isFull;
                const hint = already ? 'Already selected' : (isFull ? `Max ${MAX} selected` : 'Add');
                return `
                    <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ${disabled ? 'disabled' : 'addStudentBtn'}"
                        data-id="${stu.id}"
                        data-name="${stu.full_name || ''}"
                        data-adm="${stu.admission_number || ''}"
                        data-classroom="${stu.classroom_name || ''}"
                        ${disabled ? 'disabled style="opacity: 0.6;"' : ''}>
                        <div>
                            <div><strong>${stu.admission_number || '—'}</strong> — ${stu.full_name || '—'}</div>
                            ${stu.classroom_name ? `<small class="text-muted">${stu.classroom_name}</small>` : ''}
                        </div>
                        <span class="btn btn-sm ${disabled ? 'btn-secondary' : 'btn-settings-primary'}">${hint}</span>
                    </button>
                `;
            }).join('');

            resultsEl.querySelectorAll('.addStudentBtn').forEach(btn => {
                btn.addEventListener('click', () => {
                    if (selected.length >= MAX) return;
                    const id = btn.dataset.id;
                    if (isSelected(id)) return;
                    selected.push({
                        id,
                        name: btn.dataset.name,
                        adm: btn.dataset.adm,
                        classroom: btn.dataset.classroom
                    });
                    // reset search UI
                    searchInput.value = '';
                    resultsEl.innerHTML = '';
                    sync();
                });
            });
        } catch (e) {
            renderEmpty('Error searching students. Please try again.');
        }
    }

    searchInput?.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        if (query.length < 2) { resultsEl.innerHTML = ''; return; }
        searchTimeout = setTimeout(() => doSearch(query), 300);
    });

    clearBtn?.addEventListener('click', () => {
        selected = [];
        resultsEl.innerHTML = '';
        searchInput.value = '';
        sync();
    });

    // initial state
    sync();
});
</script>
@endpush
@endsection

