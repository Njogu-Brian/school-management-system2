@extends('layouts.app')

@push('styles')
    @if(request()->routeIs('senior_teacher.*'))
        @include('senior_teacher.partials.styles')
    @else
        @include('settings.partials.styles')
    @endif
@endpush

@section('content')
<div class="{{ request()->routeIs('senior_teacher.*') ? 'senior-teacher-page' : 'settings-page' }}">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory / Student Requirements</div>
                <h1>Student Requirements Tracker</h1>
                <p>See which learners have delivered items and follow up on outstanding ones.</p>
            </div>
            <a href="{{ route('inventory.student-requirements.collect') }}" class="btn btn-settings-primary">
                <i class="bi bi-clipboard-check"></i> Collect / Verify
            </a>
        </div>

        @include('partials.alerts')

        <div class="settings-card mb-3">
            <div class="card-header">
                <h5 class="mb-0">Filters</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
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
                        <button class="btn btn-settings-primary me-2" type="submit"><i class="bi bi-funnel"></i> Apply</button>
                        <a href="{{ route('inventory.student-requirements.index') }}" class="btn btn-ghost-strong"><i class="bi bi-x-circle"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Requirements</h5>
                <span class="input-chip">{{ $requirements->total() }} total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-modern mb-0 align-middle">
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
                                    <div class="fw-semibold">{{ $requirement->student ? $requirement->student->name : 'N/A' }}</div>
                                    <div class="small text-muted">{{ $requirement->student?->classroom?->name ?? '—' }}</div>
                                </td>
                                <td>
                                    <div>{{ $requirement->requirementTemplate->requirementType->name }}</div>
                                    <div class="small text-muted">{{ $requirement->requirementTemplate->brand ?? '' }}</div>
                                </td>
                                <td>
                                    @if($requirement->status === 'complete')
                                        <span class="pill-badge">Complete</span>
                                    @elseif($requirement->status === 'partial')
                                        <span class="pill-badge">Partial</span>
                                    @else
                                        <span class="pill-badge">Pending</span>
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($requirement->quantity_collected, 2) }} / {{ number_format($requirement->quantity_required, 2) }}</td>
                                <td class="text-end">
                                    {{ number_format(max($requirement->quantity_missing, 0), 2) }}
                                    <div class="small text-muted">{{ $requirement->requirementTemplate->unit }}</div>
                                </td>
                                <td>{{ optional($requirement->updated_at)->diffForHumans() }}</td>
                                <td class="text-end">
                                    <a href="{{ route('inventory.student-requirements.show', $requirement) }}" class="btn btn-sm btn-ghost-strong">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">No requirement records yet. Use “Collect / Verify” to start.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($requirements->hasPages())
                <div class="p-3">
                    {{ $requirements->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@if(request()->routeIs('senior_teacher.*'))
</div>
@endif
@endsection

