<div class="card mt-3">
    <div class="card-header">Attendance Summary ({{ $selectedDate }})</div>
    <div class="card-body">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Class</th>
                    <th>Present</th>
                    <th>Absent</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendanceSummary as $class => $summary)
                    <tr>
                        <td>{{ $class }}</td>
                        <td>{{ $summary['present'] }}</td>
                        <td>{{ $summary['absent'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
