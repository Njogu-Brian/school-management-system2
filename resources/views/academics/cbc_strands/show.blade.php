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
        <h1 class="mb-1">{{ $cbc_strand->name }}</h1>
        <p class="text-muted mb-0">Strand details and substrands.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('academics.cbc-strands.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        <a href="{{ route('academics.cbc-strands.edit', $cbc_strand) }}" class="btn btn-ghost-strong"><i class="bi bi-pencil"></i> Edit</a>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-md-8">
        <div class="settings-card mb-3">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-journal-text"></i><h5 class="mb-0">Strand Details</h5></div>
          <div class="card-body">
            <table class="table table-borderless mb-0">
              <tr><th width="160">Code</th><td><strong>{{ $cbc_strand->code }}</strong></td></tr>
              <tr><th>Name</th><td>{{ $cbc_strand->name }}</td></tr>
              <tr><th>Learning Area</th><td><span class="pill-badge pill-info">{{ $cbc_strand->learning_area }}</span></td></tr>
              <tr><th>Level</th><td><span class="pill-badge pill-secondary">{{ $cbc_strand->level }}</span></td></tr>
              @if($cbc_strand->description)<tr><th>Description</th><td>{{ $cbc_strand->description }}</td></tr>@endif
            </table>
          </div>
        </div>

        @if($cbc_strand->substrands->count() > 0)
        <div class="settings-card">
          <div class="card-header"><h5 class="mb-0">Substrands ({{ $cbc_strand->substrands->count() }})</h5></div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-modern table-hover align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Learning Outcomes</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($cbc_strand->substrands as $substrand)
                    <tr>
                      <td class="fw-semibold">{{ $substrand->code }}</td>
                      <td>{{ $substrand->name }}</td>
                      <td>
                        @if($substrand->learning_outcomes)
                          @if(is_array($substrand->learning_outcomes))
                            <ul class="mb-0">@foreach($substrand->learning_outcomes as $outcome)<li>{{ $outcome }}</li>@endforeach</ul>
                          @else
                            {{ $substrand->learning_outcomes }}
                          @endif
                        @else
                          <span class="text-muted">N/A</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>
        @endif
      </div>

      <div class="col-md-4">
        <div class="settings-card">
          <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i><h5 class="mb-0">Information</h5></div>
          <div class="card-body">
            <small class="text-muted d-block mb-1"><strong>Status:</strong> <span class="pill-badge pill-{{ $cbc_strand->is_active ? 'success' : 'muted' }}">{{ $cbc_strand->is_active ? 'Active' : 'Inactive' }}</span></small>
            <small class="text-muted d-block mb-1"><strong>Display Order:</strong> {{ $cbc_strand->display_order }}</small>
            <small class="text-muted d-block"><strong>Created:</strong> {{ $cbc_strand->created_at->format('d M Y') }}</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
