<div class="dash-card card h-100">
  <div class="card-header">
    <strong>Top Absence Alerts (last 7 days)</strong>
  </div>
  <div class="table-responsive erp-invoice-table">
    <table class="table table-sm mb-0 dash-table">
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
  <div class="erp-invoice-cards">
    @forelse($absenceAlerts as $row)
      <div class="erp-invoice-card">
        <div class="fw-semibold">{{ $row->student_name }}</div>
        <div class="small dash-muted">{{ $row->classroom }} · {{ $row->days_absent }} days absent</div>
        <a href="{{ route('attendance.records',['student_id'=>$row->student_id]) }}" class="btn btn-outline-primary btn-sm mt-2">View</a>
      </div>
    @empty
      <div class="erp-empty"><i class="bi bi-clipboard-check"></i>No attendance alerts in the last 7 days.</div>
    @endforelse
  </div>
</div>
