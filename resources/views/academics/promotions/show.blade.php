@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Promote Students: {{ $classroom->name }}</h2>
            <small class="text-muted">
                @if($classroom->is_alumni)
                    Mark students as Alumni
                @elseif($classroom->nextClass)
                    Promote to: <strong>{{ $classroom->nextClass->name }}</strong>
                @else
                    <span class="text-danger">No next class mapped. Please set next class in class settings.</span>
                @endif
            </small>
        </div>
        <a href="{{ route('academics.promotions.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    @if(!$classroom->nextClass && !$classroom->is_alumni)
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> This class does not have a next class mapped. 
            <a href="{{ route('academics.classrooms.edit', $classroom) }}">Set next class</a> before promoting students.
        </div>
    @endif

    @php
        // Check if this class has already been promoted in the current academic year
        $alreadyPromoted = false;
        if ($currentYear) {
            $alreadyPromoted = \App\Models\StudentAcademicHistory::where('classroom_id', $classroom->id)
                ->where('academic_year_id', $currentYear->id)
                ->where('promotion_status', 'promoted')
                ->exists();
        }
    @endphp

    @if($alreadyPromoted)
        <div class="alert alert-warning">
            <i class="bi bi-info-circle"></i> This class has already been promoted in the current academic year ({{ $currentYear->year }}). 
            Each class can only be promoted once per academic year.
        </div>
    @endif

    <form action="{{ route('academics.promotions.promote', $classroom) }}" method="POST" id="promotionForm">
        @csrf
        
        <div class="row mb-4">
            <div class="col-md-4">
                <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                <select name="academic_year_id" class="form-select" required>
                    <option value="">-- Select Year --</option>
                    @foreach(\App\Models\AcademicYear::orderBy('year', 'desc')->get() as $year)
                        <option value="{{ $year->id }}" @selected($currentYear && $currentYear->id == $year->id)>
                            {{ $year->year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Term <span class="text-danger">*</span></label>
                <select name="term_id" class="form-select" required>
                    <option value="">-- Select Term --</option>
                    @foreach(\App\Models\Term::orderBy('name')->get() as $term)
                        <option value="{{ $term->id }}" @selected($currentTerm && $currentTerm->id == $term->id)>
                            {{ $term->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Promotion Date <span class="text-danger">*</span></label>
                <input type="date" name="promotion_date" class="form-control" value="{{ date('Y-m-d') }}" required>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Select Students to Promote</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">Select All</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">Deselect All</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th style="width:50px;">
                                    <input type="checkbox" id="selectAllCheckbox" onchange="toggleAll()">
                                </th>
                                <th>Admission #</th>
                                <th>Name</th>
                                <th>Stream</th>
                                <th>Current Class</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td>
                                        <input type="checkbox" name="student_ids[]" value="{{ $student->id }}" class="student-checkbox">
                                    </td>
                                    <td class="fw-semibold">{{ $student->admission_number }}</td>
                                    <td>{{ $student->full_name }}</td>
                                    <td>
                                        @if($student->stream)
                                            <span class="badge bg-info">{{ $student->stream->name }}</span>
                                        @else
                                            <span class="text-muted">No stream</span>
                                        @endif
                                    </td>
                                    <td>{{ $classroom->name }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">No students in this class.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Notes (Optional)</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Add any notes about this promotion..."></textarea>
        </div>

        <div class="d-flex justify-content-between">
            <a href="{{ route('academics.promotions.index') }}" class="btn btn-secondary">Cancel</a>
            <div>
                @if($students->count() > 0 && ($classroom->nextClass || $classroom->is_alumni) && !$alreadyPromoted)
                    <button type="button" class="btn btn-success me-2" onclick="promoteAll()">
                        <i class="bi bi-arrow-up-circle-fill"></i> Promote All Students
                    </button>
                @endif
                <button type="submit" class="btn btn-primary" @if(!$classroom->nextClass && !$classroom->is_alumni || $alreadyPromoted) disabled @endif>
                    <i class="bi bi-arrow-up-circle"></i> 
                    @if($classroom->is_alumni)
                        Mark as Alumni
                    @else
                        Promote Selected
                    @endif
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function toggleAll() {
    const selectAll = document.getElementById('selectAllCheckbox').checked;
    document.querySelectorAll('.student-checkbox').forEach(cb => {
        cb.checked = selectAll;
    });
}

function selectAll() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckbox').checked = true;
}

function deselectAll() {
    document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
}

function promoteAll() {
    selectAll();
    const action = @if($classroom->is_alumni) 'mark as alumni' @else 'promote' @endif;
    if (confirm(`Are you sure you want to ${action} ALL ${document.querySelectorAll('.student-checkbox').length} student(s)?`)) {
        document.getElementById('promotionForm').submit();
    } else {
        // Keep all selected but don't submit
    }
}

document.getElementById('promotionForm').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.student-checkbox:checked').length;
    if (checked === 0) {
        e.preventDefault();
        alert('Please select at least one student to promote.');
        return false;
    }
    
    const action = @if($classroom->is_alumni) 'mark as alumni' @else 'promote' @endif;
    if (!confirm(`Are you sure you want to ${action} ${checked} student(s)?`)) {
        e.preventDefault();
        return false;
    }
});
</script>
@endsection

