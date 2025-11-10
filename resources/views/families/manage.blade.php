@extends('layouts.app')

@section('content')
<div class="container">
  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ Route::has('dashboard') ? route('dashboard') : url('/') }}"><i class="bi bi-house"></i></a></li>
      <li class="breadcrumb-item"><a href="{{ route('families.index') }}">Families</a></li>
      <li class="breadcrumb-item active">Family #{{ $family->id }}</li>
    </ol>
  </nav>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="h4 mb-0">Family #{{ $family->id }}</h1>
    <a href="{{ route('families.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  @include('students.partials.alerts')

  <div class="row">
    <div class="col-lg-5">
      {{-- Parent Info from Students --}}
      @if($parentInfo)
      <div class="card mb-3">
        <div class="card-header">Parent/Guardian Information</div>
        <div class="card-body">
          <div class="vstack gap-2">
            @if($parentInfo->father_name)
            <div>
              <strong>Father:</strong> {{ $parentInfo->father_name }}
              @if($parentInfo->father_phone) <br><small class="text-muted"><i class="bi bi-telephone"></i> {{ $parentInfo->father_phone }}</small> @endif
              @if($parentInfo->father_email) <br><small class="text-muted"><i class="bi bi-envelope"></i> {{ $parentInfo->father_email }}</small> @endif
            </div>
            @endif
            @if($parentInfo->mother_name)
            <div>
              <strong>Mother:</strong> {{ $parentInfo->mother_name }}
              @if($parentInfo->mother_phone) <br><small class="text-muted"><i class="bi bi-telephone"></i> {{ $parentInfo->mother_phone }}</small> @endif
              @if($parentInfo->mother_email) <br><small class="text-muted"><i class="bi bi-envelope"></i> {{ $parentInfo->mother_email }}</small> @endif
            </div>
            @endif
            @if($parentInfo->guardian_name)
            <div>
              <strong>Guardian:</strong> {{ $parentInfo->guardian_name }}
              @if($parentInfo->guardian_phone) <br><small class="text-muted"><i class="bi bi-telephone"></i> {{ $parentInfo->guardian_phone }}</small> @endif
              @if($parentInfo->guardian_email) <br><small class="text-muted"><i class="bi bi-envelope"></i> {{ $parentInfo->guardian_email }}</small> @endif
            </div>
            @endif
          </div>
          <div class="mt-3">
            <small class="text-muted">
              <i class="bi bi-info-circle"></i> This information comes from the student's parent record. 
              Edit parent details from the student's profile if needed.
            </small>
          </div>
        </div>
      </div>
      @endif

      {{-- Edit family contact (optional - for fee billing) --}}
      <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Family Contact Details <span class="badge bg-secondary">Optional</span></span>
          <form action="{{ route('families.destroy', $family) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this family? All students will be unlinked. This action cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-sm btn-outline-danger">
              <i class="bi bi-trash"></i> Delete Family
            </button>
          </form>
        </div>
        <form action="{{ route('families.update', $family) }}" method="POST">
          @csrf @method('PUT')
        <div class="card-body vstack gap-3">
          <div class="alert alert-info small mb-0">
            <i class="bi bi-info-circle"></i> These fields are optional and used for family-level billing and communication. 
            If left empty, parent details from students will be used.
          </div>
          
          <h6 class="text-muted mb-2">Father Information</h6>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Father Name</label>
              <input type="text" class="form-control" name="father_name" value="{{ old('father_name', $family->father_name) }}" placeholder="Leave empty to use parent info">
            </div>
            <div class="col-md-6">
              <label class="form-label">Father Phone</label>
              <input type="text" class="form-control" name="father_phone" value="{{ old('father_phone', $family->father_phone) }}" placeholder="Leave empty to use parent info">
            </div>
            <div class="col-md-12">
              <label class="form-label">Father Email</label>
              <input type="email" class="form-control" name="father_email" value="{{ old('father_email', $family->father_email) }}" placeholder="Leave empty to use parent info">
            </div>
          </div>

          <hr>

          <h6 class="text-muted mb-2">Mother Information</h6>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Mother Name</label>
              <input type="text" class="form-control" name="mother_name" value="{{ old('mother_name', $family->mother_name) }}" placeholder="Leave empty to use parent info">
            </div>
            <div class="col-md-6">
              <label class="form-label">Mother Phone</label>
              <input type="text" class="form-control" name="mother_phone" value="{{ old('mother_phone', $family->mother_phone) }}" placeholder="Leave empty to use parent info">
            </div>
            <div class="col-md-12">
              <label class="form-label">Mother Email</label>
              <input type="email" class="form-control" name="mother_email" value="{{ old('mother_email', $family->mother_email) }}" placeholder="Leave empty to use parent info">
            </div>
          </div>

          <hr>

          <h6 class="text-muted mb-2">Guardian Information (Primary Contact)</h6>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label">Guardian Name</label>
              <input type="text" class="form-control" name="guardian_name" value="{{ old('guardian_name', $family->guardian_name) }}" placeholder="Leave empty to use parent info">
            </div>
            <div class="col-md-6">
              <label class="form-label">Phone</label>
              <input type="text" class="form-control" name="phone" value="{{ old('phone', $family->phone) }}" placeholder="Leave empty to use parent info">
            </div>
            <div class="col-md-12">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" name="email" value="{{ old('email', $family->email) }}" placeholder="Leave empty to use parent info">
            </div>
          </div>
        </div>
        <div class="card-footer d-flex justify-content-end">
          <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
        </div>
        </form>
      </div>

      {{-- Attach student --}}
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Link Students as Siblings</span>
          <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#studentSearchModal">
            <i class="bi bi-plus-circle"></i> Add Student
          </button>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-0">
            <i class="bi bi-info-circle"></i> Search for students by name or admission number to link them as siblings. 
            You can add multiple students one at a time.
          </p>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      {{-- Members list --}}
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <span>Family Members / Siblings ({{ $family->students->count() }})</span>
          @if($family->students->count() > 0)
            <span class="badge bg-primary">{{ $family->students->count() }} {{ Str::plural('sibling', $family->students->count()) }}</span>
          @endif
        </div>
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Admission</th>
                <th>Student</th>
                <th>Class</th>
                <th>Stream</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($family->students as $st)
                <tr>
                  <td class="fw-semibold">{{ $st->admission_number }}</td>
                  <td>
                    <a href="{{ route('students.show', $st->id) }}" class="fw-semibold">
                      {{ $st->first_name }} {{ $st->last_name }}
                    </a>
                    <div class="text-muted small">DOB: {{ $st->dob ?: '—' }}</div>
                  </td>
                  <td>{{ $st->classroom->name ?? '—' }}</td>
                  <td>{{ $st->stream->name ?? '—' }}</td>
                  <td class="text-end">
                    <form action="{{ route('families.detach', $family) }}" method="POST" onsubmit="return confirm('Remove this student from the family?')">
                      @csrf
                      <input type="hidden" name="student_id" value="{{ $st->id }}">
                      <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Remove</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No members yet.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
@endsection

{{-- Student Search Modal for Family Linking --}}
<div class="modal fade" id="studentSearchModal" tabindex="-1" aria-labelledby="studentSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Search Student to Link as Sibling</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Search by name or admission number</label>
          <input type="text" id="studentSearchInput" class="form-control" placeholder="Type name or admission number...">
        </div>

        <table class="table table-hover table-sm" id="studentSearchResults">
          <thead>
            <tr>
              <th>Admission #</th>
              <th>Name</th>
              <th>Class</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td colspan="4" class="text-center text-muted">Start typing to search for students...</td>
            </tr>
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
    const attachUrl = `{{ route('families.attach', $family) }}`;
    const currentMemberIds = @json($family->students->pluck('id')->toArray());
    let searchTimeout = null;

    if (!input || !results) return;

    input.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length < 2) {
            results.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Start typing to search for students...</td></tr>';
            return;
        }

        searchTimeout = setTimeout(async () => {
            results.innerHTML = '<tr><td colspan="4" class="text-center">Searching...</td></tr>';
            
            try {
                const res = await fetch(`{{ route('api.students.search') }}?q=${encodeURIComponent(query)}`);
                const data = await res.json();

                if (data.length === 0) {
                    results.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No students found.</td></tr>';
                    return;
                }

                results.innerHTML = data.map(stu => {
                    const isAlreadyLinked = currentMemberIds.includes(stu.id);
                    const buttonClass = isAlreadyLinked ? 'btn-secondary' : 'btn-primary';
                    const buttonText = isAlreadyLinked ? 'Already Linked' : 'Link Student';
                    const buttonDisabled = isAlreadyLinked ? 'disabled' : '';
                    
                    return `
                        <tr>
                            <td class="fw-semibold">${stu.admission_number || '—'}</td>
                            <td>${stu.full_name || '—'}</td>
                            <td>${stu.classroom_name || '—'}</td>
                            <td>
                                <button type="button" class="btn btn-sm ${buttonClass} linkStudentBtn" 
                                    data-id="${stu.id}" 
                                    data-name="${stu.full_name} (${stu.admission_number})"
                                    ${buttonDisabled}>
                                    ${buttonText}
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');

                // Attach click handlers for link buttons
                document.querySelectorAll('.linkStudentBtn:not(:disabled)').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const studentId = this.dataset.id;
                        const studentName = this.dataset.name;
                        
                        if (confirm(`Link ${studentName} to this family as a sibling?`)) {
                            // Create and submit form to attach student
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = attachUrl;
                            form.innerHTML = `
                                @csrf
                                <input type="hidden" name="student_id" value="${studentId}">
                            `;
                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                });
            } catch (error) {
                console.error('Search error:', error);
                results.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error searching students. Please try again.</td></tr>';
            }
        }, 300);
    });

    // Clear search when modal is closed
    const modal = document.getElementById('studentSearchModal');
    if (modal) {
        modal.addEventListener('hidden.bs.modal', function() {
            input.value = '';
            results.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Start typing to search for students...</td></tr>';
        });
    }
});
</script>
@endpush
