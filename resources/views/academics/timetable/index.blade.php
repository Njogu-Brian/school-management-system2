@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Timetable Management</h1>
    </div>

    <!-- Selection Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Classroom</label>
                    <select name="classroom_id" class="form-select" required>
                        <option value="">Select Classroom</option>
                        @foreach($classrooms as $classroom)
                            <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                {{ $classroom->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Academic Year</label>
                    <select name="academic_year_id" class="form-select" required>
                        @foreach($years as $year)
                            <option value="{{ $year->id }}" {{ ($selectedYear && $selectedYear->id == $year->id) || (!$selectedYear && ($currentYearId ?? null) == $year->id) || (!$selectedYear && !$currentYearId && $loop->first) ? 'selected' : '' }}>
                                {{ $year->year }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Term</label>
                    <select name="term_id" class="form-select" required>
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}" {{ ($selectedTerm && $selectedTerm->id == $term->id) || (!$selectedTerm && ($currentTermId ?? null) == $term->id) || (!$selectedTerm && !$currentTermId && $loop->first) ? 'selected' : '' }}>
                                {{ $term->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Generate Timetable</button>
                </div>
            </form>
        </div>
    </div>

    @if($timetable)
    <!-- Timetable Display -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Timetable for {{ $timetable['classroom']->name }}</h5>
                <button class="btn btn-success" onclick="saveTimetable()">Save Timetable</button>
            </div>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            @foreach($timetable['days'] as $day)
                                <th>{{ $day }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($timetable['time_slots'] as $slot)
                        <tr>
                            <td class="fw-bold">
                                @if(in_array($slot['period'], ['Break', 'Lunch']))
                                    {{ $slot['period'] }}<br>
                                    <small>{{ $slot['start'] }} - {{ $slot['end'] }}</small>
                                @else
                                    Period {{ $slot['period'] }}<br>
                                    <small>{{ $slot['start'] }} - {{ $slot['end'] }}</small>
                                @endif
                            </td>
                            @foreach($timetable['days'] as $day)
                                <td>
                                    @if(isset($timetable['timetable'][$day][$slot['period']]))
                                        @php $period = $timetable['timetable'][$day][$slot['period']]; @endphp
                                        @if(in_array($slot['period'], ['Break', 'Lunch']))
                                            <span class="text-muted">{{ $slot['period'] }}</span>
                                        @elseif($period['subject'])
                                            <div class="p-2 bg-light rounded">
                                                <strong>{{ $period['subject']->name }}</strong><br>
                                                <small>{{ $period['teacher']->full_name ?? 'TBA' }}</small>
                                            </div>
                                        @else
                                            <span class="text-muted">Free</span>
                                        @endif
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<script>
function saveTimetable() {
    // Redirect to edit page to save timetable
    const url = new URL(window.location.href);
    const classroomId = url.searchParams.get('classroom_id');
    const yearId = url.searchParams.get('academic_year_id');
    const termId = url.searchParams.get('term_id');
    
    if (classroomId && yearId && termId) {
        window.location.href = `/academics/timetable/classroom/${classroomId}/edit?academic_year_id=${yearId}&term_id=${termId}`;
    } else {
        alert('Please select classroom, academic year, and term first.');
    }
}
</script>
@endsection

