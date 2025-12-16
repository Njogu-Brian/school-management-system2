<div class="modal fade" id="studentSearchModal" tabindex="-1" aria-labelledby="studentSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Search Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="studentSearchInput" class="form-control mb-3" placeholder="Type name or admission number...">

        <table class="table table-hover table-sm" id="studentSearchResults">
          <thead>
            <tr>
              <th>Admission #</th>
              <th>Name</th>
              <th>Class</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            {{-- JS will populate here --}}
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('studentSearchInput');
    const results = document.querySelector('#studentSearchResults tbody');

    input.addEventListener('input', async function() {
        const query = this.value.trim();
        results.innerHTML = '<tr><td colspan="4">Searching...</td></tr>';
        if (query.length < 2) return;

        try {
            const res = await fetch(`{{ route('students.search') }}?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            });
            if (!res.ok) {
                throw new Error('Search failed');
            }
            const data = await res.json();

            results.innerHTML = data.length ? data.map(stu => `
                <tr>
                    <td>${stu.admission_number || ''}</td>
                    <td>${stu.full_name || ''}</td>
                    <td>${stu.classroom_name || 'N/A'}</td>
                    <td><button type="button" class="btn btn-sm btn-primary selectStudentBtn" 
                        data-id="${stu.id}" 
                        data-name="${stu.full_name || ''}" 
                        data-adm="${stu.admission_number || ''}">
                        Select
                    </button></td>
                </tr>
            `).join('') : '<tr><td colspan="4" class="text-muted">No results found.</td></tr>';

            document.querySelectorAll('.selectStudentBtn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const studentId = this.dataset.id;
                    const studentName = this.dataset.name;
                    const studentAdm = this.dataset.adm;
                    const displayName = `${studentName} (${studentAdm})`;
                    
                    const studentIdField = document.getElementById('selectedStudentId');
                    const studentNameField = document.getElementById('selectedStudentName');
                    
                    if (studentIdField) studentIdField.value = studentId;
                    if (studentNameField) studentNameField.value = displayName;
                    
                    // Enable the view statement button
                    const viewBtn = document.getElementById('viewStatementBtn');
                    if (viewBtn) {
                        viewBtn.disabled = false;
                        viewBtn.innerHTML = '<i class="bi bi-eye"></i> View Statement';
                        // Update onclick to use the new student ID
                        viewBtn.setAttribute('onclick', `viewStatement()`);
                    }
                    
                    const modal = bootstrap.Modal.getInstance(document.getElementById('studentSearchModal'));
                    if (modal) modal.hide();
                });
            });
        } catch (error) {
            console.error('Search error:', error);
            results.innerHTML = '<tr><td colspan="4" class="text-danger">Search failed. Please try again.</td></tr>';
        }
    });
});
</script>
@endpush
