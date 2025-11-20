@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Class Requirements</h1>
            <p class="text-muted mb-0">Define stationery / food items per class and term so teachers can collect or verify easily.</p>
        </div>
        <a href="{{ route('inventory.requirement-templates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Requirement Set
        </a>
    </div>

    @include('partials.alerts')

    <form method="GET" class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Academic Year</label>
                <select name="academic_year_id" class="form-select">
                    <option value="">All</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Term</label>
                <select name="term_id" class="form-select">
                    <option value="">All</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" @selected(request('term_id') == $term->id)>{{ $term->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Classroom</label>
                <select name="classroom_id" class="form-select">
                    <option value="">All</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>{{ $classroom->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-secondary me-2" type="submit">Apply</button>
                <a href="{{ route('inventory.requirement-templates.index') }}" class="btn btn-light">Reset</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Requirement</th>
                        <th>Class</th>
                        <th>Brand / Notes</th>
                        <th>Qty / Student</th>
                        <th>Student Type</th>
                        <th>Handling</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($templates as $template)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $template->requirementType->name }}</div>
                                <div class="small text-muted">{{ $template->requirementType->category }}</div>
                            </td>
                            <td>{{ $template->classroom->name ?? 'All Classes' }}</td>
                            <td>
                                <div>{{ $template->brand ?? 'Any brand' }}</div>
                                <div class="small text-muted">{{ $template->notes }}</div>
                            </td>
                            <td>
                                {{ number_format($template->quantity_per_student, 2) }} {{ $template->unit }}
                            </td>
                            <td class="text-capitalize">{{ $template->student_type }}</td>
                            <td>
                                @if($template->leave_with_teacher)
                                    <span class="badge bg-success">Keep at school</span>
                                @endif
                                @if($template->is_verification_only)
                                    <span class="badge bg-info text-dark">Verification only</span>
                                @endif
                                @unless($template->leave_with_teacher || $template->is_verification_only)
                                    <span class="badge bg-secondary">Return to student</span>
                                @endunless
                            </td>
                            <td>
                                @if($template->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Archived</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('inventory.requirement-templates.edit', $template) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form action="{{ route('inventory.requirement-templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No requirement templates configured yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($templates->hasPages())
            <div class="card-footer">
                {{ $templates->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection

