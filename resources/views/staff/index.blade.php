@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Staff Management</h2>
      <small class="text-muted">Manage employees, roles and HR details</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('staff.upload.form') }}" class="btn btn-outline-primary">
        <i class="bi bi-upload"></i> Bulk Upload
      </a>
      <a href="{{ route('staff.template') }}" class="btn btn-outline-secondary">
        <i class="bi bi-download"></i> Template
      </a>
      <a href="{{ route('staff.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add Staff
      </a>
    </div>
  </div>

  {{-- alerts --}}
  @if(session('success')) 
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  @if(session('error'))   
    <div class="alert alert-danger alert-dismissible fade show">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('errors') && is_array(session('errors')))
    <div class="alert alert-warning alert-dismissible fade show">
      <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Import Errors</h5>
      <p class="mb-2">The following rows failed to import:</p>
      <ul class="mb-0">
        @foreach(session('errors') as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('error_details') && is_array(session('error_details')))
    <div class="card border-warning mb-4">
      <div class="card-header bg-warning text-dark">
        <h5 class="mb-0"><i class="bi bi-file-earmark-excel"></i> Detailed Import Error Report</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-bordered">
            <thead>
              <tr>
                <th>Row #</th>
                <th>Name</th>
                <th>Email</th>
                <th>Error</th>
              </tr>
            </thead>
            <tbody>
              @foreach(session('error_details') as $detail)
                <tr>
                  <td>{{ $detail['row'] }}</td>
                  <td>{{ $detail['name'] ?: 'N/A' }}</td>
                  <td>{{ $detail['email'] ?: 'N/A' }}</td>
                  <td class="text-danger"><small>{{ $detail['error'] }}</small></td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif

  {{-- Summary Statistics --}}
  <div class="row mb-4">
    <div class="col-md-3">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Total Staff</h6>
              <h3 class="mb-0">{{ $totalStaff }}</h3>
            </div>
            <i class="bi bi-people fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Active Staff</h6>
              <h3 class="mb-0">{{ $activeStaff }}</h3>
            </div>
            <i class="bi bi-check-circle fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-secondary text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Archived</h6>
              <h3 class="mb-0">{{ $archivedStaff }}</h3>
            </div>
            <i class="bi bi-archive fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Departments</h6>
              <h3 class="mb-0">{{ $departments->count() }}</h3>
            </div>
            <i class="bi bi-building fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
  </div>

  {{-- search & filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Search</label>
          <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Name, email, phone, staff ID">
        </div>
        <div class="col-md-3">
          <label class="form-label">Department</label>
          <select name="department_id" class="form-select">
            <option value="">All Departments</option>
            @foreach(\App\Models\Department::orderBy('name')->get() as $d)
              <option value="{{ $d->id }}" @selected(request('department_id')==$d->id)>{{ $d->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="status" class="form-select">
            <option value="">All Status</option>
            <option value="active" @selected(request('status')==='active')>Active</option>
            <option value="archived" @selected(request('status')==='archived')>Archived</option>
          </select>
        </div>
        <div class="col-md-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-search"></i> Filter
          </button>
        </div>
      </form>
    </div>
  </div>

  {{-- table --}}
  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Staff</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Staff</th>
              <th>Contacts</th>
              <th>HR Details</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($staff as $s)
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <img src="{{ $s->photo_url }}" class="rounded-circle me-3" width="44" height="44" alt="avatar" onerror="this.src='https://ui-avatars.com/api/?name={{ urlencode($s->full_name) }}&background=0D8ABC&color=fff&size=44'">
                    <div>
                      <div class="fw-semibold">{{ $s->first_name }} {{ $s->last_name }}</div>
                      <div class="small text-muted">ID: {{ $s->staff_id }}</div>
                    </div>
                  </div>
                </td>
                <td>
                  <div class="small">
                    <div class="d-flex align-items-center gap-2 mb-1">
                      <i class="bi bi-envelope text-primary"></i> 
                      <span>{{ $s->work_email }}</span>
                    </div>
                    <div class="d-flex align-items-center gap-2 text-muted">
                      <i class="bi bi-telephone text-success"></i> 
                      <span>{{ $s->phone_number }}</span>
                    </div>
                  </div>
                </td>
                <td class="small">
                  <div class="mb-1">
                    <span class="text-muted">Dept:</span> 
                    <span class="badge bg-info">{{ $s->department->name ?? '—' }}</span>
                  </div>
                  <div class="mb-1">
                    <span class="text-muted">Title:</span> {{ $s->jobTitle->name ?? '—' }}
                  </div>
                  <div>
                    <span class="text-muted">Category:</span> 
                    <span class="badge bg-secondary">{{ $s->category->name ?? '—' }}</span>
                  </div>
                </td>
                <td>
                  @php 
                    $badge = $s->status === 'active' ? 'success' : 'secondary';
                  @endphp
                  <span class="badge bg-{{ $badge }}">{{ ucfirst($s->status) }}</span>
                </td>
                <td class="text-end">
                  <div class="btn-group" role="group">
                    <a href="{{ route('staff.show', $s->id) }}" class="btn btn-sm btn-outline-info" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('staff.edit', $s->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" title="More">
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                      <li>
                        <a class="dropdown-item" href="{{ route('staff.show', $s->id) }}">
                          <i class="bi bi-eye"></i> View Details
                        </a>
                      </li>
                      @if($s->status === 'active')
                        <li>
                          <form action="{{ route('staff.archive', $s->id) }}" method="POST" onsubmit="return confirm('Archive this staff member?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="dropdown-item text-danger">
                              <i class="bi bi-archive"></i> Archive
                            </button>
                          </form>
                        </li>
                      @else
                        <li>
                          <form action="{{ route('staff.restore', $s->id) }}" method="POST" onsubmit="return confirm('Restore this staff member?')">
                            @csrf @method('PATCH')
                            <button type="submit" class="dropdown-item text-success">
                              <i class="bi bi-arrow-counterclockwise"></i> Restore
                            </button>
                          </form>
                        </li>
                      @endif
                    </ul>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                  No staff found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($staff->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing {{ $staff->firstItem() }}–{{ $staff->lastItem() }} of {{ $staff->total() }} staff
        </div>
        {{ $staff->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>
@endsection
