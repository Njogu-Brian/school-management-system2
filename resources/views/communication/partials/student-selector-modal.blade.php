{{-- Multi-Student Selector Modal with Search --}}
<div class="modal fade" id="studentSelectorModal" tabindex="-1" aria-labelledby="studentSelectorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="studentSelectorModalLabel">
                    <i class="bi bi-people-fill"></i> Select Students
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- Search Bar --}}
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" 
                               id="studentSearchInput" 
                               class="form-control" 
                               placeholder="Search by name, admission number, or class...">
                    </div>
                    <small class="text-muted">Start typing to filter students</small>
                </div>

                {{-- Quick Actions --}}
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="selectAllStudents">
                        <i class="bi bi-check-all"></i> Select All
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearAllStudents">
                        <i class="bi bi-x-circle"></i> Clear All
                    </button>
                    <span class="badge bg-info ms-auto align-self-center" id="selectedCount">0 selected</span>
                </div>

                {{-- Class Filter --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Filter by Class</label>
                    <select id="classFilterSelect" class="form-select form-select-sm">
                        <option value="">All Classes</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Students List with Checkboxes --}}
                <div id="studentsListContainer" style="max-height: 400px; overflow-y: auto;">
                    <div class="list-group" id="studentsList">
                        @foreach($students as $student)
                            <label class="list-group-item list-group-item-action student-item" 
                                   data-student-id="{{ $student->id }}"
                                   data-student-name="{{ strtolower($student->full_name) }}"
                                   data-admission="{{ strtolower($student->admission_number ?? $student->admission_no ?? '') }}"
                                   data-class-id="{{ $student->classroom_id ?? '' }}"
                                   data-class-name="{{ strtolower(optional($student->classroom)->name ?? '') }}">
                                <div class="d-flex align-items-center">
                                    <input class="form-check-input me-3 student-checkbox" 
                                           type="checkbox" 
                                           value="{{ $student->id }}"
                                           id="student_{{ $student->id }}">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">
                                            {{ $student->full_name }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $student->admission_number ?? $student->admission_no ?? 'N/A' }} 
                                            @if($student->classroom)
                                                | {{ $student->classroom->name }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div id="noStudentsFound" class="alert alert-info d-none mt-3">
                    <i class="bi bi-info-circle"></i> No students found matching your search.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmStudentSelection">
                    <i class="bi bi-check-lg"></i> Confirm Selection
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('studentSelectorModal');
    const searchInput = document.getElementById('studentSearchInput');
    const classFilter = document.getElementById('classFilterSelect');
    const selectAllBtn = document.getElementById('selectAllStudents');
    const clearAllBtn = document.getElementById('clearAllStudents');
    const confirmBtn = document.getElementById('confirmStudentSelection');
    const selectedCountBadge = document.getElementById('selectedCount');
    const studentsContainer = document.getElementById('studentsList');
    const noStudentsFound = document.getElementById('noStudentsFound');
    const allStudentItems = document.querySelectorAll('.student-item');
    const allCheckboxes = document.querySelectorAll('.student-checkbox');

    let selectedStudentIds = [];

    // Update selected count
    function updateSelectedCount() {
        const count = document.querySelectorAll('.student-checkbox:checked').length;
        selectedCountBadge.textContent = `${count} selected`;
        selectedStudentIds = Array.from(document.querySelectorAll('.student-checkbox:checked'))
            .map(cb => cb.value);
    }

    // Filter students based on search and class filter
    function filterStudents() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const selectedClass = classFilter.value;
        let visibleCount = 0;

        allStudentItems.forEach(item => {
            const name = item.dataset.studentName || '';
            const admission = item.dataset.admission || '';
            const classId = item.dataset.classId || '';
            const className = item.dataset.className || '';
            
            const matchesSearch = !searchTerm || 
                name.includes(searchTerm) || 
                admission.includes(searchTerm) ||
                className.includes(searchTerm);
            
            const matchesClass = !selectedClass || classId === selectedClass;
            
            if (matchesSearch && matchesClass) {
                item.classList.remove('d-none');
                visibleCount++;
            } else {
                item.classList.add('d-none');
            }
        });

        noStudentsFound.classList.toggle('d-none', visibleCount > 0);
    }

    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', filterStudents);
    }

    if (classFilter) {
        classFilter.addEventListener('change', filterStudents);
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', () => {
            const visibleCheckboxes = Array.from(allCheckboxes).filter(cb => {
                return !cb.closest('.student-item').classList.contains('d-none');
            });
            visibleCheckboxes.forEach(cb => cb.checked = true);
            updateSelectedCount();
        });
    }

    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', () => {
            allCheckboxes.forEach(cb => cb.checked = false);
            updateSelectedCount();
        });
    }

    // Update count on checkbox change
    allCheckboxes.forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });

    // Confirm selection
    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            // Dispatch custom event with selected IDs
            const event = new CustomEvent('studentsSelected', {
                detail: { studentIds: selectedStudentIds }
            });
            document.dispatchEvent(event);
            
            // Close modal
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        });
    }

    // Reset search when modal is closed
    if (modal) {
        modal.addEventListener('hidden.bs.modal', () => {
            searchInput.value = '';
            classFilter.value = '';
            filterStudents();
        });
    }

    // Initialize count
    updateSelectedCount();
});
</script>
@endpush

<style>
.student-item {
    cursor: pointer;
    transition: background-color 0.2s;
}

.student-item:hover {
    background-color: rgba(0, 123, 255, 0.05);
}

.student-item .form-check-input {
    cursor: pointer;
}

.student-item .form-check-input:checked ~ .flex-grow-1 {
    color: #0d6efd;
}

#studentsListContainer {
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.5rem;
}
</style>




