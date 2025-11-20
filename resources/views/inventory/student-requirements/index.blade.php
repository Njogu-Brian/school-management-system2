@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Student Requirements Tracker</h1>
            <p class="text-muted mb-0">See which learners have delivered stationery/food items and follow up on outstanding ones.</p>
        </div>
        <a href="{{ route('inventory.student-requirements.collect') }}" class="btn btn-primary">
            <i class="bi bi-clipboard-check"></i> Collect / Verify
        </a>
    </div>

    @include('partials.alerts')

    <form method="GET" class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Classroom</label>
                <select name="classroom_id" class="form-select">
                    <option value="">All</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Any</option>
                    @foreach(['pending' => 'Pending', 'partial' => 'Partial', 'complete' => 'Complete'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') == $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Academic Year</label>
                <select name="academic_year_id" class="form-select">
                    <option value="">All</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>
                            {{ $year->year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Term</label>
                <select name="term_id" class="form-select">
                    <option value="">All</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" @selected(request('term_id') == $term->id)>
                            {{ $term->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 text-end">
                <button class="btn btn-secondary me-2" type="submit">Apply Filters</button>
                <a href="{{ route('inventory.student-requirements.index') }}" class="btn btn-light">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Requirement</th>
                        <th>Status</th>
                        <th class="text-end">Collected</th>
                        <th class="text-end">Missing</th>
                        <th>Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requirements as $requirement)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $requirement->student->getNameAttribute() }}</div>
                                <div class="small text-muted">{{ $requirement->student->classroom->name ?? '—' }}</div>
                            </td>
                            <td>
                                <div>{{ $requirement->requirementTemplate->requirementType->name }}</div>
                                <div class="small text-muted">{{ $requirement->requirementTemplate->brand ?? '' }}</div>
                            </td>
                            <td>
                                @if($requirement->status === 'complete')
                                    <span class="badge bg-success">Complete</span>
                                @elseif($requirement->status === 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format($requirement->quantity_collected, 2) }} / {{ number_format($requirement->quantity_required, 2) }}</td>
                            <td class="text-end">
                                {{ number_format(max($requirement->quantity_missing, 0), 2) }}
                                <div class="small text-muted">{{ $requirement->requirementTemplate->unit }}</div>
                            </td>
                            <td>{{ optional($requirement->updated_at)->diffForHumans() }}</td>
                            <td class="text-end">
                                <a href="{{ route('inventory.student-requirements.show', $requirement) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No requirement records yet. Teachers should start with the “Collect / Verify” button.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requirements->hasPages())
            <div class="card-footer">
                {{ $requirements->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

