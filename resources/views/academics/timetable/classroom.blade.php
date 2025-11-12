@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Timetable - {{ $classroom->name }}</h1>
        <div class="btn-group">
            <a href="{{ route('academics.timetable.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <a href="{{ route('academics.timetable.edit', ['classroom' => $classroom->id, 'academic_year_id' => $year->id, 'term_id' => $term->id]) }}" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#duplicateModal">
                <i class="bi bi-files"></i> Duplicate
            </button>
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <strong>Academic Year:</strong> {{ $year->year }} | 
                    <strong>Term:</strong> {{ $term->name }}
                </div>
            </div>
        </div>
    </div>

    @if($conflicts && count($conflicts) > 0)
    <div class="alert alert-warning">
        <strong>Conflicts Detected:</strong>
        <ul class="mb-0">
            @foreach($conflicts as $conflict)
                <li>Teacher conflict on {{ $conflict['day'] }} Period {{ $conflict['period'] }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    @if($savedTimetable && $savedTimetable->count() > 0)
    <!-- Saved Timetable -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Time</th>
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                <th>{{ $day }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $timeSlots = [
                                ['start' => '08:00', 'end' => '08:40', 'period' => 1],
                                ['start' => '08:40', 'end' => '09:20', 'period' => 2],
                                ['start' => '09:20', 'end' => '10:00', 'period' => 3],
                                ['start' => '10:00', 'end' => '10:20', 'period' => 'Break'],
                                ['start' => '10:20', 'end' => '11:00', 'period' => 4],
                                ['start' => '11:00', 'end' => '11:40', 'period' => 5],
                                ['start' => '11:40', 'end' => '12:20', 'period' => 6],
                                ['start' => '12:20', 'end' => '13:00', 'period' => 'Lunch'],
                                ['start' => '13:00', 'end' => '13:40', 'period' => 7],
                                ['start' => '13:40', 'end' => '14:20', 'period' => 8],
                            ];
                        @endphp
                        @foreach($timeSlots as $slot)
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
                            @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'] as $day)
                                <td>
                                    @php
                                        $periodData = $savedTimetable->get($day)?->get($slot['period']);
                                    @endphp
                                    @if($periodData && $periodData->first())
                                        @php $entry = $periodData->first(); @endphp
                                        @if(in_array($slot['period'], ['Break', 'Lunch']))
                                            <span class="text-muted">{{ $slot['period'] }}</span>
                                        @else
                                            <div class="p-2 bg-light rounded">
                                                <strong>{{ $entry->subject->name }}</strong><br>
                                                <small>{{ $entry->teacher->full_name ?? 'TBA' }}</small>
                                                @if($entry->room)
                                                    <br><small class="text-muted">Room: {{ $entry->room }}</small>
                                                @endif
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-muted">Free</span>
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
    @elseif($timetable)
    <!-- Generated Timetable Preview -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="alert alert-info">
                This is a preview. Click "Edit" to customize and save.
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

    <!-- Subject Requirements -->
    @if($assignments->count() > 0)
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5 class="mb-0">Subject Requirements (Lessons per Week)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Lessons per Week</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assignments as $assignment)
                        <tr>
                            <td>{{ $assignment->subject->name }}</td>
                            <td>{{ $assignment->teacher->full_name ?? 'Not Assigned' }}</td>
                            <td>
                                <form action="{{ route('academics.classroom-subjects.update-lessons', $assignment->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('PUT')
                                    <div class="input-group input-group-sm" style="width: 120px;">
                                        <input type="number" name="lessons_per_week" class="form-control" 
                                               value="{{ $assignment->lessons_per_week ?? 5 }}" min="1" max="20">
                                        <button type="submit" class="btn btn-outline-primary">Update</button>
                                    </div>
                                </form>
                            </td>
                            <td>
                                <span class="badge bg-{{ $assignment->is_compulsory ? 'success' : 'info' }}">
                                    {{ $assignment->is_compulsory ? 'Compulsory' : 'Optional' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- Extra-Curricular Activities -->
    @if($activities && $activities->count() > 0)
    <div class="card shadow-sm mt-4">
        <div class="card-header">
            <h5 class="mb-0">Extra-Curricular Activities</h5>
        </div>
        <div class="card-body">
            @foreach($activities as $day => $dayActivities)
                <h6>{{ $day }}</h6>
                <ul>
                    @foreach($dayActivities as $activity)
                        <li>
                            <strong>{{ $activity->name }}</strong> 
                            ({{ $activity->start_time->format('H:i') }} - {{ $activity->end_time->format('H:i') }})
                            <span class="badge bg-secondary">{{ ucfirst($activity->type) }}</span>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
    @endif
</div>

<!-- Duplicate Modal -->
<div class="modal fade" id="duplicateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Duplicate Timetable</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('academics.timetable.duplicate') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <input type="hidden" name="source_classroom_id" value="{{ $classroom->id }}">
                    <input type="hidden" name="academic_year_id" value="{{ $year->id }}">
                    <input type="hidden" name="term_id" value="{{ $term->id }}">
                    
                    <div class="mb-3">
                        <label class="form-label">Select Classrooms to Duplicate To</label>
                        <select name="target_classroom_ids[]" class="form-select" multiple required>
                            @foreach(\App\Models\Academics\Classroom::where('id', '!=', $classroom->id)->orderBy('name')->get() as $targetClassroom)
                                <option value="{{ $targetClassroom->id }}">{{ $targetClassroom->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">Hold Ctrl/Cmd to select multiple</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Duplicate</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection


