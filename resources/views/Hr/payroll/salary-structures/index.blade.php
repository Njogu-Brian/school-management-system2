@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Salary Structures</h2>
      <small class="text-muted">Manage staff salary structures</small>
    </div>
    <a href="{{ route('hr.payroll.salary-structures.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> New Structure
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Filters --}}
  <div class="card shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" class="row g-2 align-items-end">
        <div class="col-md-4">
          <label class="form-label">Staff</label>
          <select name="staff_id" class="form-select">
            <option value="">All Staff</option>
            @foreach($staff as $s)
              <option value="{{ $s->id }}" @selected(request('staff_id')==$s->id)>{{ $s->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Status</label>
          <select name="is_active" class="form-select">
            <option value="">All</option>
            <option value="1" @selected(request('is_active')==='1')>Active</option>
            <option value="0" @selected(request('is_active')==='0')>Inactive</option>
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

  {{-- Table --}}
  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Salary Structures</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Staff</th>
              <th>Basic Salary</th>
              <th>Gross Salary</th>
              <th>Net Salary</th>
              <th>Effective Period</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($structures as $structure)
              <tr>
                <td>
                  <div class="fw-semibold">{{ $structure->staff->name }}</div>
                  <div class="small text-muted">{{ $structure->staff->department->name ?? '—' }}</div>
                </td>
                <td><strong>Ksh {{ number_format($structure->basic_salary, 2) }}</strong></td>
                <td><strong class="text-success">Ksh {{ number_format($structure->gross_salary, 2) }}</strong></td>
                <td><strong class="text-primary">Ksh {{ number_format($structure->net_salary, 2) }}</strong></td>
                <td>
                  <div>{{ $structure->effective_from->format('M d, Y') }}</div>
                  @if($structure->effective_to)
                    <div class="small text-muted">to {{ $structure->effective_to->format('M d, Y') }}</div>
                  @else
                    <div class="small text-muted">Ongoing</div>
                  @endif
                </td>
                <td>
                  <span class="badge bg-{{ $structure->is_active ? 'success' : 'secondary' }}">
                    {{ $structure->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group" role="group">
                    <a href="{{ route('hr.payroll.salary-structures.show', $structure->id) }}" class="btn btn-sm btn-outline-info" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('hr.payroll.salary-structures.edit', $structure->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                  No salary structures found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($structures->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing {{ $structures->firstItem() }}–{{ $structures->lastItem() }} of {{ $structures->total() }} structures
        </div>
        {{ $structures->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

