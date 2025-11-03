{{-- Student-Based Optional Fees (attendance-like search) --}}

<form method="GET" action="{{ route('finance.optional_fees.student_view') }}" class="row g-3 mb-3" id="studentForm">
    <div class="col-md-6">
        <label class="form-label">Student</label>
        <div class="input-group">
            <input type="hidden" id="selectedStudentId" name="student_id" value="{{ $student->id ?? '' }}">
            <input type="text" id="selectedStudentName"
                   class="form-control" placeholder="Search by name or admission #"
                   value="{{ isset($student) ? ($student->full_name.' ('.$student->admission_number.')') : '' }}"
                   readonly>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#studentSearchModal">
                Search
            </button>
            <button class="btn btn-primary" type="submit">Load</button>
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label">Term</label>
        <select name="term" id="termSelect" class="form-select" required>
            <option value="">Select</option>
            @for($i = 1; $i <= 3; $i++)
                <option value="{{ $i }}" {{ request('term') == $i ? 'selected' : '' }}>Term {{ $i }}</option>
            @endfor
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Year</label>
        <input type="number" name="year" id="yearInput" value="{{ request('year') ?? now()->year }}" class="form-control" required>
    </div>
</form>

{{-- Results only when a student + term + year are present --}}
@if(request()->filled(['student_id','term','year']) && isset($student))
    <div class="card">
        <div class="card-header">
            <strong>Optional Fees for:</strong> {{ $student->full_name }} ({{ $student->admission_number }})
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('finance.optional_fees.save_student') }}">
                @csrf
                <input type="hidden" name="student_id" value="{{ $student->id }}">
                <input type="hidden" name="term" value="{{ request('term') }}">
                <input type="hidden" name="year" value="{{ request('year') }}">

                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Votehead</th>
                            <th class="text-center">Billing Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($voteheads as $votehead)
                            @php $status = $statuses[$votehead->id] ?? 'exempt'; @endphp
                            <tr>
                                <td>{{ $votehead->name }}</td>
                                <td class="text-center">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio"
                                               name="statuses[{{ $votehead->id }}]"
                                               value="billed" {{ $status == 'billed' ? 'checked' : '' }}>
                                        <label class="form-check-label">Bill</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio"
                                               name="statuses[{{ $votehead->id }}]"
                                               value="exempt" {{ $status == 'exempt' ? 'checked' : '' }}>
                                        <label class="form-check-label">Exempt</label>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <button class="btn btn-success" type="submit">Save</button>
            </form>
        </div>
    </div>
@endif

{{-- Student Search Modal (same UX as attendance) --}}
<div class="modal fade" id="studentSearchModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">Search Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="text" id="studentSearchInput" class="form-control mb-3" placeholder="Type name or admission number...">
        <ul id="studentSearchResults" class="list-group"></ul>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-submit when term/year change and student already picked
    const form = document.getElementById('studentForm');
    const sid  = document.getElementById('selectedStudentId');
    const term = document.getElementById('termSelect');
    const year = document.getElementById('yearInput');
    function ready(){ return sid.value && term.value && year.value; }
    [term, year].forEach(el => el.addEventListener('change', () => { if (ready()) form.submit(); }));

    // Modal search (identical pattern as attendance)
    const input = document.getElementById('studentSearchInput');
    const list  = document.getElementById('studentSearchResults');
    if (!input) return;

    let timer = null;
    input.addEventListener('keyup', function () {
        clearTimeout(timer);
        const q = this.value.trim();
        if (q.length < 2) { list.innerHTML = ''; return; }

        list.innerHTML = '<li class="list-group-item">Searching…</li>';
        timer = setTimeout(() => {
            fetch("{{ route('students.search') }}?q=" + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
              .then(r => r.json())
              .then(rows => {
                  list.innerHTML = rows.length
                    ? rows.map(s => `
                        <li class="list-group-item list-group-item-action pick"
                            data-id="${s.id}"
                            data-name="${s.full_name}"
                            data-adm="${s.admission_number}">
                            ${s.full_name} (${s.admission_number})
                        </li>`).join('')
                    : `<li class="list-group-item text-muted">No results</li>`;

                  document.querySelectorAll('#studentSearchResults .pick').forEach(el => {
                      el.addEventListener('click', () => {
                          document.getElementById('selectedStudentId').value   = el.dataset.id;
                          document.getElementById('selectedStudentName').value = `${el.dataset.name} (${el.dataset.adm})`;
                          bootstrap.Modal.getInstance(document.getElementById('studentSearchModal')).hide();

                          if (ready()) form.submit();
                      });
                  });
              })
              .catch(() => list.innerHTML = '<li class="list-group-item text-danger">Search failed</li>');
        }, 350); // debounce
    });
});
</script>
@endpush
