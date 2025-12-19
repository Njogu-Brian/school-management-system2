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
                <h1>Class Requirements</h1>
                <p>Define stationery/food items per class and term for easy collection or verification.</p>
            </div>
            <a href="{{ route('inventory.requirement-templates.create') }}" class="btn btn-settings-primary">
                <i class="bi bi-plus-lg"></i> New Requirement Set
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
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button class="btn btn-settings-primary w-100" type="submit"><i class="bi bi-funnel"></i> Apply</button>
                        <a href="{{ route('inventory.requirement-templates.index') }}" class="btn btn-ghost-strong"><i class="bi bi-x-circle"></i></a>
                    </div>
                </form>
            </div>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Requirement Templates</h5>
                <span class="input-chip">{{ $templates->total() }} total</span>
            </div>
            <div class="table-responsive">
                <table class="table table-modern mb-0 align-middle">
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
                                <td>{{ number_format($template->quantity_per_student, 2) }} {{ $template->unit }}</td>
                                <td class="text-capitalize">{{ $template->student_type }}</td>
                                <td>
                                    @if($template->leave_with_teacher)
                                        <span class="pill-badge">Keep at school</span>
                                    @endif
                                    @if($template->is_verification_only)
                                        <span class="pill-badge">Verification only</span>
                                    @endif
                                    @unless($template->leave_with_teacher || $template->is_verification_only)
                                        <span class="pill-badge">Return to student</span>
                                    @endunless
                                </td>
                                <td>
                                    <span class="pill-badge">{{ $template->is_active ? 'Active' : 'Archived' }}</span>
                                </td>
                                <td class="text-end d-flex justify-content-end gap-2">
                                    <a href="{{ route('inventory.requirement-templates.edit', $template) }}" class="btn btn-sm btn-ghost-strong">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form action="{{ route('inventory.requirement-templates.destroy', $template) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this template?');">
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
                                <td colspan="8" class="text-center text-muted py-4">No requirement templates configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($templates->hasPages())
                <div class="p-3">
                    {{ $templates->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

