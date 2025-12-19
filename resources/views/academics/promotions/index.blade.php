@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">Student Promotions</h1>
        <p class="text-muted mb-0">Promote students to mapped classes or mark as alumni.</p>
      </div>
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
          <h5 class="mb-0"><i class="bi bi-diagram-3"></i> Class Mapping & Promotions</h5>
          <p class="text-muted small mb-0">Mapped next classes, readiness, and promote actions.</p>
        </div>
        <span class="input-chip">{{ $classrooms->count() }} classes</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Class</th>
                <th>Next Class</th>
                <th>Students</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($classrooms as $classroom)
                @php
                  $currentYear = \App\Models\AcademicYear::where('is_active', true)->first();
                  $alreadyPromoted = false;
                  if ($currentYear) {
                      $alreadyPromoted = \App\Models\StudentAcademicHistory::where('classroom_id', $classroom->id)
                          ->where('academic_year_id', $currentYear->id)
                          ->where('promotion_status', 'promoted')
                          ->exists();
                  }
                @endphp
                <tr>
                  <td class="fw-semibold">{{ $classroom->name }}</td>
                  <td>
                    @if($classroom->is_alumni)
                      <span class="text-muted d-inline-flex align-items-center gap-1"><i class="bi bi-trophy"></i> Graduation → Alumni</span>
                    @elseif($classroom->nextClass)
                      <span class="text-success d-inline-flex align-items-center gap-1"><i class="bi bi-arrow-right"></i> {{ $classroom->nextClass->name }}</span>
                    @else
                      <span class="text-danger d-inline-flex align-items-center gap-1"><i class="bi bi-exclamation-triangle"></i> Not Mapped</span>
                    @endif
                  </td>
                  <td><span class="pill-badge pill-primary">{{ $classroom->students->count() }} students</span></td>
                  <td>
                    @if($alreadyPromoted)
                      <span class="pill-badge pill-warning"><i class="bi bi-check-circle"></i> Already Promoted</span>
                    @elseif($classroom->students->count() > 0 && ($classroom->nextClass || $classroom->is_alumni))
                      <span class="pill-badge pill-success">Ready</span>
                    @else
                      <span class="pill-badge pill-muted">Not Ready</span>
                    @endif
                  </td>
                  <td class="text-end">
                    @if($classroom->students->count() > 0 && ($classroom->nextClass || $classroom->is_alumni))
                      @if($alreadyPromoted)
                        <span class="text-muted small">Already promoted this year</span>
                      @else
                        <div class="d-flex justify-content-end gap-1 flex-wrap">
                          <a href="{{ route('academics.promotions.show', $classroom) }}" class="btn btn-sm btn-ghost-strong" title="Select Students">
                            <i class="bi bi-arrow-up-circle"></i> Promote
                          </a>
                          <form action="{{ route('academics.promotions.promote-all', $classroom) }}" method="POST" onsubmit="return confirm('Promote ALL students from {{ $classroom->name }}?');">
                            @csrf
                            <input type="hidden" name="academic_year_id" value="{{ $currentYear?->id }}">
                            <input type="hidden" name="term_id" value="{{ $currentTerm?->id }}">
                            <input type="hidden" name="promotion_date" value="{{ date('Y-m-d') }}">
                            <button type="submit" class="btn btn-sm btn-settings-primary" title="Promote All Students">
                              <i class="bi bi-arrow-up-circle-fill"></i> Promote All
                            </button>
                          </form>
                        </div>
                      @endif
                    @else
                      <span class="text-muted">No students</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No classrooms found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
