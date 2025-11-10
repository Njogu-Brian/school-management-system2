@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Term Days Management</h2>
      <small class="text-muted">Set opening and closing dates for terms</small>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Add Term Days --}}
  <div class="card mb-3">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add Term Days</h5>
    </div>
    <div class="card-body">
      <form action="{{ route('settings.term-days.store') }}" method="POST">
        @csrf
        <div class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Academic Year <span class="text-danger">*</span></label>
            <select name="academic_year_id" class="form-select" required>
              <option value="">Select Year</option>
              @foreach($academicYears as $year)
                <option value="{{ $year->id }}">{{ $year->year }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Term (Optional)</label>
            <select name="term_id" class="form-select">
              <option value="">All Terms</option>
              @foreach($terms as $term)
                <option value="{{ $term->id }}">{{ $term->name }}</option>
              @endforeach
            </select>
            <small class="text-muted">Leave empty for year-wide settings</small>
          </div>
          <div class="col-md-2">
            <label class="form-label">Opening Date <span class="text-danger">*</span></label>
            <input type="date" name="opening_date" class="form-control" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Closing Date <span class="text-danger">*</span></label>
            <input type="date" name="closing_date" class="form-control" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Expected Days</label>
            <input type="number" name="expected_school_days" class="form-control" min="0" placeholder="Auto">
            <small class="text-muted">Leave empty for auto-calculation</small>
          </div>
        </div>
        <div class="row g-3 mt-2">
          <div class="col-md-12">
            <label class="form-label">Notes</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
          </div>
          <div class="col-md-12">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-save"></i> Save Term Days
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  {{-- Term Days List --}}
  <div class="card">
    <div class="card-header">
      <h5 class="mb-0"><i class="bi bi-calendar-range"></i> Term Days Records</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>Academic Year</th>
              <th>Term</th>
              <th>Opening Date</th>
              <th>Closing Date</th>
              <th class="text-center">Duration</th>
              <th class="text-center">Expected Days</th>
              <th class="text-center">Actual Days</th>
              <th>Notes</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($termDays as $termDay)
              @php
                $duration = \Carbon\Carbon::parse($termDay->opening_date)->diffInDays(\Carbon\Carbon::parse($termDay->closing_date));
                $actualDays = $termDay->calculateActualSchoolDays();
              @endphp
              <tr>
                <td class="fw-semibold">{{ $termDay->academicYear->year ?? '—' }}</td>
                <td>{{ $termDay->term->name ?? 'All Terms' }}</td>
                <td>{{ $termDay->opening_date->format('M d, Y') }}</td>
                <td>{{ $termDay->closing_date->format('M d, Y') }}</td>
                <td class="text-center">{{ $duration }} days</td>
                <td class="text-center">{{ $termDay->expected_school_days ?? '—' }}</td>
                <td class="text-center">
                  <span class="badge bg-{{ $actualDays >= ($termDay->expected_school_days ?? 0) ? 'success' : 'warning' }}">
                    {{ $actualDays }} days
                  </span>
                </td>
                <td class="text-muted small">{{ Str::limit($termDay->notes ?? '—', 30) }}</td>
                <td class="text-end">
                  <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $termDay->id }}">
                    <i class="bi bi-pencil"></i> Edit
                  </button>
                  <form action="{{ route('settings.term-days.destroy', $termDay) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this term days record?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="bi bi-trash"></i> Delete
                    </button>
                  </form>
                </td>
              </tr>

              {{-- Edit Modal --}}
              <div class="modal fade" id="editModal{{ $termDay->id }}" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Term Days</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('settings.term-days.update', $termDay) }}" method="POST">
                      @csrf
                      @method('PUT')
                      <div class="modal-body">
                        <div class="mb-3">
                          <label class="form-label">Academic Year</label>
                          <select name="academic_year_id" class="form-select" required>
                            @foreach($academicYears as $year)
                              <option value="{{ $year->id }}" @selected($termDay->academic_year_id==$year->id)>{{ $year->year }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="mb-3">
                          <label class="form-label">Term</label>
                          <select name="term_id" class="form-select">
                            <option value="">All Terms</option>
                            @foreach($terms as $term)
                              <option value="{{ $term->id }}" @selected($termDay->term_id==$term->id)>{{ $term->name }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="form-label">Opening Date</label>
                            <input type="date" name="opening_date" class="form-control" value="{{ $termDay->opening_date->toDateString() }}" required>
                          </div>
                          <div class="col-md-6">
                            <label class="form-label">Closing Date</label>
                            <input type="date" name="closing_date" class="form-control" value="{{ $termDay->closing_date->toDateString() }}" required>
                          </div>
                          <div class="col-md-12">
                            <label class="form-label">Expected School Days</label>
                            <input type="number" name="expected_school_days" class="form-control" value="{{ $termDay->expected_school_days }}" min="0">
                          </div>
                          <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2">{{ $termDay->notes }}</textarea>
                          </div>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">
                  No term days records found. Add one above to get started.
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection

