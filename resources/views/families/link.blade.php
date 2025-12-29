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
        <p class="text-muted mb-0">Create or extend a family by linking two students.</p>
      </div>
      <a href="{{ route('families.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @include('students.partials.alerts')

    <div class="row g-3">
      <div class="col-lg-6">
        <div class="settings-card h-100">
          <div class="card-header">1. Search First Student</div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Search by name or admission number</label>
              <input type="text" id="studentA_search" class="form-control" placeholder="Type to search...">
            </div>
            <div id="studentA_results" class="list-group"></div>
            <div id="studentA_selected" class="mt-3"></div>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="settings-card h-100">
          <div class="card-header">2. Search Second Student</div>
          <div class="card-body">
            <div class="mb-3">
              <label class="form-label">Search by name or admission number</label>
              <input type="text" id="studentB_search" class="form-control" placeholder="Type to search..." disabled>
            </div>
            <div id="studentB_results" class="list-group"></div>
            <div id="studentB_selected" class="mt-3"></div>
          </div>
        </div>

        <form action="{{ route('families.link.store') }}" method="POST" id="linkForm" style="display: none;" class="settings-card mt-3">
          @csrf
          <input type="hidden" name="student_a_id" id="student_a_id">
          <input type="hidden" name="student_b_id" id="student_b_id">
          <div class="card-body">
            <button type="submit" class="btn btn-settings-primary w-100">
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
    let studentA = null;
    let studentB = null;
    let searchTimeoutA = null;
    let searchTimeoutB = null;

    const searchA = document.getElementById('studentA_search');
    const resultsA = document.getElementById('studentA_results');
    const selectedA = document.getElementById('studentA_selected');
    const searchB = document.getElementById('studentB_search');
    const resultsB = document.getElementById('studentB_results');
    const selectedB = document.getElementById('studentB_selected');
    const linkForm = document.getElementById('linkForm');
    const studentAIdInput = document.getElementById('student_a_id');
    const studentBIdInput = document.getElementById('student_b_id');

    function renderSearching(target){ target.innerHTML = '<div class=\"list-group-item text-center\">Searching...</div>'; }
    function renderEmpty(target){ target.innerHTML = '<div class=\"list-group-item text-center text-muted\">No students found.</div>'; }

    searchA.addEventListener('input', function() {
        clearTimeout(searchTimeoutA);
        const query = this.value.trim();
        if (query.length < 2) { resultsA.innerHTML = ''; return; }
        searchTimeoutA = setTimeout(async () => {
            renderSearching(resultsA);
            try {
                const res = await fetch(`{{ route('api.students.search') }}?q=${encodeURIComponent(query)}`);
                const data = await res.json();
                if (data.length === 0) { renderEmpty(resultsA); return; }
                // Use a single interactive element (button). Avoid nesting <button> inside <a> which is invalid HTML
                // and can prevent click handlers from firing reliably.
                resultsA.innerHTML = data.map(stu => `
                    <button type="button" class="list-group-item list-group-item-action selectStudentA"
                       data-id="${stu.id}" data-name="${stu.full_name}" data-adm="${stu.admission_number}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong>${stu.admission_number}</strong> — ${stu.full_name}
                                ${stu.classroom_name ? '<br><small class="text-muted">' + stu.classroom_name + '</small>' : ''}
                            </div>
                            <span class="btn btn-sm btn-settings-primary">Select</span>
                        </div>
                    </button>
                `).join('');
                document.querySelectorAll('.selectStudentA').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        studentA = { id: this.dataset.id, name: this.dataset.name, adm: this.dataset.adm };
                        selectedA.innerHTML = `
                            <div class=\"alert alert-success mb-0\">
                                <strong>Selected:</strong> ${studentA.adm} — ${studentA.name}
                                <button type=\"button\" class=\"btn btn-sm btn-ghost-strong float-end\" onclick=\"clearStudentA()\">Clear</button>
                            </div>
                        `;
                        resultsA.innerHTML = '';
                        searchA.value = '';
                        searchB.disabled = false;
                        studentAIdInput.value = studentA.id;
                        checkFormReady();
                    });
                });
            } catch (error) { renderEmpty(resultsA); }
        }, 300);
    });

    searchB.addEventListener('input', function() {
        clearTimeout(searchTimeoutB);
        const query = this.value.trim();
        if (query.length < 2) { resultsB.innerHTML = ''; return; }
        searchTimeoutB = setTimeout(async () => {
            renderSearching(resultsB);
            try {
                const res = await fetch(`{{ route('api.students.search') }}?q=${encodeURIComponent(query)}`);
                const data = await res.json();
                if (data.length === 0) { renderEmpty(resultsB); return; }
                resultsB.innerHTML = data.map(stu => {
                    const isStudentA = studentA && parseInt(stu.id) === parseInt(studentA.id);
                    return `
                        <button type="button" class="list-group-item list-group-item-action ${isStudentA ? 'disabled' : 'selectStudentB'}"
                           data-id="${stu.id}" data-name="${stu.full_name}" data-adm="${stu.admission_number}" ${isStudentA ? 'style="opacity: 0.5;" disabled' : ''}>
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${stu.admission_number}</strong> — ${stu.full_name}
                                    ${stu.classroom_name ? '<br><small class="text-muted">' + stu.classroom_name + '</small>' : ''}
                                    ${isStudentA ? '<br><small class="text-danger">(Already selected as first student)</small>' : ''}
                                </div>
                                ${!isStudentA ? '<span class="btn btn-sm btn-settings-primary">Select</span>' : ''}
                            </div>
                        </button>
                    `;
                }).join('');
                document.querySelectorAll('.selectStudentB').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        studentB = { id: this.dataset.id, name: this.dataset.name, adm: this.dataset.adm };
                        selectedB.innerHTML = `
                            <div class=\"alert alert-success mb-0\">
                                <strong>Selected:</strong> ${studentB.adm} — ${studentB.name}
                                <button type=\"button\" class=\"btn btn-sm btn-ghost-strong float-end\" onclick=\"clearStudentB()\">Clear</button>
                            </div>
                        `;
                        resultsB.innerHTML = '';
                        searchB.value = '';
                        studentBIdInput.value = studentB.id;
                        checkFormReady();
                    });
                });
            } catch (error) { renderEmpty(resultsB); }
        }, 300);
    });

    function checkFormReady() { linkForm.style.display = (studentA && studentB) ? 'block' : 'none'; }

    window.clearStudentA = function() { studentA = null; selectedA.innerHTML=''; searchA.value=''; searchB.disabled=true; studentAIdInput.value=''; checkFormReady(); };
    window.clearStudentB = function() { studentB = null; selectedB.innerHTML=''; searchB.value=''; studentBIdInput.value=''; checkFormReady(); };
});
</script>
@endpush
@endsection

