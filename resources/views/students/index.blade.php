@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page ds-pilot">
  <div class="settings-shell">
    <x-nav.breadcrumb :items="[
      'Home' => Route::has('dashboard') ? route('dashboard') : (Route::has('home') ? route('home') : url('/')),
      'Students' => null,
    ]" class="mb-3" />

    <x-page-header eyebrow="Students" title="Students" description="Browse, filter, and manage student records." class="mb-3">
      <x-slot:actions>
        @if(Route::has('students.archived') && can_edit_student_records())
          <a href="{{ route('students.archived') }}" class="btn btn-ghost-strong">
            <i class="bi bi-archive-fill"></i> Archived
          </a>
        @endif
        @if(Route::has('students.alumni') && can_edit_student_records())
          <a href="{{ route('students.alumni') }}" class="btn btn-ghost-strong">
            <i class="bi bi-mortarboard"></i> Alumni
          </a>
        @endif
        @if(Route::has('students.export'))
          <button type="button" class="btn btn-ghost-strong" data-bs-toggle="modal" data-bs-target="#studentsExportModal">
            <i class="bi bi-download"></i> Export
          </button>
        @endif
        @if(Route::has('students.enrollment-report'))
          <a href="{{ route('students.enrollment-report') }}" class="btn btn-ghost-strong">
            <i class="bi bi-bar-chart-steps"></i> Enrollment by Class
          </a>
        @endif
        @if(Route::has('students.duplicate-report') && can_edit_student_records())
          <a href="{{ route('students.duplicate-report') }}" class="btn btn-ghost-strong">
            <i class="bi bi-person-exclamation"></i> Duplicate admissions
          </a>
        @endif
        @if(Route::has('students.bulk.assign-streams'))
          <a href="{{ route('students.bulk.assign-streams') }}" class="btn btn-ghost-strong">
            <i class="bi bi-diagram-3"></i> Bulk Assign Streams
          </a>
        @endif
        @if(Route::has('students.bulk'))
        @if(can_edit_student_records())
        <a href="{{ route('students.bulk') }}" class="btn btn-ghost-strong"><i class="bi bi-upload"></i> Bulk Upload</a>
        @endif
        @endif
        @if(can_edit_student_records())
        <a href="{{ route('students.create') }}" class="btn btn-settings-primary"><i class="bi bi-person-plus" aria-hidden="true"></i> New Student</a>
        @endif
      </x-slot:actions>
    </x-page-header>

    <x-feedback.flash />

    <x-card title="Filters" subtitle="Search, class, stream, and pagination." class="mb-3">
      <x-slot:header><x-badge>Live query</x-badge></x-slot:header>
        <form class="row g-2" method="GET" action="{{ route('students.index') }}">
          <div class="col-md-3">
            <label class="form-label">Admission #</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-card-text"></i></span>
              <input type="text" name="admission_number" value="{{ request('admission_number') }}" class="form-control" placeholder="Admission #">
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label">Name</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Name">
            </div>
          </div>
          <div class="col-md-2">
            <label class="form-label">Class</label>
            <select name="classroom_id" class="form-select">
              <option value="">All Classes</option>
              @foreach ($classrooms as $c)
                <option value="{{ $c->id }}" @selected(request('classroom_id')==$c->id)>{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Stream</label>
            <select name="stream_id" class="form-select">
              <option value="">All Streams</option>
              @foreach ($streams as $s)
                <option value="{{ $s->id }}" @selected(request('stream_id')==$s->id)>{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Per Page</label>
            <select name="per_page" class="form-select">
              @foreach ([10,20,50,100] as $pp)
                <option value="{{ $pp }}" @selected(request('per_page',20)==$pp)>{{ $pp }}/page</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 d-flex gap-2 flex-wrap mt-2">
            <button class="btn btn-settings-primary"><i class="bi bi-funnel"></i> Apply</button>
            <a href="{{ route('students.index') }}" class="btn btn-ghost-strong">Reset</a>
            @if(request('showArchived'))
              <a href="{{ route('students.index', array_merge(request()->except('showArchived'), [])) }}" class="btn btn-ghost-strong">Show Active</a>
            @else
              <a href="{{ route('students.index', array_merge(request()->all(), ['showArchived'=>1])) }}" class="btn btn-ghost-strong">Show Archived</a>
            @endif
          </div>
        </form>
    </x-card>

    <form action="#" method="POST" id="bulkForm">
      @csrf
      <x-card class="mb-0">
        <x-slot:header>
          <div class="d-flex gap-2">
            <div class="btn-group">
              <button type="button" class="btn btn-ghost-strong dropdown-toggle" data-bs-toggle="dropdown">
                <i class="bi bi-check-square"></i> Bulk Actions
              </button>
              <ul class="dropdown-menu">
                @if(Route::has('students.bulk.assign'))
                <li><a href="#" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#assignModal"><i class="bi bi-diagram-3"></i> Assign Class/Stream</a></li>
                <li><hr class="dropdown-divider"></li>
                @endif
                @if(Route::has('students.bulk.archive'))
                <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#confirm-bulk-archive"><i class="bi bi-archive"></i> Archive</button></li>
                @endif
                @if(Route::has('students.bulk.restore'))
                <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#confirm-bulk-restore"><i class="bi bi-arrow-counterclockwise"></i> Restore</button></li>
                @endif
              </ul>
            </div>
          </div>
          @if($students->total())
          <span class="input-chip">{{ $students->total() }} total</span>
          @endif
        </x-slot:header>

        <div class="table-responsive">
          <table class="table table-modern align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width:28px;"><input type="checkbox" id="chk_all"></th>
                <th>Admission</th>
                <th>Student</th>
                <th>Class</th>
                <th>Stream</th>
                <th>Category</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse ($students as $student)
                @php $birthdayThisWeek = in_array($student->id, $thisWeekBirthdays ?? []); @endphp
                <tr @class(['table-secondary'=> $student->archive])>
                  <td><input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="chk"></td>
                  <td class="fw-semibold">{{ $student->admission_number }}</td>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <img
                        src="{{ $student->photo_url }}"
                        alt="{{ $student->full_name }}"
                        class="avatar-36"
                        onerror="this.onerror=null;this.src='{{ asset('images/avatar-student.png') }}'">
                      <div>
                        @if(Route::has('students.show'))
                          <a href="{{ route('students.show', $student->id) }}" class="fw-semibold text-reset">
                            {{ $student->full_name }}
                          </a>
                        @else
                          <span class="fw-semibold">{{ $student->full_name }}</span>
                        @endif
                        <div class="text-muted small">
                          DOB: {{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d M Y') : '-' }}
                          @if($birthdayThisWeek)
                            <span class="pill-badge pill-warning ms-1"><i class="bi bi-cake2"></i> Birthday week</span>
                          @endif
                        </div>
                      </div>
                    </div>
                  </td>
                  <td>{{ $student->classroom->name ?? '—' }}</td>
                  <td>{{ $student->stream->name ?? '—' }}</td>
                  <td>{{ $student->category->name ?? '—' }}</td>
                  <td>
                    @if($student->archive)
                      <span class="pill-badge pill-secondary"><i class="bi bi-archive me-1"></i> Archived</span>
                    @else
                      <span class="pill-badge pill-success"><i class="bi bi-check2-circle me-1"></i> Active</span>
                    @endif
                  </td>
                  <td class="text-end">@include('students.partials.action-dropdown')</td>
                </tr>
              @empty
                @php
                    $hasAnyFilter = request()->filled('name') || request()->filled('admission_number')
                        || request()->filled('classroom_id') || request()->filled('stream_id')
                        || request()->boolean('show_all') || request()->has('showArchived');
                @endphp
                @if(! $hasAnyFilter)
                  <tr>
                    <td colspan="8" class="text-center py-5">
                      <div class="d-flex flex-column align-items-center gap-2">
                        <i class="bi bi-funnel" style="font-size:2rem; color:#6c757d;"></i>
                        <h6 class="mb-0">Pick a filter to load students</h6>
                        <p class="text-muted small mb-2">To keep this page fast, no students are listed until you search, pick a class/stream, or choose "Show all".</p>
                        <a href="{{ request()->fullUrlWithQuery(['show_all' => 1]) }}" class="btn btn-outline-primary btn-sm">
                          <i class="bi bi-people"></i> Show all students
                        </a>
                      </div>
                    </td>
                  </tr>
                @else
                  <tr><td colspan="8">@include('students.partials.empty-state')</td></tr>
                @endif
              @endforelse
            </tbody>
          </table>
        </div>

        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
          <div class="text-muted small">
            Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}
          </div>
          {{ $students->withQueryString()->links() }}
        </div>
      </x-card>
    </form>
    <x-feedback.confirmation-modal
      id="confirm-bulk-archive"
      title="Archive selected students?"
      message="Selected students will be archived and removed from the active list."
      confirm-label="Archive students"
      form="bulkForm"
      formaction="{{ route('students.bulk.archive') }}" />
    <x-feedback.confirmation-modal
      id="confirm-bulk-restore"
      title="Restore selected students?"
      message="Selected students will be restored to the active list."
      confirm-label="Restore students"
      variant="primary"
      form="bulkForm"
      formaction="{{ route('students.bulk.restore') }}" />
  </div>
</div>

@if(Route::has('students.bulk.assign'))
<div class="modal fade" id="assignModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content settings-card mb-0" method="POST" action="{{ route('students.bulk.assign') }}">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Assign Class / Stream</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body vstack gap-3">
        <input type="hidden" name="student_ids" id="assign_student_ids">
        <div>
          <label class="form-label">Classroom</label>
          <select name="classroom_id" id="assign_classroom" class="form-select">
            <option value="">— Keep current —</option>
            @foreach ($classrooms as $c)
              <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="form-label">Stream</label>
          <select name="stream_id" id="assign_stream" class="form-select">
            <option value="">— Keep current —</option>
            @foreach ($streams as $s)
              <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-settings-primary"><i class="bi bi-save"></i> Apply</button>
      </div>
    </form>
  </div>
</div>
@endif

{{-- Hidden archive/restore forms (to avoid nested forms) --}}
@stack('archive-forms')

{{-- Restore modal --}}
<div class="modal fade" id="restoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="restoreConfirmForm" class="modal-content settings-card mb-0" method="POST">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Restore Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body vstack gap-3">
        <div>
          <label class="form-label">Student</label>
          <div class="fw-semibold restore-student-name">—</div>
        </div>
        <div>
          <label class="form-label">Reason for restoration <span class="text-danger">*</span></label>
          <select name="reason" class="form-select" required>
            <option value="">Select reason…</option>
            <option value="Returned to school">Returned to school</option>
            <option value="Fees settled">Fees settled</option>
            <option value="Archived in error">Archived in error</option>
            <option value="Readmission">Readmission</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div>
          <label class="form-label">Notes (optional)</label>
          <textarea name="restored_notes" class="form-control" rows="2" placeholder="Any extra detail about this restoration…"></textarea>
        </div>
        <div class="text-muted small mb-0">
          <i class="bi bi-info-circle me-1"></i>
          The reason, date and person restoring are recorded in the student's archive history.
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-success" type="submit"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
      </div>
    </form>
  </div>
</div>

{{-- Archive modal --}}
<div class="modal fade" id="archiveModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="archiveConfirmForm" class="modal-content settings-card mb-0" method="POST">
      @csrf
      <div class="modal-header">
        <h5 class="modal-title">Archive Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body vstack gap-3">
        <div class="alert alert-warning small mb-0">
          <strong>Note:</strong> Invoices/payments remain intact for archived students.
        </div>
        <div>
          <label class="form-label">Student</label>
          <div class="fw-semibold archive-student-name">—</div>
        </div>
        <div>
          <label class="form-label">Reason</label>
          <select name="reason" class="form-select" required>
            <option value="School fees">School fees</option>
            <option value="Relocating from region">Relocating from region</option>
            <option value="Unhappy with school">Unhappy with school</option>
            <option value="Completed learning">Completed learning</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div>
          <label class="form-label">Details</label>
          <textarea name="archived_notes" class="form-control" rows="3" placeholder="Add brief details" required></textarea>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end gap-2">
        <button type="button" class="btn btn-ghost-strong" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-settings-primary" type="submit"><i class="bi bi-archive"></i> Archive</button>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
  document.getElementById('chk_all')?.addEventListener('change', e=>{
    document.querySelectorAll('.chk').forEach(c=>c.checked = e.target.checked);
  });
  const assignModalEl = document.getElementById('assignModal');
  assignModalEl?.addEventListener('show.bs.modal', ()=>{
    const ids = Array.from(document.querySelectorAll('.chk:checked')).map(chk=>chk.value);
    const hidden = document.getElementById('assign_student_ids');
    hidden.value = ids.join(',');
    const container = hidden.parentElement;
    container.querySelectorAll('input[name="student_ids[]"]').forEach(el=>el.remove());
    ids.forEach(id=>{
      const i=document.createElement('input'); i.type='hidden'; i.name='student_ids[]'; i.value=id; container.appendChild(i);
    });
  });

  // Archive modal handling
  const archiveModal = document.getElementById('archiveModal');
  const archiveForm = document.getElementById('archiveConfirmForm');
  document.querySelectorAll('.archive-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const studentId = btn.getAttribute('data-student-id');
      const name = btn.getAttribute('data-student-name');
      archiveForm.action = "{{ url('students') }}/" + studentId + "/archive";
      archiveModal.querySelector('.archive-student-name').innerText = name;
      archiveModal.querySelector('select[name=\"reason\"]').value = 'School fees';
      archiveModal.querySelector('textarea[name=\"archived_notes\"]').value = '';
      const modal = bootstrap.Modal.getOrCreateInstance(archiveModal);
      modal.show();
    });
  });
  // Restore buttons open the restore modal (reason is required)
  const restoreModal = document.getElementById('restoreModal');
  const restoreForm = document.getElementById('restoreConfirmForm');
  document.querySelectorAll('.restore-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      const studentId = btn.getAttribute('data-student-id');
      const name = btn.getAttribute('data-student-name');
      restoreForm.action = "{{ url('students') }}/" + studentId + "/restore";
      restoreModal.querySelector('.restore-student-name').innerText = name;
      restoreModal.querySelector('select[name="reason"]').value = '';
      restoreModal.querySelector('textarea[name="restored_notes"]').value = '';
      const modal = bootstrap.Modal.getOrCreateInstance(restoreModal);
      modal.show();
    });
  });
</script>
@endpush

@php
    $directoryExport = app(\App\Services\DirectoryExportService::class);
@endphp
@include('partials.directory_export_modal', [
    'exportType' => 'students',
    'exportRoute' => route('students.export'),
    'fieldGroups' => $directoryExport->studentFieldGroups(),
    'defaultFields' => $directoryExport->defaultStudentFields(),
    'classrooms' => $classrooms ?? \App\Models\Academics\Classroom::orderBy('name')->get(),
    'filterParams' => request()->only(['name', 'admission_number', 'classroom_id', 'stream_id', 'showArchived', 'show_all']),
])

@endsection
