@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Attendance Reason Codes</h2>
      <small class="text-muted">Manage absence reason codes</small>
    </div>
    <a href="{{ route('attendance.reason-codes.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> Add Reason Code
    </a>
  </div>

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

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Code</th>
              <th>Name</th>
              <th>Description</th>
              <th class="text-center">Requires Excuse</th>
              <th class="text-center">Medical</th>
              <th class="text-center">Status</th>
              <th class="text-center">Sort Order</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($reasonCodes as $code)
              <tr>
                <td class="fw-semibold">{{ $code->code }}</td>
                <td>{{ $code->name }}</td>
                <td class="text-muted">{{ $code->description ?? '—' }}</td>
                <td class="text-center">
                  @if($code->requires_excuse)
                    <span class="badge bg-warning">Yes</span>
                  @else
                    <span class="badge bg-secondary">No</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($code->is_medical)
                    <span class="badge bg-danger">Medical</span>
                  @else
                    <span class="badge bg-secondary">—</span>
                  @endif
                </td>
                <td class="text-center">
                  @if($code->is_active)
                    <span class="badge bg-success">Active</span>
                  @else
                    <span class="badge bg-secondary">Inactive</span>
                  @endif
                </td>
                <td class="text-center">{{ $code->sort_order }}</td>
                <td class="text-end">
                  <a href="{{ route('attendance.reason-codes.edit', $code) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil"></i> Edit
                  </a>
                  <form action="{{ route('attendance.reason-codes.destroy', $code) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this reason code?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bi bi-trash"></i> Delete
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  No reason codes found. <a href="{{ route('attendance.reason-codes.create') }}">Create one</a>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

