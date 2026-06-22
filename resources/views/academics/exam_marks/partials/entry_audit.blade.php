@if(!empty($entryAudit))
  <div class="settings-card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
        <div>
          <h2 class="h6 mb-1">Submission &amp; activity</h2>
          <p class="small text-muted mb-0">Who saved or submitted marks and when.</p>
        </div>
        @if(!empty($entryAudit['exam']['marking_submitted_at']))
          <span class="badge bg-success">Submitted for review</span>
        @else
          <span class="badge bg-secondary">Draft in progress</span>
        @endif
      </div>

      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <div class="small text-muted">Exam status</div>
          <div class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $entryAudit['exam']['status'] ?? '—') }}</div>
        </div>
        <div class="col-md-4">
          <div class="small text-muted">Submitted for review</div>
          <div class="fw-semibold">
            @if(!empty($entryAudit['exam']['marking_submitted_at']))
              {{ \Carbon\Carbon::parse($entryAudit['exam']['marking_submitted_at'])->format('d M Y H:i') }}
            @else
              Not yet submitted
            @endif
          </div>
        </div>
        <div class="col-md-4">
          <div class="small text-muted">Submitted by</div>
          <div class="fw-semibold">{{ $entryAudit['exam']['marking_submitted_by'] ?? '—' }}</div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <span class="badge bg-light text-dark border">Draft: {{ $entryAudit['counts']['draft'] ?? 0 }}</span>
        <span class="badge bg-light text-dark border">Submitted: {{ $entryAudit['counts']['submitted'] ?? 0 }}</span>
        <span class="badge bg-light text-dark border">Absent: {{ $entryAudit['counts']['absent'] ?? 0 }}</span>
        <span class="badge bg-light text-dark border">Total rows: {{ $entryAudit['counts']['total_marks'] ?? 0 }}</span>
      </div>

      @if(!empty($entryAudit['recent_activity']) && count($entryAudit['recent_activity']) > 0)
        <div class="table-responsive">
          <table class="table table-sm table-modern mb-0">
            <thead class="table-light">
              <tr>
                <th>Student</th>
                <th>Result</th>
                <th>Status</th>
                <th>Last update</th>
                <th>By</th>
              </tr>
            </thead>
            <tbody>
              @foreach($entryAudit['recent_activity'] as $row)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $row['student_name'] ?? '—' }}</div>
                    <div class="small text-muted">{{ $row['admission_number'] ?? '' }}</div>
                  </td>
                  <td>{{ $row['is_absent'] ? 'ABS' : ($row['score'] ?? '—') }}</td>
                  <td class="text-capitalize">{{ $row['status'] ?? '—' }}</td>
                  <td class="small">
                    @if(!empty($row['last_action_at']))
                      {{ \Carbon\Carbon::parse($row['last_action_at'])->format('d M Y H:i') }}
                    @else
                      —
                    @endif
                  </td>
                  <td class="small">{{ $row['last_action_by'] ?? '—' }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <p class="small text-muted mb-0">No mark activity recorded yet.</p>
      @endif
    </div>
  </div>
@endif
