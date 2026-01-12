{{-- resources/views/senior_teacher/dashboard.blade.php --}}
@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page">
  <div class="dashboard-shell">
    <div class="dash-hero d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <span class="crumb">Dashboard</span>
        <h2 class="mb-1">Senior Teacher Dashboard</h2>
        <p class="mb-0">Welcome back, {{ auth()->user()->name }}!</p>
      </div>
      <div>
        <span class="dash-chip">{{ now()->format('l, F j, Y') }}</span>
      </div>
    </div>

    {{-- Quick Stats --}}
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <div class="text-muted small mb-1">Supervised Classrooms</div>
                <div class="fs-3 fw-bold text-primary">{{ $kpis['supervised_classrooms'] }}</div>
              </div>
              <div class="ms-3">
                <span class="badge rounded-circle p-3 bg-primary bg-opacity-10">
                  <i class="bi bi-building fs-4 text-primary"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <div class="text-muted small mb-1">Supervised Staff</div>
                <div class="fs-3 fw-bold text-success">{{ $kpis['supervised_staff'] }}</div>
              </div>
              <div class="ms-3">
                <span class="badge rounded-circle p-3 bg-success bg-opacity-10">
                  <i class="bi bi-person-badge fs-4 text-success"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <div class="text-muted small mb-1">Total Students</div>
                <div class="fs-3 fw-bold text-info">{{ number_format($kpis['total_students']) }}</div>
              </div>
              <div class="ms-3">
                <span class="badge rounded-circle p-3 bg-info bg-opacity-10">
                  <i class="bi bi-people fs-4 text-info"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100 shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex align-items-center">
              <div class="flex-grow-1">
                <div class="text-muted small mb-1">Attendance Rate</div>
                <div class="fs-3 fw-bold text-warning">{{ $kpis['attendance_rate'] }}%</div>
              </div>
              <div class="ms-3">
                <span class="badge rounded-circle p-3 bg-warning bg-opacity-10">
                  <i class="bi bi-calendar-check fs-4 text-warning"></i>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Main Content Row --}}
    <div class="row g-4">
      {{-- Left Column --}}
      <div class="col-lg-8">
        {{-- Supervised Classrooms --}}
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-building me-2"></i>Supervised Classrooms</h5>
            <a href="{{ route('senior_teacher.supervised_classrooms') }}" class="btn btn-sm btn-outline-primary">
              View All
            </a>
          </div>
          <div class="card-body">
            @if($supervisedClassrooms->isEmpty())
              <div class="text-center py-4">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-2">No classrooms assigned for supervision yet.</p>
              </div>
            @else
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Class</th>
                      <th>Students</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($supervisedClassrooms->take(5) as $classroom)
                      <tr>
                        <td>
                          <strong>{{ $classroom->name }}</strong>
                        </td>
                        <td>
                          <span class="badge bg-info">{{ $classroom->students_count }} students</span>
                        </td>
                        <td>
                          <div class="btn-group btn-group-sm">
                            <a href="{{ route('senior_teacher.students.index') }}?classroom_id={{ $classroom->id }}" 
                               class="btn btn-outline-primary" title="View Students">
                              <i class="bi bi-people"></i>
                            </a>
                            <a href="{{ route('attendance.records') }}?classroom_id={{ $classroom->id }}" 
                               class="btn btn-outline-success" title="View Attendance">
                              <i class="bi bi-calendar-check"></i>
                            </a>
                          </div>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
        </div>

        {{-- Supervised Staff --}}
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-person-badge me-2"></i>Supervised Staff</h5>
            <a href="{{ route('senior_teacher.supervised_staff') }}" class="btn btn-sm btn-outline-primary">
              View All
            </a>
          </div>
          <div class="card-body">
            @if($supervisedStaff->isEmpty())
              <div class="text-center py-4">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="text-muted mt-2">No staff assigned for supervision yet.</p>
              </div>
            @else
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Staff Member</th>
                      <th>Position</th>
                      <th>Email</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($supervisedStaff->take(5) as $staff)
                      <tr>
                        <td>
                          <strong>{{ $staff->full_name }}</strong>
                        </td>
                        <td>
                          <span class="badge bg-light text-dark">{{ $staff->position->name ?? 'N/A' }}</span>
                        </td>
                        <td>
                          <small>{{ $staff->user->email ?? 'N/A' }}</small>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @endif
          </div>
        </div>

        {{-- Attendance Overview --}}
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white border-bottom">
            <h5 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Today's Attendance</h5>
          </div>
          <div class="card-body">
            <div class="row text-center g-3">
              <div class="col-md-3">
                <div class="p-3">
                  <div class="fs-4 fw-bold text-success">{{ $todayAttendance['present'] }}</div>
                  <div class="text-muted small">Present</div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3">
                  <div class="fs-4 fw-bold text-danger">{{ $todayAttendance['absent'] }}</div>
                  <div class="text-muted small">Absent</div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3">
                  <div class="fs-4 fw-bold text-warning">{{ $todayAttendance['late'] }}</div>
                  <div class="text-muted small">Late</div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="p-3">
                  <div class="fs-4 fw-bold text-info">{{ $todayAttendance['total'] }}</div>
                  <div class="text-muted small">Total Active</div>
                </div>
              </div>
            </div>
            
            {{-- Attendance Trends Chart (Last 7 Days) --}}
            @if(!empty($attendanceTrends))
              <hr>
              <h6 class="mb-3">Last 7 Days Trend</h6>
              <div style="height: 200px;">
                <canvas id="attendanceTrendChart"></canvas>
              </div>
            @endif
          </div>
        </div>

        {{-- Recent Student Behaviours --}}
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2"></i>Recent Student Behaviours</h5>
            <a href="{{ route('academics.student-behaviours.index') }}" class="btn btn-sm btn-outline-primary">
              View All
            </a>
          </div>
          <div class="card-body">
            @if($recentBehaviours->isEmpty())
              <div class="text-center py-4">
                <i class="bi bi-check-circle fs-1 text-success"></i>
                <p class="text-muted mt-2">No recent behaviour incidents.</p>
              </div>
            @else
              <div class="list-group list-group-flush">
                @foreach($recentBehaviours->take(5) as $behaviour)
                  <div class="list-group-item border-0 px-0">
                    <div class="d-flex justify-content-between align-items-start">
                      <div class="flex-grow-1">
                        <h6 class="mb-1">
                          {{ $behaviour->student->full_name }}
                          <span class="badge {{ $behaviour->type === 'Positive' ? 'bg-success' : 'bg-danger' }}">
                            {{ $behaviour->type }}
                          </span>
                        </h6>
                        <p class="mb-1 text-muted small">
                          {{ $behaviour->behaviourCategory->name ?? 'N/A' }} - {{ Str::limit($behaviour->description, 60) }}
                        </p>
                        <small class="text-muted">
                          {{ $behaviour->incident_date->format('M j, Y') }} by {{ $behaviour->staff->full_name ?? 'N/A' }}
                        </small>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>
      </div>

      {{-- Right Column --}}
      <div class="col-lg-4">
        {{-- Fee Balances Summary --}}
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white border-bottom">
            <h5 class="mb-0"><i class="bi bi-currency-exchange me-2"></i>Fee Balances</h5>
          </div>
          <div class="card-body">
            <div class="mb-3">
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted small">Total Invoiced</span>
                <span class="fw-bold">KES {{ number_format($feeBalances['total_invoiced'], 2) }}</span>
              </div>
              <div class="d-flex justify-content-between mb-1">
                <span class="text-muted small">Total Paid</span>
                <span class="text-success fw-bold">KES {{ number_format($feeBalances['total_paid'], 2) }}</span>
              </div>
              <hr>
              <div class="d-flex justify-content-between">
                <span class="text-muted">Outstanding Balance</span>
                <span class="text-danger fw-bold">KES {{ number_format($feeBalances['total_balance'], 2) }}</span>
              </div>
            </div>
            <div class="alert alert-info mb-0">
              <small><i class="bi bi-info-circle me-1"></i>{{ $feeBalances['students_with_balance'] }} students with balances</small>
            </div>
            <div class="mt-3 text-center">
              <a href="{{ route('senior_teacher.fee_balances') }}" class="btn btn-outline-primary btn-sm w-100">
                <i class="bi bi-list me-1"></i>View Detailed Balances
              </a>
            </div>
          </div>
        </div>

        {{-- Upcoming Exams --}}
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white border-bottom">
            <h5 class="mb-0"><i class="bi bi-journal-check me-2"></i>Upcoming Exams</h5>
          </div>
          <div class="card-body">
            @if($upcomingExams->isEmpty())
              <div class="text-center py-4">
                <i class="bi bi-calendar-x fs-1 text-muted"></i>
                <p class="text-muted mt-2 mb-0">No upcoming exams scheduled.</p>
              </div>
            @else
              <div class="list-group list-group-flush">
                @foreach($upcomingExams as $exam)
                  <div class="list-group-item border-0 px-0">
                    <h6 class="mb-1">{{ $exam->name }}</h6>
                    <small class="text-muted">
                      <i class="bi bi-calendar me-1"></i>{{ \Carbon\Carbon::parse($exam->start_date)->format('M j, Y') }}
                    </small>
                  </div>
                @endforeach
              </div>
            @endif
          </div>
        </div>

        {{-- Pending Homework --}}
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white border-bottom">
            <h5 class="mb-0"><i class="bi bi-journal me-2"></i>Pending Homework</h5>
          </div>
          <div class="card-body">
            @if($pendingHomework->isEmpty())
              <div class="text-center py-4">
                <i class="bi bi-check-circle fs-1 text-success"></i>
                <p class="text-muted mt-2 mb-0">No pending homework.</p>
              </div>
            @else
              <div class="list-group list-group-flush">
                @foreach($pendingHomework->take(5) as $homework)
                  <div class="list-group-item border-0 px-0">
                    <h6 class="mb-1">{{ Str::limit($homework->title, 30) }}</h6>
                    <small class="text-muted">
                      {{ $homework->classroom->name ?? 'N/A' }} - {{ $homework->subject->name ?? 'N/A' }}<br>
                      Due: {{ \Carbon\Carbon::parse($homework->due_date)->format('M j, Y') }}
                    </small>
                  </div>
                @endforeach
              </div>
              <div class="mt-3 text-center">
                <a href="{{ route('academics.homework.index') }}" class="btn btn-outline-primary btn-sm w-100">
                  View All Homework
                </a>
              </div>
            @endif
          </div>
        </div>

        {{-- Announcements --}}
        @if(isset($announcements) && $announcements->isNotEmpty())
          @include('dashboard.partials.announcements', ['announcements' => $announcements])
        @endif

        {{-- Quick Actions --}}
        <div class="card shadow-sm border-0">
          <div class="card-header bg-white border-bottom">
            <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
          </div>
          <div class="card-body">
            <div class="d-grid gap-2">
              <a href="{{ route('senior_teacher.students.index') }}" class="btn btn-primary">
                <i class="bi bi-people me-2"></i>View Students
              </a>
              <a href="{{ route('attendance.mark.form') }}" class="btn btn-success">
                <i class="bi bi-calendar-check me-2"></i>Mark Attendance
              </a>
              <a href="{{ route('academics.homework.create') }}" class="btn btn-info">
                <i class="bi bi-journal-plus me-2"></i>Create Homework
              </a>
              <a href="{{ route('academics.student-behaviours.create') }}" class="btn btn-warning">
                <i class="bi bi-exclamation-triangle me-2"></i>Record Behaviour
              </a>
              <a href="{{ route('senior_teacher.timetable.index') }}" class="btn btn-secondary">
                <i class="bi bi-calendar-week me-2"></i>View Timetable
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
@if(!empty($attendanceTrends))
// Attendance Trend Chart
const attendanceTrendCtx = document.getElementById('attendanceTrendChart');
if (attendanceTrendCtx) {
  new Chart(attendanceTrendCtx, {
    type: 'line',
    data: {
      labels: {!! json_encode(array_column($attendanceTrends, 'date')) !!},
      datasets: [
        {
          label: 'Present',
          data: {!! json_encode(array_column($attendanceTrends, 'present')) !!},
          borderColor: '#28a745',
          backgroundColor: 'rgba(40, 167, 69, 0.1)',
          tension: 0.4
        },
        {
          label: 'Absent',
          data: {!! json_encode(array_column($attendanceTrends, 'absent')) !!},
          borderColor: '#dc3545',
          backgroundColor: 'rgba(220, 53, 69, 0.1)',
          tension: 0.4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          position: 'bottom',
        }
      },
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
}
@endif
</script>
@endpush
@endsection

