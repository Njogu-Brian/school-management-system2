@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">Inventory / Class Requirements</div>
                <h1>Requirement Assignments</h1>
                <p>Assign requirement items to specific term/class and student category, with quantities per scope.</p>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('inventory.requirement-templates.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-file-earmark-text"></i> Items
                </a>
                <a href="{{ route('inventory.requirement-template-assignments.create') }}" class="btn btn-settings-primary">
                    <i class="bi bi-plus-circle"></i> New Assignment
                </a>
            </div>
        </div>

        <div class="settings-card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Requirement Type</label>
                        <select name="requirement_type_id" class="form-select">
                            <option value="">All</option>
                            @foreach($requirementTypes as $type)
                                <option value="{{ $type->id }}" @selected(request('requirement_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Academic Year</label>
                        <select name="academic_year_id" class="form-select">
                            <option value="">Any</option>
                            @foreach($academicYears as $year)
                                <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>{{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Term</label>
                        <select name="term_id" class="form-select">
                            <option value="">Any</option>
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
                    <div class="col-md-2">
                        <label class="form-label">Student Category</label>
                        <select name="student_type" class="form-select">
                            <option value="">All</option>
                            @foreach(['new' => 'New', 'existing' => 'Existing', 'both' => 'All'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('student_type') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('inventory.requirement-template-assignments.index') }}" class="btn btn-ghost-strong">Reset</a>
                        <button class="btn btn-settings-primary" type="submit"><i class="bi bi-funnel"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Scope</th>
                                <th>Qty</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignments as $assignment)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $assignment->template?->requirementType?->name ?? 'Requirement' }}</div>
                                        <div class="small text-muted">{{ $assignment->brand ?? 'Any brand' }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $assignment->classroom?->name ?? 'All Classes' }}</div>
                                        <div class="small text-muted">
                                            {{ $assignment->academicYear?->year ?? 'Any year' }} • {{ $assignment->term?->name ?? 'Any term' }}
                                        </div>
                                    </td>
                                    <td>{{ number_format($assignment->quantity_per_student, 2) }} {{ $assignment->unit }}</td>
                                    <td class="text-capitalize">{{ $assignment->student_type }}</td>
                                    <td><span class="pill-badge">{{ $assignment->is_active ? 'Active' : 'Archived' }}</span></td>
                                    <td class="text-end d-flex justify-content-end gap-2">
                                        <a href="{{ route('inventory.requirement-template-assignments.edit', $assignment) }}" class="btn btn-sm btn-ghost-strong">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('inventory.requirement-template-assignments.destroy', $assignment) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this assignment?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-ghost-strong text-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No assignments configured yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($assignments->hasPages())
                <div class="p-3">
                    {{ $assignments->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

