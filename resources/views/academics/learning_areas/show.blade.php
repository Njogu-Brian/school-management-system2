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
        <h1 class="mb-1">{{ $learning_area->name }}</h1>
        <p class="text-muted mb-0">Details, strands, and quick actions.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        @can('learning_areas.edit')
        <a href="{{ route('academics.learning-areas.edit', $learning_area) }}" class="btn btn-ghost-strong"><i class="bi bi-pencil"></i> Edit</a>
        @endcan
        <a href="{{ route('academics.learning-areas.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i><h5 class="mb-0">Learning Area Details</h5></div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-3">Code</dt><dd class="col-sm-9"><strong>{{ $learning_area->code }}</strong></dd>
              <dt class="col-sm-3">Name</dt><dd class="col-sm-9">{{ $learning_area->name }}</dd>
              <dt class="col-sm-3">Description</dt><dd class="col-sm-9">{{ $learning_area->description ?? '-' }}</dd>
              <dt class="col-sm-3">Level Category</dt><dd class="col-sm-9">@if($learning_area->level_category)<span class="pill-badge pill-info">{{ $learning_area->level_category }}</span>@else<span class="text-muted">-</span>@endif</dd>
              <dt class="col-sm-3">Levels</dt><dd class="col-sm-9">@if($learning_area->levels && count($learning_area->levels) > 0) @foreach($learning_area->levels as $level)<span class="pill-badge pill-secondary me-1">{{ $level }}</span>@endforeach @else <span class="text-muted">-</span> @endif</dd>
              <dt class="col-sm-3">Type</dt><dd class="col-sm-9">@if($learning_area->is_core)<span class="pill-badge pill-primary">Core</span>@else<span class="pill-badge pill-muted">Optional</span>@endif</dd>
              <dt class="col-sm-3">Status</dt><dd class="col-sm-9">@if($learning_area->is_active)<span class="pill-badge pill-success">Active</span>@else<span class="pill-badge pill-danger">Inactive</span>@endif</dd>
            </dl>
          </div>
        </div>

        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-diagram-3"></i><h5 class="mb-0">Strands ({{ $strands->count() }})</h5></div>
          <div class="card-body">
            @if($strands->count() > 0)
              <div class="table-responsive">
                <table class="table table-modern table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Code</th>
                      <th>Name</th>
                      <th>Level</th>
                      <th>Substrands</th>
                      <th>Competencies</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($strands as $strand)
                      <tr>
                        <td class="fw-semibold">{{ $strand->code }}</td>
                        <td>{{ $strand->name }}</td>
                        <td><span class="pill-badge pill-secondary">{{ $strand->level }}</span></td>
                        <td>{{ $strand->substrands_count ?? 0 }}</td>
                        <td>{{ $strand->competencies_count ?? 0 }}</td>
                        <td>
                          <a href="{{ route('academics.cbc-strands.show', $strand) }}" class="btn btn-sm btn-ghost-strong text-info"><i class="bi bi-eye"></i></a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="text-muted mb-0">No strands found for this learning area.</p>
            @endif
          </div>
        </div>
      </div>

      <div class="col-md-4">
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-lightning-charge"></i><h5 class="mb-0">Quick Actions</h5></div>
          <div class="card-body d-grid gap-2">
            @can('cbc_strands.create')
            <a href="{{ route('academics.cbc-strands.create') }}?learning_area={{ $learning_area->id }}" class="btn btn-settings-primary"><i class="bi bi-plus-circle"></i> Add Strand</a>
            @endcan
            @can('competencies.create')
            <a href="{{ route('academics.competencies.create') }}" class="btn btn-ghost-strong text-info"><i class="bi bi-plus-circle"></i> Add Competency</a>
            @endcan
            <a href="{{ route('academics.cbc-strands.index') }}?learning_area={{ $learning_area->name }}" class="btn btn-ghost-strong"><i class="bi bi-list"></i> View All Strands</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
