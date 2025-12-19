@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Learning Areas</div>
        <h1 class="mb-1">Learning Areas</h1>
        <p class="text-muted mb-0">Manage codes, levels, strands, and status.</p>
      </div>
      @can('learning_areas.create')
      <a href="{{ route('academics.learning-areas.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Add Learning Area</a>
      @endcan
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label">Level Category</label>
            <select name="level_category" class="form-select">
              <option value="">All Categories</option>
              @foreach($levelCategories as $category)
                <option value="{{ $category }}" {{ request('level_category') == $category ? 'selected' : '' }}>{{ $category }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Level</label>
            <select name="level" class="form-select">
              <option value="">All Levels</option>
              @foreach($levels as $level)
                <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>{{ $level }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Type</label>
            <select name="is_core" class="form-select">
              <option value="">All</option>
              <option value="1" {{ request('is_core') == '1' ? 'selected' : '' }}>Core</option>
              <option value="0" {{ request('is_core') == '0' ? 'selected' : '' }}>Optional</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" placeholder="Search..." value="{{ request('search') }}">
          </div>
          <div class="col-md-1">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i></button>
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
                <th>Category</th>
                <th>Levels</th>
                <th>Type</th>
                <th>Strands</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($learningAreas as $learningArea)
                <tr>
                  <td class="fw-semibold">{{ $learningArea->code }}</td>
                  <td>{{ $learningArea->name }}</td>
                  <td>@if($learningArea->level_category)<span class="pill-badge pill-info">{{ $learningArea->level_category }}</span>@else<span class="text-muted">-</span>@endif</td>
                  <td>
                    @if($learningArea->levels)
                      <div class="d-flex flex-wrap gap-1">
                        @foreach(array_slice($learningArea->levels, 0, 3) as $level)
                          <span class="pill-badge pill-secondary">{{ $level }}</span>
                        @endforeach
                        @if(count($learningArea->levels) > 3)
                          <span class="pill-badge pill-muted">+{{ count($learningArea->levels) - 3 }}</span>
                        @endif
                      </div>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>@if($learningArea->is_core)<span class="pill-badge pill-primary">Core</span>@else<span class="pill-badge pill-muted">Optional</span>@endif</td>
                  <td><span class="pill-badge pill-secondary">{{ $learningArea->strands_count ?? 0 }}</span></td>
                  <td>@if($learningArea->is_active)<span class="pill-badge pill-success">Active</span>@else<span class="pill-badge pill-danger">Inactive</span>@endif</td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.learning-areas.show', $learningArea) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                      @can('learning_areas.edit')
                      <a href="{{ route('academics.learning-areas.edit', $learningArea) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                      @endcan
                      @can('learning_areas.delete')
                      <form action="{{ route('academics.learning-areas.destroy', $learningArea) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this learning area?');">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-ghost-strong text-danger" title="Delete"><i class="bi bi-trash"></i></button>
                      </form>
                      @endcan
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No learning areas found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $learningAreas->links() }}</div>
    </div>
  </div>
</div>
@endsection
