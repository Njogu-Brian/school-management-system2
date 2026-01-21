@if ($filtersApplied && $students->isNotEmpty())
    <div class="dash-card card mt-3">
        <div class="card-header">Filtered Students</div>
        <div class="card-body">
            <table class="table table-bordered dash-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Class</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($students as $student)
                        <tr>
                            <!-- Display Full Name -->
                            <td>{{ $student->full_name }}</td>
                            
                            <!-- Display Class Name -->
                            <td>{{ $student->classrooms->name ?? 'N/A' }}</td>
                            
                            <!-- Display Attendance Status -->
                            <td>
                                @if ($student->attendances->where('date', $selectedDate)->where('is_present', 1)->count())
                                    <span class="badge bg-success">Present</span>
                                @elseif ($student->attendances->where('date', $selectedDate)->where('is_present', 0)->count())
                                    <span class="badge bg-danger">Absent</span>
                                @else
                                    <span class="badge bg-warning">Not Marked</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@elseif ($filtersApplied)
    <p>No students found for the selected filters.</p>
@endif
