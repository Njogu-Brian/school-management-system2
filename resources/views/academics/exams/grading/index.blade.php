@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Exams</div>
        <h1 class="mb-1">Class grading schemes</h1>
        <p class="text-muted mb-0">Map percentage-based letter grades to each class for exam marks and results.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('academics.exams.grading.bulk') }}" class="btn btn-outline-secondary">Apply to multiple classes</a>
        <a href="{{ route('academics.exams.grading.duplicate') }}" class="btn btn-settings-primary">Duplicate scheme</a>
      </div>
    </div>

    @includeIf('partials.alerts')

    <div class="settings-card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Class</th>
                <th>Grading scheme</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($classrooms as $c)
                @php $map = $mappings->get($c->id); @endphp
                <tr>
                  <td class="fw-semibold">{{ $c->name }}</td>
                  <td>{{ $map?->scheme?->name ?? 'Default (system)' }}</td>
                  <td class="text-end">
                    <a href="{{ route('academics.exams.grading.edit', $c) }}" class="btn btn-sm btn-outline-primary">Change</a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="text-center text-muted py-4">No classes available for your account.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
