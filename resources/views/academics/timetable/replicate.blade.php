@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Timetable</div>
        <h1 class="mb-1">Bulk replication</h1>
        <p class="text-muted mb-0">Copy layout + requirements + allocations from one stream to other streams.</p>
      </div>
      <a href="{{ route('academics.timetable.whole-school') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Whole-school</a>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="settings-card">
      <div class="card-body">
        <form method="POST" action="{{ route('academics.timetable.whole-school.replicate.store') }}" class="row g-3 align-items-end">
          @csrf
          <div class="col-md-3">
            <label class="form-label">Academic Year</label>
            <select name="academic_year_id" class="form-select" required>
              @foreach($years as $y)
                <option value="{{ $y->id }}" {{ $selectedYear && $selectedYear->id == $y->id ? 'selected' : '' }}>{{ $y->year }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Term</label>
            <select name="term_id" class="form-select" required>
              @foreach($terms as $t)
                <option value="{{ $t->id }}" {{ $selectedTerm && $selectedTerm->id == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Source stream</label>
            <select name="source_stream_id" class="form-select" required>
              @foreach($streams as $s)
                <option value="{{ $s->id }}">{{ $s->name }}{{ $s->classroom?->name ? ' · '.$s->classroom->name : '' }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-9">
            <label class="form-label">Target streams</label>
            <select name="target_stream_ids[]" class="form-select" multiple required>
              @foreach($streams as $s)
                <option value="{{ $s->id }}">{{ $s->name }}{{ $s->classroom?->name ? ' · '.$s->classroom->name : '' }}</option>
              @endforeach
            </select>
            <div class="form-text">This will upsert: stream layout, subject requirements, subject-teacher splits, activities and activity teachers.</div>
          </div>
          <div class="col-md-3">
            <button class="btn btn-settings-primary w-100" type="submit"><i class="bi bi-copy"></i> Replicate</button>
          </div>
        </form>
      </div>
    </div>

  </div>
</div>
@endsection

