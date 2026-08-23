<div class="dash-card card h-100">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Recent Admissions</strong>
    @if(Route::has('students.index'))
      <a class="small dash-btn-ghost" href="{{ route('students.index') }}">View all</a>
    @endif
  </div>
  <div class="card-body p-0">
    <div class="table-responsive erp-invoice-table">
      <table class="table table-sm mb-0 dash-table">
        <thead>
          <tr>
            <th>Student</th>
            <th>Admission No.</th>
            <th>Class</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse($recentAdmissions ?? [] as $row)
            <tr>
              <td>
                <a href="{{ route('students.show', $row->id) }}">{{ $row->name }}</a>
              </td>
              <td>{{ $row->admission_number ?: '—' }}</td>
              <td>{{ $row->classroom ?: '—' }}</td>
              <td>{{ $row->status ? ucfirst($row->status) : '—' }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="text-muted">No recent admissions.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    <div class="erp-invoice-cards">
      @forelse($recentAdmissions ?? [] as $row)
        <a class="erp-invoice-card text-decoration-none text-reset" href="{{ route('students.show', $row->id) }}">
          <div class="fw-semibold">{{ $row->name }}</div>
          <div class="small dash-muted">{{ $row->admission_number ?: '—' }} · {{ $row->classroom ?: 'No class' }}</div>
        </a>
      @empty
        <div class="erp-empty"><i class="bi bi-person-plus"></i>No recent admissions.</div>
      @endforelse
    </div>
  </div>
</div>
