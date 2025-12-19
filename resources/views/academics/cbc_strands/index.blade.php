@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · CBC Strands</div>
        <h1 class="mb-1">CBC Strands</h1>
        <p class="text-muted mb-0">Manage strands by learning area and level.</p>
      </div>
      <a href="{{ route('academics.cbc-strands.create') }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Add Strand</a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
          <div class="col-md-4">
            <label class="form-label">Learning Area</label>
            <select name="learning_area" class="form-select">
              <option value="">All Learning Areas</option>
              @foreach($learningAreas as $area)
                <option value="{{ $area }}" {{ request('learning_area') == $area ? 'selected' : '' }}>{{ $area }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">Level</label>
            <select name="level" class="form-select">
              <option value="">All Levels</option>
              @foreach($levels as $level)
                <option value="{{ $level }}" {{ request('level') == $level ? 'selected' : '' }}>{{ $level }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-settings-primary w-100"><i class="bi bi-search"></i> Filter</button>
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
                <th>Level</th>
                <th>Substrands</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($strands as $strand)
                <tr>
                  <td class="fw-semibold">{{ $strand->code }}</td>
                  <td>{{ $strand->name }}</td>
                  <td><span class="pill-badge pill-info">{{ $strand->learning_area }}</span></td>
                  <td><span class="pill-badge pill-secondary">{{ $strand->level }}</span></td>
                  <td><span class="pill-badge pill-primary">{{ $strand->substrands_count }}</span></td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                      <a href="{{ route('academics.cbc-strands.show', $strand) }}" class="btn btn-sm btn-ghost-strong text-info" title="View"><i class="bi bi-eye"></i></a>
                      <a href="{{ route('academics.cbc-strands.edit', $strand) }}" class="btn btn-sm btn-ghost-strong" title="Edit"><i class="bi bi-pencil"></i></a>
                    </div>
                  </td>
                </tr>
              @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No strands found</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-end">{{ $strands->links() }}</div>
    </div>
  </div>
</div>
@endsection
