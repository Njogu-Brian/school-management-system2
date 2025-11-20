@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-0">HR Analytics Dashboard</h2>
      <small class="text-muted">Comprehensive HR metrics and insights</small>
    </div>
    <a href="{{ route('hr.reports.index') }}" class="btn btn-outline-primary">
      <i class="bi bi-file-earmark-text"></i> Reports
    </a>
  </div>

  {{-- KPI Cards --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Total Staff</h6>
              <h3 class="mb-0">{{ $totalStaff }}</h3>
            </div>
            <i class="bi bi-people fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">Active Staff</h6>
              <h3 class="mb-0">{{ $activeStaff }}</h3>
            </div>
            <i class="bi bi-check-circle fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">New Hires (This Year)</h6>
              <h3 class="mb-0">{{ $newHiresThisYear }}</h3>
            </div>
            <i class="bi bi-person-plus fs-1 opacity-50"></i>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning text-white">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <h6 class="card-subtitle mb-2 text-white-50">On Leave</h6>
              <h3 class="mb-0">{{ $onLeaveStaff }}</h3>
            </div>
            <i class="bi bi-calendar-x fs-1 opacity-50"></i>
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
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <strong>Staff by Department</strong>
            </div>
            <div class="card-body">
              <canvas id="departmentChart" height="200"></canvas>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <strong>Staff by Category</strong>
            </div>
            <div class="card-body">
              <canvas id="categoryChart" height="200"></canvas>
            </div>
          </div>
        </div>
      </div>

      {{-- Employment Status & Leave Utilization --}}
      <div class="row g-3 mb-3">
        <div class="col-12 col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <strong>Employment Status Breakdown</strong>
            </div>
            <div class="card-body">
              <canvas id="employmentStatusChart" height="200"></canvas>
            </div>
          </div>
        </div>
        <div class="col-12 col-lg-6">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <strong>Leave Utilization</strong>
            </div>
            <div class="card-body">
              <div class="text-center mb-3">
                <h4 class="mb-0">{{ number_format($leaveUtilization['utilization_rate'], 1) }}%</h4>
                <small class="text-muted">Utilization Rate</small>
              </div>
              <div class="row text-center">
                <div class="col-4">
                  <div class="border-end">
                    <h6 class="mb-0">{{ $leaveUtilization['total_entitlement'] }}</h6>
                    <small class="text-muted">Entitlement</small>
                  </div>
                </div>
                <div class="col-4">
                  <div class="border-end">
                    <h6 class="mb-0">{{ $leaveUtilization['total_used'] }}</h6>
                    <small class="text-muted">Used</small>
                  </div>
                </div>
                <div class="col-4">
                  <h6 class="mb-0">{{ $leaveUtilization['total_remaining'] }}</h6>
                  <small class="text-muted">Remaining</small>
                </div>
              </div>
              <hr>
              <div class="row text-center">
                <div class="col-6">
                  <h6 class="mb-0">{{ $leaveUtilization['pending_requests'] }}</h6>
                  <small class="text-muted">Pending Requests</small>
                </div>
                <div class="col-6">
                  <h6 class="mb-0">{{ $leaveUtilization['approved_requests'] }}</h6>
                  <small class="text-muted">Approved (This Year)</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Attendance Statistics --}}
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <strong>Attendance Statistics (This Month)</strong>
        </div>
        <div class="card-body">
          <div class="row text-center">
            <div class="col-md-3">
              <h4 class="mb-0 text-success">{{ $attendanceStats['present'] }}</h4>
              <small class="text-muted">Present</small>
            </div>
            <div class="col-md-3">
              <h4 class="mb-0 text-danger">{{ $attendanceStats['absent'] }}</h4>
              <small class="text-muted">Absent</small>
            </div>
            <div class="col-md-3">
              <h4 class="mb-0 text-warning">{{ $attendanceStats['late'] }}</h4>
              <small class="text-muted">Late</small>
            </div>
            <div class="col-md-3">
              <h4 class="mb-0 text-info">{{ number_format($attendanceStats['attendance_rate'], 1) }}%</h4>
              <small class="text-muted">Attendance Rate</small>
            </div>
          </div>
          <hr>
          <div class="row text-center">
            <div class="col-6">
              <h6 class="mb-0">{{ $attendanceStats['present_today'] }}</h6>
              <small class="text-muted">Present Today</small>
            </div>
            <div class="col-6">
              <h6 class="mb-0">{{ $attendanceStats['absent_today'] }}</h6>
              <small class="text-muted">Absent Today</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Right Column --}}
    <div class="col-12 col-xl-4">
      {{-- Recent Hires --}}
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-white">
          <strong>Recent Hires</strong>
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
      <div class="card shadow-sm">
        <div class="card-header bg-white">
          <strong>Upcoming Contract Renewals (Next 30 Days)</strong>
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

