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
        <p class="text-muted mb-0">Create or extend a family by linking 2-4 students (2 required).</p>
      </div>
      <a href="{{ route('families.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @include('students.partials.alerts')

    <div class="row g-3">
      <div class="col-lg-7">
        <div class="settings-card h-100">
          <div class="card-header d-flex align-items-center justify-content-between">
            <span>1. Find students</span>
            <span class="text-muted small">Select 2-4 students</span>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Search by name or admission number</label>
              <input type="text" id="student_search" class="form-control" placeholder="Type to search...">
            </div>
            <div id="student_results" class="list-group"></div>
          </div>
        </div>
      </div>

      <div class="col-lg-5">
        <form action="{{ route('families.link.store') }}" method="POST" id="linkForm" class="settings-card h-100">
          @csrf
          <div class="card-header d-flex align-items-center justify-content-between">
            <span>2. Selected students</span>
            <span class="badge text-bg-secondary" id="selected_count">0/4</span>
          </div>
          <div class="card-body">
            <div class="small text-muted mb-2">Pick at least two students. Up to four can be linked at once.</div>
            <div id="selected_list" class="vstack gap-2 mb-3"></div>
            <div id="hidden_inputs"></div>
            <button type="submit" class="btn btn-settings-primary w-100" id="link_button" disabled>
              <i class="bi bi-link-45deg"></i> Link Students as Siblings
            </button>
          </div>
        </form>
      </div>
    </div>

    <div class="alert alert-soft border-0 mt-3">
      <i class="bi bi-info-circle"></i> Linking students enables family-level billing, sibling discounts, and unified family communication. Guardian details are pulled from student parent records and can be edited later from the family page.
    </div>
  </div>
</div>

@include('partials.student_search_modal')

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectionLimit = 4;
    const minimumSelection = 2;
    let searchTimeout = null;
    let selectedStudents = [];

    const searchInput = document.getElementById('student_search');
    const resultsEl = document.getElementById('student_results');
    const selectedListEl = document.getElementById('selected_list');
    const selectedCountEl = document.getElementById('selected_count');
    const hiddenInputsEl = document.getElementById('hidden_inputs');
    const linkButton = document.getElementById('link_button');

    function renderMessage(target, message) {
        target.innerHTML = `<div class="list-group-item text-center text-muted">${message}</div>`;
    }

    function renderSearching(target) {
        target.innerHTML = '<div class="list-group-item text-center">Searching...</div>';
    }

    function updateHiddenInputs() {
        hiddenInputsEl.innerHTML = '';
        selectedStudents.forEach(student => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'student_ids[]';
            input.value = student.id;
            hiddenInputsEl.appendChild(input);
        });
    }

    function updateButtonState() {
        const count = selectedStudents.length;
        const validCount = count >= minimumSelection && count <= selectionLimit;
        linkButton.disabled = !validCount;
        linkButton.innerHTML = validCount
            ? '<i class="bi bi-link-45deg"></i> Link Students as Siblings'
            : `<i class="bi bi-link-45deg"></i> Select ${minimumSelection}-${selectionLimit} students`;
    }

    function renderSelected() {
        selectedCountEl.textContent = `${selectedStudents.length}/${selectionLimit}`;

        if (selectedStudents.length === 0) {
            selectedListEl.innerHTML = '<div class="text-muted small">No students selected yet.</div>';
            updateHiddenInputs();
            updateButtonState();
            return;
        }

        selectedListEl.innerHTML = selectedStudents.map(student => `
            <div class="d-flex align-items-center justify-content-between border rounded p-2">
                            <div>
                    <div class="fw-semibold">${student.adm} — ${student.name}</div>
                    ${student.classroom ? `<div class="text-muted small">${student.classroom}</div>` : ''}
                            </div>
                <button type="button" class="btn btn-sm btn-ghost-strong removeSelected" data-id="${student.id}" aria-label="Remove ${student.name}">
                    <i class="bi bi-x-lg"></i>
                </button>
                        </div>
                `).join('');

        document.querySelectorAll('.removeSelected').forEach(btn => {
            btn.addEventListener('click', function() {
                const idToRemove = this.getAttribute('data-id');
                selectedStudents = selectedStudents.filter(s => String(s.id) !== String(idToRemove));
                renderSelected();
                // Re-render results so removed students become selectable again
                if (searchInput.value.trim().length >= 2) {
                    searchStudents(searchInput.value.trim());
                }
                    });
                });

        updateHiddenInputs();
        updateButtonState();
    }

    function addStudent(student) {
        const alreadySelected = selectedStudents.some(s => String(s.id) === String(student.id));
        if (alreadySelected || selectedStudents.length >= selectionLimit) {
            return;
        }
        selectedStudents.push(student);
        renderSelected();
        searchInput.value = '';
        resultsEl.innerHTML = '';
    }

    function renderResults(data) {
        if (!data || data.length === 0) {
            renderMessage(resultsEl, 'No students found.');
            return;
        }

        resultsEl.innerHTML = data.map(stu => {
            const isSelected = selectedStudents.some(s => String(s.id) === String(stu.id));
            const atLimit = selectedStudents.length >= selectionLimit;
            const disabled = isSelected || atLimit;

                    return `
                <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center ${disabled ? 'disabled' : 'selectResult'}"
                        data-id="${stu.id}"
                        data-name="${stu.full_name}"
                        data-adm="${stu.admission_number}"
                        data-classroom="${stu.classroom_name || ''}"
                        ${disabled ? 'disabled' : ''}>
                                <div>
                                    <strong>${stu.admission_number}</strong> — ${stu.full_name}
                        ${stu.classroom_name ? '<br><small class="text-muted">' + stu.classroom_name + '</small>' : ''}
                        ${isSelected ? '<br><small class="text-success">Selected</small>' : ''}
                        ${(!isSelected && atLimit) ? '<br><small class="text-muted">Maximum selected</small>' : ''}
                                </div>
                    <span class="badge text-bg-primary">${isSelected ? 'Added' : 'Select'}</span>
                </button>
                    `;
                }).join('');

        document.querySelectorAll('.selectResult').forEach(btn => {
            btn.addEventListener('click', function() {
                addStudent({
                    id: this.dataset.id,
                    name: this.dataset.name,
                    adm: this.dataset.adm,
                    classroom: this.dataset.classroom
                });
                // Re-render results to reflect disabled state when at limit
                if (searchInput.value.trim().length >= 2) {
                    searchStudents(searchInput.value.trim());
                }
            });
        });
    }

    async function searchStudents(query) {
        renderSearching(resultsEl);
        try {
            const res = await fetch(`{{ route('api.students.search') }}?q=${encodeURIComponent(query)}`);
            const data = await res.json();
            renderResults(data);
        } catch (error) {
            renderMessage(resultsEl, 'Unable to search right now.');
        }
    }

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        if (query.length < 2) {
            renderMessage(resultsEl, 'Start typing to search students.');
            return;
        }
        searchTimeout = setTimeout(() => searchStudents(query), 300);
    });

    // Initial state
    renderMessage(resultsEl, 'Start typing to search students.');
    renderSelected();
});
</script>
@endpush
@endsection

