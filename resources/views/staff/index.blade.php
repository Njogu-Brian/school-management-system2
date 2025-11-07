@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h1 class="h3 mb-1">Staff</h1>
      <div class="text-muted">Manage employees, roles and HR details.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('staff.upload.form') }}" class="btn btn-outline-primary">
        ⬆ Bulk Upload
      </a>
      <a href="{{ route('staff.create') }}" class="btn btn-success">
        ➕ Add Staff
      </a>
    </div>
  </div>

  {{-- alerts --}}
  @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
  @if(session('error'))   <div class="alert alert-danger">{{ session('error') }}</div> @endif

  {{-- search & filters --}}
  <form method="GET" class="card shadow-sm mb-3">
    <div class="card-body row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label">Search</label>
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, email, phone, staff ID">
      </div>
      <div class="col-md-3">
        <label class="form-label">Department</label>
        <select name="department_id" class="form-select">
          <option value="">All</option>
          @foreach(\App\Models\Department::orderBy('name')->get() as $d)
            <option value="{{ $d->id }}" @selected(request('department_id')==$d->id)>{{ $d->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
          <option value="">All</option>
          @foreach(['active'=>'Active','inactive'=>'Inactive','archived'=>'Archived'] as $k=>$v)
            <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
          @endforeach
        </select>
      </div>
      <div class="col-md-2 text-end">
        <button class="btn btn-primary w-100">Filter</button>
      </div>
    </div>
  </form>

  {{-- table --}}
  <div class="card shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Staff</th>
            <th>Contacts</th>
            <th>HR</th>
            <th>Status</th>
            <th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($staff as $s)
            <tr>
              <td>
                <div class="d-flex align-items-center">
                  <img  src="{{ $s->photo_url }}"  class="rounded-circle me-3"  width="44" height="44" alt="avatar">
                    <div class="small text-muted">ID: {{ $s->staff_id }}</div>
                  </div>
                </div>
              </td>
              <td>
                <div class="small">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-envelope"></i> {{ $s->work_email }}
                  </div>
                  <div class="d-flex align-items-center gap-2 text-muted">
                    <i class="bi bi-telephone"></i> {{ $s->phone_number }}
                  </div>
                </div>
              </td>
              <td class="small">
                <div><span class="text-muted">Dept:</span> {{ $s->department->name ?? '—' }}</div>
                <div><span class="text-muted">Title:</span> {{ $s->jobTitle->name ?? '—' }}</div>
                <div><span class="text-muted">Category:</span> {{ $s->category->name ?? '—' }}</div>
              </td>
              <td>
                @php $badge = $s->status === 'active' ? 'success' : ($s->status === 'archived' ? 'secondary' : 'warning'); @endphp
                <span class="badge bg-{{ $badge }}">{{ ucfirst($s->status) }}</span>
              </td>
              <td class="text-end">
                <div class="btn-group">
                  <a href="{{ route('staff.edit', $s->id) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                  <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                  <ul class="dropdown-menu dropdown-menu-end">
                    @if($s->status === 'active')
                      <li>
                        <form action="{{ route('staff.archive', $s->id) }}" method="POST" onsubmit="return confirm('Archive this staff?')">
                          @csrf @method('PATCH')
                          <button class="dropdown-item text-danger">Archive</button>
                        </form>
                      </li>
                    @else
                      <li>
                        <form action="{{ route('staff.restore', $s->id) }}" method="POST" onsubmit="return confirm('Restore this staff?')">
                          @csrf @method('PATCH')
                          <button class="dropdown-item text-success">Restore</button>
                        </form>
                      </li>
                    @endif
                  </ul>
                </div>
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">No staff found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if(method_exists($staff,'links'))
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing {{ $staff->firstItem() ?? 0 }}–{{ $staff->lastItem() ?? 0 }} of {{ $staff->total() ?? $staff->count() }}
        </div>
        {{ $staff->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
