@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <a href="{{ route('inventory.student-requirements.index') }}" class="text-decoration-none">
            <i class="bi bi-arrow-left"></i> Requirements Tracker
        </a>
    </div>

    @include('partials.alerts')

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Classroom</label>
                    <select name="classroom_id" class="form-select" onchange="this.form.submit()">
                        <option value="">My classes</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Academic Year</label>
                    <input type="text" class="form-control" value="{{ $currentYear->year ?? 'Not set' }}" disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Term</label>
                    <input type="text" class="form-control" value="{{ $currentTerm->name ?? 'Not set' }}" disabled>
                </div>
            </form>
        </div>
    </div>

    @if($students->isEmpty())
        <div class="alert alert-warning">
            You have no students assigned to this class. Please select a different class or contact the administrator.
        </div>
    @elseif($templates->isEmpty())
        <div class="alert alert-info">
            No requirements have been published for these classes yet. Ask an administrator to set them up first.
        </div>
    @else
        <div class="card shadow-sm">
            <div class="card-body">
                <h1 class="h4 mb-3">Collect / Verify Items</h1>
                <form method="POST" action="{{ route('inventory.student-requirements.collect.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Learner</label>
                        <select name="student_id" class="form-select" required>
                            <option value="">Select student</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>
                                    {{ $student->getNameAttribute() }} ({{ $student->classroom->name ?? '—' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Requirement</th>
                                    <th>Brand / Notes</th>
                                    <th style="width: 140px;">Collected</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $index => $template)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold">{{ $template->requirementType->name }}</div>
                                            <div class="small text-muted">
                                                Needed: {{ number_format($template->quantity_per_student, 2) }} {{ $template->unit }}
                                            </div>
                                            @if($template->leave_with_teacher)
                                                <div><span class="badge bg-success">Keep at school</span></div>
                                            @endif
                                            @if($template->is_verification_only)
                                                <div><span class="badge bg-info text-dark">Verification only</span></div>
                                            @endif
                                        </td>
                                        <td>
                                            {{ $template->brand ?? 'Any brand' }}<br>
                                            <small class="text-muted">{{ $template->notes }}</small>
                                        </td>
                                        <td>
                                            <input type="hidden" name="requirements[{{ $index }}][template_id]" value="{{ $template->id }}">
                                            <input type="number" step="0.01" min="0" class="form-control" name="requirements[{{ $index }}][quantity_collected]" value="{{ old('requirements.' . $index . '.quantity_collected', $template->quantity_per_student) }}">
                                        </td>
                                        <td>
                                            <textarea name="requirements[{{ $index }}][notes]" rows="2" class="form-control" placeholder="Optional notes">{{ old('requirements.' . $index . '.notes') }}</textarea>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button class="btn btn-primary">
                            <i class="bi bi-check2-circle"></i> Save Collection & Notify Parent
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection

