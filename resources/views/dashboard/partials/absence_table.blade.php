<div class="card shadow-sm h-100">
  <div class="card-header bg-white">
    <strong>Top Absence Alerts (last 7 days)</strong>
  </div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Student</th><th>Class</th><th>Days Absent</th><th></th></tr></thead>
      <tbody>
        @forelse($absenceAlerts as $row)
          <tr>
            <td>{{ $row->student_name }}</td>
            <td>{{ $row->classroom }}</td>
            <td class="fw-semibold">{{ $row->days_absent }}</td>
            <td><a href="{{ route('attendance.records',['student_id'=>$row->student_id]) }}" class="btn btn-outline-primary btn-sm">View</a></td>
          </tr>
        @empty
          <tr><td colspan="4" class="text-muted">No alerts.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
