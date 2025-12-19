@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · CBC Substrands</div>
        <h1 class="mb-1">CBC Substrands</h1>
        <p class="text-muted mb-0">Manage substrands under strands and learning areas.</p>
      </div>
      @can('cbc_strands.manage')
      <a href="{{ route('academics.cbc-substrands.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Add Substrand</a>
      @endcan
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Strand</label>
            <select name="strand_id" class="form-select">
              <option value="">All Strands</option>
              @foreach($strands as $strand)
                <option value="{{ $strand->id }}" {{ request('strand_id') == $strand->id ? 'selected' : '' }}>{{ $strand->code }} - {{ $strand->name }} ({{ $strand->level }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Search by name or code">
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i> Filter</button>
          </div>
          <div class="col-md-2">
            <a href="{{ route('academics.cbc-substrands.index') }}" class="btn btn-ghost-strong w-100">Clear</a>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Strand</th>
                <th>Learning Area</th>
                <th>Level</th>
                <th>Suggested Lessons</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($substrands as $substrand)
                <tr>
                  <td class="fw-semibold">{{ $substrand->code }}</td>
                  <td>{{ $substrand->name }}</td>
                  <td><a href="{{ route('academics.cbc-strands.show', $substrand->strand_id) }}" class="text-reset text-decoration-none">{{ $substrand->strand->name ?? 'N/A' }}</a></td>
                  <td>@if($substrand->strand)<span class="pill-badge pill-info">{{ $substrand->strand->learning_area ?? 'N/A' }}</span>@else<span class="text-muted">N/A</span>@endif</td>
                  <td>@if($substrand->strand)<span class="pill-badge pill-secondary">{{ $substrand->strand->level ?? 'N/A' }}</span>@else<span class="text-muted">N/A</span>@endif</td>
                  <td><span class="pill-badge pill-primary">{{ $substrand->suggested_lessons ?? 'N/A' }}</span></td>
                  <td><span class="pill-badge pill-{{ $substrand->is_active ? 'success' : 'muted' }}">{{ $substrand->is_active ? 'Active' : 'Inactive' }}</span></td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.cbc-substrands.show', $substrand) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                      @can('cbc_strands.manage')
                      <a href="{{ route('academics.cbc-substrands.edit', $substrand) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                      @endcan
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No substrands found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $substrands->links() }}</div>
    </div>
  </div>
</div>
@endsection
