<div class="modal fade student-search-modal" id="studentSearchModal" tabindex="-1" aria-labelledby="studentSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Search Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <input type="text" id="studentSearchInput" class="form-control" placeholder="Type a name or admission number...">
          <small class="text-muted">Results appear automatically after you pause typing (about 2 seconds).</small>
        </div>

        <div class="table-responsive student-search-results-container">
          <table class="table table-hover table-sm align-middle mb-0" id="studentSearchResults">
            <thead class="table-light">
              <tr>
                <th style="width: 18%">Admission #</th>
                <th style="width: 40%">Name</th>
                <th style="width: 27%">Class</th>
                <th style="width: 15%" class="text-end">Action</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="4" class="text-muted text-center">Start typing to search for students...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modalEl = document.getElementById('studentSearchModal');
    if (!modalEl) return;

    const input = modalEl.querySelector('#studentSearchInput');
    const resultsBody = modalEl.querySelector('#studentSearchResults tbody');
    // Use the general students.search route so all finance users can access it
    const searchUrl = `{{ route('students.search') }}`;
    const debounceMs = 2000; // wait ~2 seconds after typing stops
    let timer = null;

    const renderRows = (rows) => {
        if (!rows || !rows.length) {
            resultsBody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">No students found.</td></tr>';
            return;
        }

        resultsBody.innerHTML = rows.map(stu => {
            const adm = stu.admission_number || '—';
            const name = stu.full_name || '—';
            const cls = stu.classroom_name || '—';
            return `
                <tr>
                    <td class="fw-semibold">${adm}</td>
                    <td>${name}</td>
                    <td>${cls}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-primary selectStudentBtn"
                            data-id="${stu.id}"
                            data-name="${name}"
                            data-adm="${adm}"
                            data-class="${cls}">
                            Select
                        </button>
                    </td>
                </tr>
            `;
        }).join('');

        modalEl.querySelectorAll('.selectStudentBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                const student = {
                    id: btn.dataset.id,
                    full_name: btn.dataset.name,
                    admission_number: btn.dataset.adm,
                    classroom_name: btn.dataset.class
                };

                const displayName = `${student.full_name} (${student.admission_number})`;
                const studentIdField = document.getElementById('selectedStudentId');
                const studentNameField = document.getElementById('selectedStudentName');
                if (studentIdField) studentIdField.value = student.id;
                if (studentNameField) studentNameField.value = displayName;

                // Fire a global event so pages can react if they prefer
                const detail = {
                    id: student.id,
                    name: student.full_name,
                    adm: student.admission_number,
                    classroom: student.classroom_name
                };
                // Dispatch on both window and document for compatibility with existing listeners
                // Use setTimeout to ensure event listeners are ready
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('studentSelected', { detail }));
                    document.dispatchEvent(new CustomEvent('studentSelected', { detail }));
                }, 100);

                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                modalInstance?.hide();
            });
        });
    };

    const renderState = (message, isError = false) => {
        resultsBody.innerHTML = `<tr><td colspan="4" class="text-center ${isError ? 'text-danger' : 'text-muted'}">${message}</td></tr>`;
    };

    const doSearch = async () => {
        const q = input.value.trim();
        if (!q) {
            renderState('Start typing to search for students...');
            return;
        }
        renderState('Searching...');
        try {
            const res = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, {
                headers: { 'Accept': 'application/json' }
            });
            if (!res.ok) throw new Error('Search failed');
            const data = await res.json();
            renderRows(data);
        } catch (e) {
            console.error(e);
            renderState('Search failed. Please try again.', true);
        }
    };

    const debouncedSearch = () => {
        clearTimeout(timer);
        timer = setTimeout(doSearch, debounceMs);
    };

    input.addEventListener('input', debouncedSearch);

    modalEl.addEventListener('shown.bs.modal', () => {
        input?.focus();
        // Reset state when reopened
        if (!input.value.trim()) {
            renderState('Start typing to search for students...');
        } else {
            debouncedSearch();
        }
    });

    modalEl.addEventListener('hidden.bs.modal', () => {
        input.value = '';
        renderState('Start typing to search for students...');
        clearTimeout(timer);
    });
});
</script>
@endpush

@push('styles')
<style>
    .student-search-modal .modal-dialog {
        max-width: min(900px, 95vw);
    }
    .student-search-results-container {
        max-height: 60vh;
        overflow-y: auto;
    }
</style>
@endpush
