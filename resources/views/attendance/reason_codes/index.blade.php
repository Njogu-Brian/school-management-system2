@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Attendance</div>
        <h1 class="mb-1">Attendance Reason Codes</h1>
        <p class="text-muted mb-0">Manage absence reason codes.</p>
      </div>
      <a href="{{ route('attendance.reason-codes.create') }}" class="btn btn-settings-primary">
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

    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0">Reason Codes</h5>
          <p class="text-muted small mb-0">Excuse and medical flags with status.</p>
        </div>
        @if($reasonCodes->count())
          <span class="input-chip">{{ $reasonCodes->count() }} total</span>
        @endif
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
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
                    <span class="pill-badge {{ $code->requires_excuse ? 'pill-warning' : 'pill-secondary' }}">{{ $code->requires_excuse ? 'Yes' : 'No' }}</span>
                  </td>
                  <td class="text-center">
                    <span class="pill-badge {{ $code->is_medical ? 'pill-danger' : 'pill-secondary' }}">{{ $code->is_medical ? 'Medical' : '—' }}</span>
                  </td>
                  <td class="text-center">
                    <span class="pill-badge {{ $code->is_active ? 'pill-success' : 'pill-secondary' }}">{{ $code->is_active ? 'Active' : 'Inactive' }}</span>
                  </td>
                  <td class="text-center">{{ $code->sort_order }}</td>
                  <td class="text-end">
                    <a href="{{ route('attendance.reason-codes.edit', $code) }}" class="btn btn-sm btn-ghost-strong">
                      <i class="bi bi-pencil"></i> Edit
                    </a>
                    <form action="{{ route('attendance.reason-codes.destroy', $code) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this reason code?')">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-ghost-strong text-danger">
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
</div>
@endsection
