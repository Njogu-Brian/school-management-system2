@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    @include('students.partials.breadcrumbs', ['trail' => ['List' => null]])

    <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Students</h1>
        <p class="text-muted mb-0">Browse, filter, and manage student records.</p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        @if(Route::has('students.archived'))
          <a href="{{ route('students.archived') }}" class="btn btn-ghost-strong">
            <i class="bi bi-archive"></i> Archived
          </a>
        @endif
        @if(Route::has('students.export'))
          <a href="{{ route('students.export', request()->query()) }}" class="btn btn-ghost-strong">
            <i class="bi bi-download"></i> Export CSV
          </a>
        @endif
        @if(Route::has('students.bulk.assign-streams'))
          <a href="{{ route('students.bulk.assign-streams') }}" class="btn btn-ghost-strong">
            <i class="bi bi-diagram-3"></i> Bulk Assign Streams
          </a>
        @endif
        <a href="{{ route('students.bulk') }}" class="btn btn-ghost-strong"><i class="bi bi-upload"></i> Bulk Upload</a>
        <a href="{{ route('students.create') }}" class="btn btn-settings-primary"><i class="bi bi-person-plus"></i> New Student</a>
      </div>
    </div>

    @include('students.partials.alerts')

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Filters</h5>
          <p class="text-muted small mb-0">Search, class, stream, and pagination.</p>
        </div>
        <span class="pill-badge pill-secondary">Live query</span>
      </div>
      <div class="card-body">
        <form class="row g-2">
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
      </div>
    </div>

    <form action="#" method="POST" id="bulkForm">
      @csrf
      <div class="settings-card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
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
                <li><button formaction="{{ route('students.bulk.archive') }}" class="dropdown-item" onclick="return confirm('Archive selected students?')"><i class="bi bi-archive"></i> Archive</button></li>
                @endif
                @if(Route::has('students.bulk.restore'))
                <li><button formaction="{{ route('students.bulk.restore') }}" class="dropdown-item" onclick="return confirm('Restore selected students?')"><i class="bi bi-arrow-counterclockwise"></i> Restore</button></li>
                @endif
              </ul>
            </div>
          </div>
          @if($students->total())
          <span class="input-chip">{{ $students->total() }} total</span>
          @endif
        </div>

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
                        alt="{{ $student->first_name }} {{ $student->last_name }}"
                        class="avatar-36"
                        onerror="this.onerror=null;this.src='{{ asset('images/avatar-student.png') }}'">
                      <div>
                        @if(Route::has('students.show'))
                          <a href="{{ route('students.show', $student->id) }}" class="fw-semibold text-reset">
                            {{ $student->first_name }} {{ $student->last_name }}
                          </a>
                        @else
                          <span class="fw-semibold">{{ $student->first_name }} {{ $student->last_name }}</span>
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
                <tr><td colspan="8">@include('students.partials.empty-state')</td></tr>
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
      </div>
    </form>
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
</script>
@endpush
@endsection
