@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Academic Years & Terms</h2>
      <small class="text-muted">Manage academic years, terms, and term dates</small>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('settings.academic.year.create') }}" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Add Academic Year
      </a>
      <a href="{{ route('settings.academic.term.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Add Term
      </a>
    </div>
  </div>

  @if(session('success')) 
    <div class="alert alert-success alert-dismissible fade show">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-bordered table-hover">
          <thead class="table-light">
            <tr>
              <th>Year</th>
              <th class="text-center">Active</th>
              <th>Terms</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($years as $year)
            <tr>
              <td class="fw-semibold">{{ $year->year }}</td>
              <td class="text-center">
                @if($year->is_active)
                  <span class="badge bg-success">Active</span>
                @else
                  <span class="badge bg-secondary">Inactive</span>
                @endif
              </td>
              <td>
                @if($year->terms->count() > 0)
                  <div class="accordion" id="termsAccordion{{ $year->id }}">
                    @foreach($year->terms as $term)
                      @php
                        $termDay = $termDays[$term->id] ?? null;
                        $termDayRecord = $termDay ? $termDay->first() : null;
                      @endphp
                      <div class="accordion-item mb-2">
                        <h2 class="accordion-header" id="heading{{ $term->id }}">
                          <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $term->id }}">
                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                              <div>
                                <strong>{{ $term->name }}</strong>
                                @if($term->is_current)
                                  <span class="badge bg-success ms-2">Current</span>
                                @endif
                                @if($termDayRecord)
                                  <span class="badge bg-info ms-2">
                                    {{ $termDayRecord->opening_date->format('M d') }} - {{ $termDayRecord->closing_date->format('M d, Y') }}
                                  </span>
                                @endif
                              </div>
                              <div>
                                <a href="{{ route('settings.academic.term.edit', $term) }}" class="btn btn-sm btn-outline-primary me-1">
                                  <i class="bi bi-pencil"></i> Edit
                                </a>
                                <form action="{{ route('settings.academic.term.destroy', $term) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete term?')">
                                  @csrf @method('DELETE')
                                  <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i> Delete
                                  </button>
                                </form>
                              </div>
                            </div>
                          </button>
                        </h2>
                        <div id="collapse{{ $term->id }}" class="accordion-collapse collapse" data-bs-parent="#termsAccordion{{ $year->id }}">
                          <div class="accordion-body">
                            {{-- Term Days Section --}}
                            @if($termDayRecord)
                              <div class="card mb-3">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                  <strong>Term Dates</strong>
                                  <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editTermDaysModal{{ $termDayRecord->id }}">
                                      <i class="bi bi-pencil"></i> Edit Dates
                                    </button>
                                    <form action="{{ route('settings.academic.term-days.destroy', $termDayRecord) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete term dates?')">
                                      @csrf @method('DELETE')
                                      <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Delete
                                      </button>
                                    </form>
                                  </div>
                                </div>
                                <div class="card-body">
                                  <div class="row g-3">
                                    <div class="col-md-3">
                                      <strong>Opening Date:</strong><br>
                                      {{ $termDayRecord->opening_date->format('l, F d, Y') }}
                                    </div>
                                    <div class="col-md-3">
                                      <strong>Closing Date:</strong><br>
                                      {{ $termDayRecord->closing_date->format('l, F d, Y') }}
                                    </div>
                                    <div class="col-md-2">
                                      <strong>Duration:</strong><br>
                                      {{ $termDayRecord->opening_date->diffInDays($termDayRecord->closing_date) }} days
                                    </div>
                                    <div class="col-md-2">
                                      <strong>Expected Days:</strong><br>
                                      {{ $termDayRecord->expected_school_days ?? 'Auto' }}
                                    </div>
                                    <div class="col-md-2">
                                      <strong>Actual Days:</strong><br>
                                      <span class="badge bg-{{ $termDayRecord->calculateActualSchoolDays() >= ($termDayRecord->expected_school_days ?? 0) ? 'success' : 'warning' }}">
                                        {{ $termDayRecord->calculateActualSchoolDays() }} days
                                      </span>
                                    </div>
                                    @if($termDayRecord->notes)
                                      <div class="col-md-12">
                                        <strong>Notes:</strong> {{ $termDayRecord->notes }}
                                      </div>
                                    @endif
                                  </div>
                                </div>
                              </div>
                            @else
                              <div class="alert alert-info mb-3">
                                <strong>No term dates set.</strong> Add opening and closing dates for this term.
                              </div>
                              <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addTermDaysModal{{ $term->id }}">
                                <i class="bi bi-calendar-plus"></i> Add Term Dates
                              </button>
                            @endif

                            {{-- Add Term Days Modal --}}
                            <div class="modal fade" id="addTermDaysModal{{ $term->id }}" tabindex="-1">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title">Add Term Dates for {{ $term->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                  </div>
                                  <form action="{{ route('settings.academic.term-days.store') }}" method="POST">
                                    @csrf
                                    <div class="modal-body">
                                      <input type="hidden" name="academic_year_id" value="{{ $year->id }}">
                                      <input type="hidden" name="term_id" value="{{ $term->id }}">
                                      <div class="mb-3">
                                        <label class="form-label">Opening Date <span class="text-danger">*</span></label>
                                        <input type="date" name="opening_date" class="form-control" required>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">Closing Date <span class="text-danger">*</span></label>
                                        <input type="date" name="closing_date" class="form-control" required>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">Expected School Days</label>
                                        <input type="number" name="expected_school_days" class="form-control" min="0" placeholder="Leave empty for auto-calculation">
                                        <small class="text-muted">Will be calculated based on school days calendar if left empty</small>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                                      </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                      <button type="submit" class="btn btn-primary">Save Term Dates</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>

                            {{-- Edit Term Days Modal --}}
                            @if($termDayRecord)
                            <div class="modal fade" id="editTermDaysModal{{ $termDayRecord->id }}" tabindex="-1">
                              <div class="modal-dialog">
                                <div class="modal-content">
                                  <div class="modal-header">
                                    <h5 class="modal-title">Edit Term Dates for {{ $term->name }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                  </div>
                                  <form action="{{ route('settings.academic.term-days.update', $termDayRecord) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body">
                                      <div class="mb-3">
                                        <label class="form-label">Academic Year</label>
                                        <select name="academic_year_id" class="form-select" required>
                                          @foreach($years as $y)
                                            <option value="{{ $y->id }}" @selected($termDayRecord->academic_year_id==$y->id)>{{ $y->year }}</option>
                                          @endforeach
                                        </select>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">Term</label>
                                        <select name="term_id" class="form-select">
                                          <option value="">All Terms</option>
                                          @foreach($year->terms as $t)
                                            <option value="{{ $t->id }}" @selected($termDayRecord->term_id==$t->id)>{{ $t->name }}</option>
                                          @endforeach
                                        </select>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">Opening Date <span class="text-danger">*</span></label>
                                        <input type="date" name="opening_date" class="form-control" value="{{ $termDayRecord->opening_date->toDateString() }}" required>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">Closing Date <span class="text-danger">*</span></label>
                                        <input type="date" name="closing_date" class="form-control" value="{{ $termDayRecord->closing_date->toDateString() }}" required>
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">Expected School Days</label>
                                        <input type="number" name="expected_school_days" class="form-control" value="{{ $termDayRecord->expected_school_days }}" min="0">
                                      </div>
                                      <div class="mb-3">
                                        <label class="form-label">Notes</label>
                                        <textarea name="notes" class="form-control" rows="2">{{ $termDayRecord->notes }}</textarea>
                                      </div>
                                    </div>
                                    <div class="modal-footer">
                                      <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                      <button type="submit" class="btn btn-primary">Update Term Dates</button>
                                    </div>
                                  </form>
                                </div>
                              </div>
                            </div>
                            @endif
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                @else
                  <span class="text-muted">No terms added yet</span>
                @endif
              </td>
              <td class="text-end">
                <a href="{{ route('settings.academic.year.edit', $year) }}" class="btn btn-sm btn-primary">
                  <i class="bi bi-pencil"></i> Edit
                </a>
                <form action="{{ route('settings.academic.year.destroy', $year) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete academic year?')">
                  @csrf @method('DELETE')
                  <button type="submit" class="btn btn-sm btn-danger">
                    <i class="bi bi-trash"></i> Delete
                  </button>
                </form>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
