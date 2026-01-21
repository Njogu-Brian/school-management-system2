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
        <h1 class="mb-1">Family #{{ $family->id }}</h1>
        <p class="text-muted mb-0">Manage siblings and optional family contact details.</p>
      </div>
      <a href="{{ route('families.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    @include('students.partials.alerts')

    <div class="row g-3">
      <div class="col-lg-5">
        <div class="settings-card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-bold">Family Profile Update Link</span>
            @if($family->updateLink)
              <span class="badge bg-light text-dark">Token: {{ $family->updateLink->token }}</span>
            @endif
          </div>
          <div class="card-body">
            @if($family->updateLink)
              <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-sm btn-ghost-strong" onclick="navigator.clipboard.writeText('{{ route('family-update.form', $family->updateLink->token) }}'); this.innerText='Copied'; setTimeout(()=>this.innerText='Copy Link',1500);">
                  <i class="bi bi-clipboard"></i> Copy Link
                </button>
                <a class="btn btn-sm btn-ghost-strong" target="_blank" href="{{ route('family-update.form', $family->updateLink->token) }}">
                  <i class="bi bi-box-arrow-up-right"></i> Open Public Form
                </a>
                <form action="{{ route('families.update-link.reset', $family) }}" method="POST" onsubmit="return confirm('Reset link? Parents will need the new link to update again.');">
                  @csrf
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-counterclockwise"></i> Reset Link</button>
                </form>
              </div>
              <div class="small text-muted">
                Share this link with the family to let them update student details. Link stays active until reset.
              </div>
            @else
              <form action="{{ route('families.update-link.show', $family) }}" method="GET">
                <button class="btn btn-sm btn-settings-primary"><i class="bi bi-link-45deg"></i> Generate Link</button>
              </form>
              <div class="small text-muted mt-2">No link exists yet. Generate to share with parents.</div>
            @endif
          </div>
        </div>

        @if($parentInfo)
        <div class="settings-card mb-3">
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
                <i class="bi bi-info-circle"></i> This information comes from the student's parent record. Edit parent details from the student's profile if needed.
              </small>
            </div>
          </div>
        </div>
        @endif

        <div class="settings-card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Family Contact Details <span class="pill-badge pill-secondary">Optional</span></span>
            <form action="{{ route('families.destroy', $family) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this family? All students will be unlinked. This action cannot be undone.')">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-sm btn-ghost-strong text-danger">
                <i class="bi bi-trash"></i> Delete Family
              </button>
            </form>
          </div>
          <form action="{{ route('families.update', $family) }}" method="POST">
            @csrf @method('PUT')
          <div class="card-body vstack gap-3">
            <div class="alert alert-soft border-0 small mb-0">
              <i class="bi bi-info-circle"></i> Optional fields used for family-level billing/communication. If empty, parent details from students are used.
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
            <button class="btn btn-settings-primary"><i class="bi bi-save"></i> Save</button>
          </div>
          </form>
        </div>

        <div class="settings-card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Link Students as Siblings</span>
            <button type="button" class="btn btn-sm btn-ghost-strong" data-bs-toggle="modal" data-bs-target="#studentSearchModal">
              <i class="bi bi-plus-circle"></i> Add Student
            </button>
          </div>
          <div class="card-body">
            <p class="text-muted small mb-0">
              <i class="bi bi-info-circle"></i> Search students to link as siblings. You can add multiple students one at a time.
            </p>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="settings-card h-100">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Family Members / Siblings ({{ $family->students->count() }})</span>
            @if($family->students->count() > 0)
              <span class="pill-badge pill-primary">{{ $family->students->count() }} {{ Str::plural('sibling', $family->students->count()) }}</span>
            @endif
          </div>
          <div class="table-responsive">
            <table class="table table-modern align-middle mb-0">
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
                      <a href="{{ route('students.show', $st->id) }}" class="fw-semibold text-reset">
                        {{ $st->full_name }}
                      </a>
                      <div class="text-muted small">DOB: {{ $st->dob ?: '—' }}</div>
                    </td>
                    <td>{{ $st->classroom->name ?? '—' }}</td>
                    <td>{{ $st->stream->name ?? '—' }}</td>
                    <td class="text-end">
                      <form action="{{ route('families.detach', $family) }}" method="POST" onsubmit="return confirm('Remove this student from the family?')">
                        @csrf
                        <input type="hidden" name="student_id" value="{{ $st->id }}">
                        <button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-x-circle"></i> Remove</button>
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
</div>

<div class="modal fade" id="studentSearchModal" tabindex="-1" aria-labelledby="studentSearchModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content settings-card mb-0">
      <div class="modal-header">
        <h5 class="modal-title">Search Student to Link as Sibling</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Search by name or admission number</label>
          <input type="text" id="studentSearchInput" class="form-control" placeholder="Type name or admission number...">
        </div>

        <table class="table table-modern table-hover table-sm" id="studentSearchResults">
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
                    const buttonClass = isAlreadyLinked ? 'btn-secondary' : 'btn-settings-primary';
                    const buttonText = isAlreadyLinked ? 'Already Linked' : 'Link Student';
                    const buttonDisabled = isAlreadyLinked ? 'disabled' : '';
                    return `
                        <tr>
                            <td class="fw-semibold">${stu.admission_number || '—'}</td>
                            <td>${stu.full_name || '—'}</td>
                            <td>${stu.classroom_name || '—'}</td>
                            <td>
                                <button type="button" class="btn btn-sm ${buttonClass} linkStudentBtn" 
                                    data-id="${stu.id}" data-name="${stu.full_name} (${stu.admission_number})" ${buttonDisabled}>
                                    ${buttonText}
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
                document.querySelectorAll('.linkStudentBtn:not(:disabled)').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const studentId = this.dataset.id;
                        const studentName = this.dataset.name;
                        if (confirm(`Link ${studentName} to this family as a sibling?`)) {
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
                results.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error searching students. Please try again.</td></tr>';
            }
        }, 300);
    });

    const modal = document.getElementById('studentSearchModal');
    modal?.addEventListener('hidden.bs.modal', function() {
        input.value = '';
        results.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Start typing to search for students...</td></tr>';
    });
});
</script>
@endpush
@endsection
