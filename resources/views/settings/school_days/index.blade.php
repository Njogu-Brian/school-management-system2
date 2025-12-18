@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Settings / Calendar</div>
        <h1>School Days Management</h1>
        <p>Manage school days, holidays, and off days for scheduling and reporting.</p>
      </div>
      <form action="{{ route('settings.school-days.generate-holidays') }}" method="POST" class="d-inline">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <button type="submit" class="btn btn-ghost">
          <i class="bi bi-calendar-plus"></i> Generate Kenyan Holidays
        </button>
      </form>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show mt-3">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show mt-3">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="settings-card mt-3">
      <div class="card-header">
        <h5 class="mb-0">Filters</h5>
      </div>
      <div class="card-body">
        <form method="GET" class="row g-3">
          <div class="col-md-4">
            <label class="form-label">Year</label>
            <input type="number" name="year" class="form-control" value="{{ $year }}" min="2020" max="2100" onchange="this.form.submit()">
          </div>
          <div class="col-md-4">
            <label class="form-label">Type</label>
            <select name="type" class="form-select" onchange="this.form.submit()">
              <option value="">All Types</option>
              @foreach($types as $key => $label)
                <option value="{{ $key }}" @selected($type==$key)>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-4 d-flex align-items-end">
            <a href="{{ route('settings.school-days.index') }}" class="btn btn-ghost w-100">Reset Filters</a>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add Custom Day</h5>
        <span class="input-chip">Adds to {{ $year }}</span>
      </div>
      <div class="card-body">
        <form action="{{ route('settings.school-days.store') }}" method="POST">
          @csrf
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label">Date <span class="text-danger">*</span></label>
              <input type="date" name="date" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label">Type <span class="text-danger">*</span></label>
              <select name="type" class="form-select" required>
                @foreach($types as $key => $label)
                  <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Name</label>
              <input type="text" name="name" class="form-control" placeholder="e.g., Midterm Break Day 1">
            </div>
            <div class="col-md-3 d-flex align-items-end">
              <button type="submit" class="btn btn-settings-primary w-100">
                <i class="bi bi-save"></i> Add Day
              </button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header">
        <h5 class="mb-0"><i class="bi bi-calendar3"></i> School Days Calendar ({{ $year }})</h5>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-modern table-hover mb-0">
            <thead>
              <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Type</th>
                <th>Name</th>
                <th>Description</th>
                <th>Source</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
            <tbody>
              @forelse($schoolDays as $day)
                <tr>
                  <td class="fw-semibold">{{ $day->date->format('M d, Y') }}</td>
                  <td>{{ $day->date->format('l') }}</td>
                  <td>
                    <span class="badge bg-{{ $day->type == 'school_day' ? 'success' : ($day->type == 'holiday' ? 'danger' : ($day->type == 'midterm_break' ? 'warning' : 'secondary')) }}">
                      {{ $types[$day->type] ?? $day->type }}
                    </span>
                  </td>
                  <td>{{ $day->name ?? '—' }}</td>
                  <td class="text-muted small">{{ Str::limit($day->description ?? '—', 50) }}</td>
                  <td>
                    @if($day->is_kenyan_holiday)
                      <span class="badge bg-info">Auto (Kenyan)</span>
                    @elseif($day->is_custom)
                      <span class="badge bg-secondary">Custom</span>
                    @else
                      <span class="badge bg-light text-dark">System</span>
                    @endif
                  </td>
                  <td class="text-end">
                    @if(!$day->is_kenyan_holiday)
                      <form action="{{ route('settings.school-days.destroy', $day) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this day?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">
                          <i class="bi bi-trash"></i> Delete
                        </button>
                      </form>
                    @else
                      <span class="text-muted small">Auto-generated</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="7" class="text-center text-muted py-4">
                    No school days found for {{ $year }}. <a href="#" onclick="document.querySelector('form[action*=\"generate-holidays\"]').submit()">Generate holidays</a> to get started.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          {{ $schoolDays->links() }}
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

