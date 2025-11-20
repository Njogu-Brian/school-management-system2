@extends('layouts.app')

@section('content')
<div class="container">
  @include('students.partials.breadcrumbs', ['trail' => ['List' => null]])

  <div class="d-flex align-items-center justify-content-between mb-2">
    <h1 class="h4 mb-0">Students</h1>
    <div class="d-flex gap-2">
      @if(Route::has('students.export'))
        <a href="{{ route('students.export', request()->query()) }}" class="btn btn-outline-secondary">
          <i class="bi bi-download"></i> Export CSV
        </a>
      @endif
      @if(Route::has('students.bulk.assign-streams'))
        <a href="{{ route('students.bulk.assign-streams') }}" class="btn btn-outline-primary">
          <i class="bi bi-diagram-3"></i> Bulk Assign Streams
        </a>
      @endif
      <a href="{{ route('students.bulk') }}" class="btn btn-outline-info"><i class="bi bi-upload"></i> Bulk Upload</a>
      <a href="{{ route('students.create') }}" class="btn btn-success"><i class="bi bi-person-plus"></i> New Student</a>
    </div>
  </div>

  @include('students.partials.alerts')

  {{-- Filters --}}
  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-2">
        <div class="col-md-3">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-card-text"></i></span>
            <input type="text" name="admission_number" value="{{ request('admission_number') }}" class="form-control" placeholder="Admission #">
          </div>
        </div>
        <div class="col-md-3">
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="name" value="{{ request('name') }}" class="form-control" placeholder="Name">
          </div>
        </div>
        <div class="col-md-2">
          <select name="classroom_id" class="form-select">
            <option value="">All Classes</option>
            @foreach ($classrooms as $c)
              <option value="{{ $c->id }}" @selected(request('classroom_id')==$c->id)>{{ $c->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select name="stream_id" class="form-select">
            <option value="">All Streams</option>
            @foreach ($streams as $s)
              <option value="{{ $s->id }}" @selected(request('stream_id')==$s->id)>{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2">
          <select name="per_page" class="form-select">
            @foreach ([10,20,50,100] as $pp)
              <option value="{{ $pp }}" @selected(request('per_page',20)==$pp)>{{ $pp }}/page</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 d-flex gap-2">
          <button class="btn btn-primary"><i class="bi bi-funnel"></i> Apply</button>
          <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Reset</a>
          @if(request('showArchived'))
            <a href="{{ route('students.index', array_merge(request()->except('showArchived'), [])) }}" class="btn btn-secondary">Show Active</a>
          @else
            <a href="{{ route('students.index', array_merge(request()->all(), ['showArchived'=>1])) }}" class="btn btn-secondary">Show Archived</a>
          @endif
        </div>
      </form>
    </div>
  </div>

  {{-- Bulk actions --}}
  <form action="#" method="POST" id="bulkForm">
    @csrf
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
          <div class="btn-group">
            <button type="button" class="btn btn-outline-dark dropdown-toggle" data-bs-toggle="dropdown">
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
      </div>

      <div class="table-responsive">
        <table class="table align-middle mb-0">
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
                        <a href="{{ route('students.show', $student->id) }}" class="fw-semibold">
                          {{ $student->first_name }} {{ $student->last_name }}
                        </a>
                      @else
                        <span class="fw-semibold">{{ $student->first_name }} {{ $student->last_name }}</span>
                      @endif
                      <div class="text-muted small">
                        DOB: {{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d M Y') : '-' }}
                        @if($birthdayThisWeek)
                          <span class="badge bg-warning text-dark ms-1"><i class="bi bi-cake2"></i> Birthday week</span>
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
                    <span class="badge bg-secondary"><i class="bi bi-archive me-1"></i> Archived</span>
                  @else
                    <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i> Active</span>
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

      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="text-muted small">
          Showing {{ $students->firstItem() }}–{{ $students->lastItem() }} of {{ $students->total() }}
        </div>
        {{ $students->withQueryString()->links() }}
      </div>
    </div>
  </form>
</div>

{{-- Assign modal --}}
@if(Route::has('students.bulk.assign'))
<div class="modal fade" id="assignModal" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="{{ route('students.bulk.assign') }}">
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
      <div class="modal-footer">
        <button class="btn btn-primary"><i class="bi bi-save"></i> Apply</button>
      </div>
    </form>
  </div>
</div>
@endif

@endsection

@push('scripts')
<script>
  // bulk select
  document.getElementById('chk_all')?.addEventListener('change', e=>{
    document.querySelectorAll('.chk').forEach(c=>c.checked = e.target.checked);
  });
  // prep ids for modal
  const assignModal = document.getElementById('assignModal');
  assignModal?.addEventListener('show.bs.modal', ()=>{
    const ids = Array.from(document.querySelectorAll('.chk:checked')).map(chk=>chk.value);
    document.getElementById('assign_student_ids').name = 'student_ids[]';
    document.getElementById('assign_student_ids').value = ids.join(',');
    // Convert to multiple hidden inputs (Laravel array)
    const container = document.getElementById('assign_student_ids').parentElement;
    // Clear existing clones
    container.querySelectorAll('input[name="student_ids[]"]').forEach(el=>el.remove());
    ids.forEach(id=>{
      const i=document.createElement('input'); i.type='hidden'; i.name='student_ids[]'; i.value=id; container.appendChild(i);
    });
  });
</script>
@endpush
