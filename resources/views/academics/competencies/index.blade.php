@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Competencies</div>
        <h1 class="mb-1">Competencies</h1>
        <p class="text-muted mb-0">Browse competencies by learning area, strand, and level.</p>
      </div>
      @can('competencies.create')
      <a href="{{ route('academics.competencies.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Add Competency</a>
      @endcan
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Learning Area</label>
            <select name="learning_area_id" class="form-select">
              <option value="">All Learning Areas</option>
              @foreach($learningAreas as $area)
                <option value="{{ $area->id }}" {{ request('learning_area_id') == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Strand</label>
            <select name="strand_id" class="form-select">
              <option value="">All Strands</option>
              @foreach($strands as $strand)
                <option value="{{ $strand->id }}" {{ request('strand_id') == $strand->id ? 'selected' : '' }}>{{ $strand->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Substrand</label>
            <select name="substrand_id" class="form-select">
              <option value="">All Substrands</option>
              @foreach($substrands as $substrand)
                <option value="{{ $substrand->id }}" {{ request('substrand_id') == $substrand->id ? 'selected' : '' }}>{{ $substrand->name }} ({{ $substrand->strand->name ?? '' }})</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Level</label>
            <select name="competency_level" class="form-select">
              <option value="">All Levels</option>
              @foreach($competencyLevels as $level)
                <option value="{{ $level }}" {{ request('competency_level') == $level ? 'selected' : '' }}>{{ $level }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-1">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i></button>
          </div>
          <div class="col-12">
            <input type="text" name="search" class="form-control" placeholder="Search by code, name, or description..." value="{{ request('search') }}">
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
                <th>Learning Area</th>
                <th>Strand</th>
                <th>Substrand</th>
                <th>Level</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($competencies as $competency)
                <tr>
                  <td class="fw-semibold">{{ $competency->code }}</td>
                  <td>{{ $competency->name }}</td>
                  <td>@if($competency->substrand && $competency->substrand->strand && $competency->substrand->strand->learningArea)<span class="pill-badge pill-info">{{ $competency->substrand->strand->learningArea->name }}</span>@else<span class="text-muted">-</span>@endif</td>
                  <td>{{ $competency->substrand->strand->name ?? '-' }}</td>
                  <td>{{ $competency->substrand->name ?? '-' }}</td>
                  <td>@if($competency->competency_level)<span class="pill-badge pill-secondary">{{ $competency->competency_level }}</span>@else<span class="text-muted">-</span>@endif</td>
                  <td>@if($competency->is_active)<span class="pill-badge pill-success">Active</span>@else<span class="pill-badge pill-danger">Inactive</span>@endif</td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.competencies.show', $competency) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                      @can('competencies.edit')
                      <a href="{{ route('academics.competencies.edit', $competency) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                      @endcan
                      @can('competencies.delete')
                      <form action="{{ route('academics.competencies.destroy', $competency) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                      </form>
                      @endcan
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No competencies found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $competencies->links() }}</div>
    </div>
  </div>
</div>
@endsection
