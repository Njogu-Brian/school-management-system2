@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics / Promotions</div>
        <h1 class="mb-1">Alumni Students</h1>
        <p class="text-muted mb-0">View all students who have graduated and been marked as alumni.</p>
      </div>
      <a href="{{ route('academics.promotions.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back to Promotions
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

    {{-- Filters --}}
    <div class="settings-card mb-3">
      <div class="card-body">
        <form method="GET" action="{{ route('academics.promotions.alumni') }}" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Search</label>
            <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Name or Admission Number">
          </div>
          <div class="col-md-4">
            <label class="form-label">Alumni Year</label>
            <select name="alumni_year" class="form-select">
              <option value="">All Years</option>
              @foreach($years as $year)
                <option value="{{ $year }}" {{ request('alumni_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label">&nbsp;</label>
            <div>
              <button type="submit" class="btn btn-settings-primary w-100">
                <i class="bi bi-search"></i> Filter
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    {{-- Alumni List --}}
    <div class="settings-card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h5 class="mb-0"><i class="bi bi-trophy"></i> Alumni Students</h5>
          <p class="text-muted small mb-0">Total: {{ $alumni->total() }} alumni</p>
        </div>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-modern table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Admission #</th>
                <th>Name</th>
                <th>Last Class</th>
                <th>Last Stream</th>
                <th>Alumni Date</th>
                <th>Parent Contact</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($alumni as $student)
                <tr>
                  <td class="fw-semibold">{{ $student->admission_number }}</td>
                  <td>{{ $student->full_name }}</td>
                  <td>
                    @if($student->lastClassroom)
                      <span class="pill-badge pill-info">{{ $student->lastClassroom->name }}</span>
                    @else
                      <span class="text-muted">N/A</span>
                    @endif
                  </td>
                  <td>
                    @if($student->lastStream)
                      <span class="pill-badge pill-secondary">{{ $student->lastStream->name }}</span>
                    @else
                      <span class="text-muted">N/A</span>
                    @endif
                  </td>
                  <td>
                    @if($student->alumni_date)
                      {{ $student->alumni_date->format('M d, Y') }}
                    @else
                      <span class="text-muted">N/A</span>
                    @endif
                  </td>
                  <td>
                    @if($student->parent)
                      {{ $student->parent->father_phone ?? $student->parent->mother_phone ?? $student->parent->guardian_phone ?? 'N/A' }}
                    @else
                      <span class="text-muted">N/A</span>
                    @endif
                  </td>
                  <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                      <a href="{{ route('students.show', $student) }}" class="btn btn-sm btn-ghost-strong" title="View Student">
                        <i class="bi bi-eye"></i>
                      </a>
                    </div>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0">No alumni students found.</p>
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
      @if($alumni->hasPages())
        <div class="card-footer">
          {{ $alumni->links() }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

