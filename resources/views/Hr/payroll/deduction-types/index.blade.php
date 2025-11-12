@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">Deduction Types</h2>
      <small class="text-muted">Manage deduction type definitions</small>
    </div>
    <a href="{{ route('hr.payroll.deduction-types.create') }}" class="btn btn-primary">
      <i class="bi bi-plus-circle"></i> New Deduction Type
    </a>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card shadow-sm">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-list-ul"></i> All Deduction Types</h5>
    </div>
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Code</th>
              <th>Calculation Method</th>
              <th>Default Amount/Percentage</th>
              <th>Type</th>
              <th>Status</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($types as $type)
              <tr>
                <td>
                  <div class="fw-semibold">{{ $type->name }}</div>
                  @if($type->description)
                    <div class="small text-muted">{{ Str::limit($type->description, 50) }}</div>
                  @endif
                </td>
                <td>
                  @if($type->code)
                    <span class="badge bg-secondary">{{ $type->code }}</span>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $type->calculation_method)) }}</span>
                </td>
                <td>
                  @if($type->calculation_method === 'fixed_amount')
                    <strong>Ksh {{ number_format($type->default_amount ?? 0, 2) }}</strong>
                  @elseif($type->percentage)
                    <strong>{{ number_format($type->percentage, 2) }}%</strong>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @if($type->is_statutory)
                    <span class="badge bg-danger">Statutory</span>
                  @else
                    <span class="badge bg-primary">Custom</span>
                  @endif
                </td>
                <td>
                  <span class="badge bg-{{ $type->is_active ? 'success' : 'secondary' }}">
                    {{ $type->is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
                <td class="text-end">
                  <div class="btn-group" role="group">
                    <a href="{{ route('hr.payroll.deduction-types.show', $type->id) }}" class="btn btn-sm btn-outline-info" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    <a href="{{ route('hr.payroll.deduction-types.edit', $type->id) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    @if(!$type->is_statutory)
                      <form action="{{ route('hr.payroll.deduction-types.destroy', $type->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this deduction type?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    @endif
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center py-4 text-muted">
                  <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                  No deduction types found.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($types->hasPages())
      <div class="card-footer d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Showing {{ $types->firstItem() }}–{{ $types->lastItem() }} of {{ $types->total() }} types
        </div>
        {{ $types->links() }}
      </div>
    @endif
  </div>
</div>
@endsection

