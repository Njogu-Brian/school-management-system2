@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Analytics</div>
                <h1 class="mb-1">HR Analytics Dashboard</h1>
                <p class="text-muted mb-0">Comprehensive HR metrics and insights.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <a href="{{ route('hr.reports.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-file-earmark-text"></i> Reports
                </a>
                <a href="{{ route('hr.profile_requests.index') }}" class="btn btn-settings-primary">
                    <i class="bi bi-speedometer2"></i> Review Requests
                </a>
            </div>
        </div>

        {{-- KPI Cards --}}
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-primary h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">Total Staff</div>
                                <div class="d-flex align-items-baseline gap-2">
                                    <h3 class="mb-0">{{ $totalStaff }}</h3>
                                    <span class="pill-badge pill-secondary">All</span>
                                </div>
                            </div>
                            <span class="pill-icon pill-primary"><i class="bi bi-people"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-success h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">Active Staff</div>
                                <div class="d-flex align-items-baseline gap-2">
                                    <h3 class="mb-0">{{ $activeStaff }}</h3>
                                    <span class="pill-badge pill-success">Active</span>
                                </div>
                            </div>
                            <span class="pill-icon pill-success"><i class="bi bi-check-circle"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-info h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">New Hires (Year)</div>
                                <div class="d-flex align-items-baseline gap-2">
                                    <h3 class="mb-0">{{ $newHiresThisYear }}</h3>
                                    <span class="pill-badge pill-info">YTD</span>
                                </div>
                            </div>
                            <span class="pill-icon pill-info"><i class="bi bi-person-plus"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="settings-card stat-card border-start border-4 border-warning h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="text-muted text-uppercase fw-semibold small mb-1">On Leave</div>
                                <div class="d-flex align-items-baseline gap-2">
                                    <h3 class="mb-0">{{ $onLeaveStaff }}</h3>
                                    <span class="pill-badge pill-warning">Today</span>
                                </div>
                            </div>
                            <span class="pill-icon pill-warning"><i class="bi bi-calendar-event"></i></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            {{-- Left Column --}}
            <div class="col-12 col-xl-8">
                {{-- Charts Row --}}
                <div class="row g-3 mb-3">
                    <div class="col-12 col-lg-6">
                        <div class="settings-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Staff by Department</strong>
                                    <div class="text-muted small">Distribution across active departments</div>
                                </div>
                                <span class="pill-badge pill-secondary">Bar</span>
                            </div>
                            <div class="card-body"><canvas id="departmentChart" height="220"></canvas></div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="settings-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Staff by Category</strong>
                                    <div class="text-muted small">Headcount by HR category</div>
                                </div>
                                <span class="pill-badge pill-info">Donut</span>
                            </div>
                            <div class="card-body"><canvas id="categoryChart" height="220"></canvas></div>
                        </div>
                    </div>
                </div>

                {{-- Employment Status & Leave Utilization --}}
                <div class="row g-3 mb-3">
                    <div class="col-12 col-lg-6">
                        <div class="settings-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Employment Status Breakdown</strong>
                                    <div class="text-muted small">Contract vs permanent vs inactive</div>
                                </div>
                                <span class="pill-badge pill-success">Live</span>
                            </div>
                            <div class="card-body"><canvas id="employmentStatusChart" height="220"></canvas></div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <div class="settings-card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>Leave Utilization</strong>
                                    <div class="text-muted small">Entitlement vs usage</div>
                                </div>
                                <span class="pill-badge pill-warning">This Year</span>
                            </div>
                            <div class="card-body">
                                <div class="text-center mb-3">
                                    <h4 class="mb-0">{{ number_format($leaveUtilization['utilization_rate'], 1) }}%</h4>
                                    <small class="text-muted">Utilization Rate</small>
                                </div>
                                <div class="row text-center g-2">
                                    <div class="col-4">
                                        <div class="mini-stat">
                                            <div class="fw-semibold">{{ $leaveUtilization['total_entitlement'] }}</div>
                                            <small class="text-muted">Entitlement</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="mini-stat">
                                            <div class="fw-semibold">{{ $leaveUtilization['total_used'] }}</div>
                                            <small class="text-muted">Used</small>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="mini-stat">
                                            <div class="fw-semibold">{{ $leaveUtilization['total_remaining'] }}</div>
                                            <small class="text-muted">Remaining</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="divider my-3"></div>
                                <div class="row text-center g-2">
                                    <div class="col-6">
                                        <div class="mini-stat">
                                            <div class="fw-semibold">{{ $leaveUtilization['pending_requests'] }}</div>
                                            <small class="text-muted">Pending Requests</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="mini-stat">
                                            <div class="fw-semibold">{{ $leaveUtilization['approved_requests'] }}</div>
                                            <small class="text-muted">Approved (This Year)</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Attendance Statistics --}}
                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Attendance Statistics (This Month)</strong>
                            <div class="text-muted small">Presence, absences and punctuality</div>
                        </div>
                        <span class="pill-badge pill-info">{{ number_format($attendanceStats['attendance_rate'], 1) }}% rate</span>
                    </div>
                    <div class="card-body">
                        <div class="row text-center g-3">
                            <div class="col-md-3 col-6">
                                <div class="mini-stat">
                                    <h4 class="mb-0 text-success">{{ $attendanceStats['present'] }}</h4>
                                    <small class="text-muted">Present</small>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="mini-stat">
                                    <h4 class="mb-0 text-danger">{{ $attendanceStats['absent'] }}</h4>
                                    <small class="text-muted">Absent</small>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="mini-stat">
                                    <h4 class="mb-0 text-warning">{{ $attendanceStats['late'] }}</h4>
                                    <small class="text-muted">Late</small>
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="mini-stat">
                                    <h4 class="mb-0 text-info">{{ number_format($attendanceStats['attendance_rate'], 1) }}%</h4>
                                    <small class="text-muted">Attendance Rate</small>
                                </div>
                            </div>
                        </div>
                        <div class="divider my-3"></div>
                        <div class="row text-center g-3">
                            <div class="col-6">
                                <div class="mini-stat">
                                    <div class="fw-semibold">{{ $attendanceStats['present_today'] }}</div>
                                    <small class="text-muted">Present Today</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="mini-stat">
                                    <div class="fw-semibold">{{ $attendanceStats['absent_today'] }}</div>
                                    <small class="text-muted">Absent Today</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="col-12 col-xl-4">
                {{-- Recent Hires --}}
                <div class="settings-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Recent Hires</strong>
                            <div class="text-muted small">Latest additions to the team</div>
                        </div>
                        <span class="pill-badge pill-success">{{ $recentHires->count() }} new</span>
                    </div>
                    <div class="card-body">
                        @if($recentHires->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($recentHires as $hire)
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">{{ $hire->full_name }}</h6>
                                                <small class="text-muted">{{ $hire->department?->name ?? 'N/A' }} • {{ $hire->jobTitle?->name ?? 'N/A' }}</small>
                                            </div>
                                            <small class="text-muted">{{ $hire->hire_date?->format('M d, Y') ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted text-center mb-0">No recent hires</p>
                        @endif
                    </div>
                </div>

                {{-- Upcoming Contract Renewals --}}
                <div class="settings-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Upcoming Contract Renewals (Next 30 Days)</strong>
                            <div class="text-muted small">Keep contracts current</div>
                        </div>
                        <span class="pill-badge pill-warning">{{ $upcomingRenewals->count() }} due</span>
                    </div>
                    <div class="card-body">
                        @if($upcomingRenewals->count() > 0)
                            <div class="list-group list-group-flush">
                                @foreach($upcomingRenewals as $renewal)
                                    <div class="list-group-item px-0">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="mb-1">{{ $renewal->full_name }}</h6>
                                                <small class="text-muted">{{ $renewal->department?->name ?? 'N/A' }}</small>
                                            </div>
                                            <small class="text-danger">{{ $renewal->contract_end_date?->format('M d') ?? 'N/A' }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-muted text-center mb-0">No upcoming renewals</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Staff by Department Chart
  const deptCtx = document.getElementById('departmentChart');
  if (deptCtx) {
    new Chart(deptCtx, {
      type: 'bar',
      data: {
        labels: @json($staffByDepartment->pluck('name')),
        datasets: [{
          label: 'Staff Count',
          data: @json($staffByDepartment->pluck('count')),
          backgroundColor: 'rgba(54, 162, 235, 0.6)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false }
        },
        scales: {
          y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
      }
    });
  }

  // Staff by Category Chart
  const catCtx = document.getElementById('categoryChart');
  if (catCtx) {
    new Chart(catCtx, {
      type: 'doughnut',
      data: {
        labels: @json($staffByCategory->pluck('name')),
        datasets: [{
          data: @json($staffByCategory->pluck('count')),
          backgroundColor: [
            'rgba(255, 99, 132, 0.6)',
            'rgba(54, 162, 235, 0.6)',
            'rgba(255, 206, 86, 0.6)',
            'rgba(75, 192, 192, 0.6)',
            'rgba(153, 102, 255, 0.6)',
            'rgba(255, 159, 64, 0.6)'
          ],
          borderColor: [
            'rgba(255, 99, 132, 1)',
            'rgba(54, 162, 235, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(255, 159, 64, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });
  }

  // Employment Status Chart
  const empCtx = document.getElementById('employmentStatusChart');
  if (empCtx) {
    new Chart(empCtx, {
      type: 'pie',
      data: {
        labels: @json($employmentStatusBreakdown->pluck('status')),
        datasets: [{
          data: @json($employmentStatusBreakdown->pluck('count')),
          backgroundColor: [
            'rgba(40, 167, 69, 0.6)',
            'rgba(255, 193, 7, 0.6)',
            'rgba(220, 53, 69, 0.6)',
            'rgba(108, 117, 125, 0.6)'
          ],
          borderColor: [
            'rgba(40, 167, 69, 1)',
            'rgba(255, 193, 7, 1)',
            'rgba(220, 53, 69, 1)',
            'rgba(108, 117, 125, 1)'
          ],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' }
        }
      }
    });
  }
});
</script>
@endpush

