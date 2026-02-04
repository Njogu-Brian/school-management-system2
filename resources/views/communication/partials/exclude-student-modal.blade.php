{{-- Modal: Select students to EXCLUDE from communication --}}
<div class="modal fade" id="excludeStudentSelectorModal" tabindex="-1" aria-labelledby="excludeStudentSelectorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="excludeStudentSelectorModalLabel">
                    <i class="bi bi-person-x"></i> Exclude Students
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">Select students (or their parents) to exclude from this communication. They will not receive the message.</p>
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" id="excludeStudentSearchInput" class="form-control" placeholder="Search by name, admission number, or class...">
                    </div>
                </div>
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="excludeSelectAllStudents"><i class="bi bi-check-all"></i> Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="excludeClearAllStudents"><i class="bi bi-x-circle"></i> Clear All</button>
                    <span class="badge bg-secondary ms-auto align-self-center" id="excludeSelectedCount">0 excluded</span>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Filter by Class</label>
                    <select id="excludeClassFilterSelect" class="form-select form-select-sm">
                        <option value="">All Classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="excludeStudentsListContainer" style="max-height: 400px; overflow-y: auto;">
                    <div class="list-group" id="excludeStudentsList">
                        @foreach($students as $student)
                            <label class="list-group-item list-group-item-action exclude-student-item"
                                   data-student-id="{{ $student->id }}"
                                   data-student-name="{{ strtolower($student->full_name) }}"
                                   data-admission="{{ strtolower($student->admission_number ?? $student->admission_no ?? '') }}"
                                   data-class-id="{{ $student->classroom_id ?? '' }}"
                                   data-class-name="{{ strtolower(optional($student->classroom)->name ?? '') }}">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-3 exclude-student-checkbox" type="checkbox" value="{{ $student->id }}" id="exclude_student_{{ $student->id }}">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $student->full_name }}</div>
                                        <small class="text-muted">{{ $student->admission_number ?? $student->admission_no ?? 'N/A' }} @if($student->classroom)| {{ $student->classroom->name }}@endif</small>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
                <div id="excludeNoStudentsFound" class="alert alert-info d-none mt-3"><i class="bi bi-info-circle"></i> No students found matching your search.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmExcludeSelection"><i class="bi bi-check-lg"></i> Confirm Exclude</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('excludeStudentSelectorModal');
    if (!modal) return;
    const searchInput = document.getElementById('excludeStudentSearchInput');
    const classFilter = document.getElementById('excludeClassFilterSelect');
    const selectAllBtn = document.getElementById('excludeSelectAllStudents');
    const clearAllBtn = document.getElementById('excludeClearAllStudents');
    const confirmBtn = document.getElementById('confirmExcludeSelection');
    const selectedCountBadge = document.getElementById('excludeSelectedCount');
    const noStudentsFound = document.getElementById('excludeNoStudentsFound');
    const allStudentItems = document.querySelectorAll('.exclude-student-item');
    const allCheckboxes = document.querySelectorAll('.exclude-student-checkbox');

    let excludedIds = [];

    function updateExcludeCount() {
        const count = document.querySelectorAll('.exclude-student-checkbox:checked').length;
        if (selectedCountBadge) selectedCountBadge.textContent = count + ' excluded';
        excludedIds = Array.from(document.querySelectorAll('.exclude-student-checkbox:checked')).map(cb => cb.value);
    }

    function filterExcludeStudents() {
        const searchTerm = (searchInput && searchInput.value) ? searchInput.value.toLowerCase().trim() : '';
        const selectedClass = (classFilter && classFilter.value) ? classFilter.value : '';
        let visibleCount = 0;
        allStudentItems.forEach(item => {
            const name = item.dataset.studentName || '';
            const admission = item.dataset.admission || '';
            const classId = item.dataset.classId || '';
            const className = item.dataset.className || '';
            const matchesSearch = !searchTerm || name.includes(searchTerm) || admission.includes(searchTerm) || className.includes(searchTerm);
            const matchesClass = !selectedClass || classId === selectedClass;
            if (matchesSearch && matchesClass) {
                item.classList.remove('d-none');
                visibleCount++;
            } else {
                item.classList.add('d-none');
            }
        });
        if (noStudentsFound) noStudentsFound.classList.toggle('d-none', visibleCount > 0);
    }

    if (searchInput) searchInput.addEventListener('input', filterExcludeStudents);
    if (classFilter) classFilter.addEventListener('change', filterExcludeStudents);
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            document.querySelectorAll('.exclude-student-item:not(.d-none) .exclude-student-checkbox').forEach(cb => { cb.checked = true; });
            updateExcludeCount();
        });
    }
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            allCheckboxes.forEach(cb => { cb.checked = false; });
            updateExcludeCount();
        });
    }
    allCheckboxes.forEach(cb => cb.addEventListener('change', updateExcludeCount));

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            document.dispatchEvent(new CustomEvent('studentsExcluded', { detail: { studentIds: excludedIds } }));
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        });
    }
    if (modal) {
        modal.addEventListener('hidden.bs.modal', () => {
            if (searchInput) searchInput.value = '';
            if (classFilter) classFilter.value = '';
            filterExcludeStudents();
        });
    }
    updateExcludeCount();

    document.addEventListener('studentsExcluded', function(e) {
        const ids = e.detail.studentIds || [];
        const inp = document.getElementById('excludeStudentIds');
        if (inp) inp.value = ids.join(',');
        const disp = document.getElementById('excludeStudentsDisplay');
        if (disp) disp.classList.toggle('d-none', ids.length === 0);
        const badge = document.getElementById('excludeStudentsBadge');
        if (badge) badge.textContent = ids.length;
    });
});
</script>
@endpush
