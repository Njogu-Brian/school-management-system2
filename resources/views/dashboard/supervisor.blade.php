{{-- resources/views/dashboard/supervisor.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container-xxl">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-1">Supervisor Dashboard</h2>
      <p class="text-muted mb-0">Welcome back, {{ $staff->full_name ?? auth()->user()->name }}!</p>
    </div>
    <div>
      <span class="badge bg-primary">{{ now()->format('l, F j, Y') }}</span>
    </div>
  </div>

  {{-- Quick Stats --}}
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <div class="flex-grow-1">
              <div class="text-muted small mb-1">Subordinates</div>
              <div class="fs-3 fw-bold text-primary">{{ $subordinateStats['total'] }}</div>
              <small class="text-muted">{{ $subordinateStats['active'] }} active</small>
            </div>
            <div class="ms-3">
              <span class="badge rounded-circle p-3 bg-primary bg-opacity-10">
                <i class="bi bi-people fs-4 text-primary"></i>
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
              <div class="text-muted small mb-1">Assigned Classes</div>
              <div class="fs-3 fw-bold text-success">{{ $subordinateStats['totalClasses'] }}</div>
            </div>
            <div class="ms-3">
              <span class="badge rounded-circle p-3 bg-success bg-opacity-10">
                <i class="bi bi-building fs-4 text-success"></i>
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
              <div class="text-muted small mb-1">Pending Approvals</div>
              <div class="fs-3 fw-bold text-warning">{{ $subordinateStats['pendingApprovals'] }}</div>
              <small class="text-muted">Lesson plans</small>
            </div>
            <div class="ms-3">
              <span class="badge rounded-circle p-3 bg-warning bg-opacity-10">
                <i class="bi bi-journal-check fs-4 text-warning"></i>
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
              <div class="text-muted small mb-1">Pending Leaves</div>
              <div class="fs-3 fw-bold text-info">{{ $subordinateStats['pendingLeaves'] }}</div>
              <small class="text-muted">Leave requests</small>
            </div>
            <div class="ms-3">
              <span class="badge rounded-circle p-3 bg-info bg-opacity-10">
                <i class="bi bi-calendar-event fs-4 text-info"></i>
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
      {{-- Pending Lesson Plans --}}
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-journal-text me-2"></i>Pending Lesson Plan Approvals</h5>
        </div>
        <div class="card-body">
          @if($pendingLessonPlans->isEmpty())
            <div class="text-center py-4">
              <i class="bi bi-check-circle fs-1 text-success"></i>
              <p class="text-muted mt-2">No pending lesson plans to approve.</p>
            </div>
          @else
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Classroom</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Planned Date</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendingLessonPlans as $plan)
                    <tr>
                      <td>{{ $plan->title }}</td>
                      <td>{{ $plan->classroom->name }}</td>
                      <td>{{ $plan->subject->name }}</td>
                      <td>{{ $plan->creator->full_name ?? 'N/A' }}</td>
                      <td>{{ $plan->planned_date->format('d M Y') }}</td>
                      <td>
                        <a href="{{ route('academics.lesson-plans.show', $plan) }}" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-eye"></i> Review
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="text-center mt-3">
              <a href="{{ route('academics.lesson-plans.index') }}" class="btn btn-primary">View All Lesson Plans</a>
            </div>
          @endif
        </div>
      </div>

      {{-- Pending Leave Requests --}}
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Pending Leave Requests</h5>
        </div>
        <div class="card-body">
          @if($pendingLeaveRequests->isEmpty())
            <div class="text-center py-4">
              <i class="bi bi-check-circle fs-1 text-success"></i>
              <p class="text-muted mt-2">No pending leave requests.</p>
            </div>
          @else
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Staff Member</th>
                    <th>Leave Type</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Days</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendingLeaveRequests as $leave)
                    <tr>
                      <td>{{ $leave->staff->full_name }}</td>
                      <td>{{ $leave->leaveType->name }}</td>
                      <td>{{ $leave->start_date->format('d M Y') }}</td>
                      <td>{{ $leave->end_date->format('d M Y') }}</td>
                      <td>{{ $leave->days_requested }}</td>
                      <td>
                        <a href="{{ route('supervisor.leave-requests.show', $leave) }}" class="btn btn-sm btn-outline-primary">
                          <i class="bi bi-eye"></i> Review
                        </a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="text-center mt-3">
              <a href="{{ route('supervisor.leave-requests.index') }}" class="btn btn-primary">View All Leave Requests</a>
            </div>
          @endif
        </div>
      </div>

      {{-- Recent Activity --}}
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Activity</h5>
        </div>
        <div class="card-body">
          <ul class="list-unstyled mb-0">
            @foreach($recentLessonPlans->take(5) as $plan)
              <li class="mb-3 pb-3 border-bottom">
                <div class="d-flex justify-content-between">
                  <div>
                    <strong>{{ $plan->creator->full_name ?? 'N/A' }}</strong> created lesson plan
                    <strong>{{ $plan->title }}</strong> for <strong>{{ $plan->classroom->name }}</strong>
                    <br><small class="text-muted">{{ $plan->created_at->diffForHumans() }}</small>
                  </div>
                  <a href="{{ route('academics.lesson-plans.show', $plan) }}" class="btn btn-sm btn-outline-primary">View</a>
                </div>
              </li>
            @endforeach
          </ul>
        </div>
      </div>
    </div>

    {{-- Right Column --}}
    <div class="col-lg-4">
      {{-- My Subordinates --}}
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-people me-2"></i>My Subordinates</h5>
        </div>
        <div class="card-body">
          @if($subordinates->isEmpty())
            <div class="text-center py-4">
              <i class="bi bi-inbox fs-1 text-muted"></i>
              <p class="text-muted mt-2">No subordinates assigned.</p>
            </div>
          @else
            <div class="list-group list-group-flush">
              @foreach($subordinates->take(10) as $subordinate)
                <div class="list-group-item px-0">
                  <div class="d-flex justify-content-between align-items-center">
                    <div>
                      <strong>{{ $subordinate->full_name }}</strong>
                      <br><small class="text-muted">{{ $subordinate->department->name ?? 'N/A' }}</small>
                    </div>
                    <span class="badge bg-{{ $subordinate->status == 'active' ? 'success' : 'secondary' }}">
                      {{ ucfirst($subordinate->status) }}
                    </span>
                  </div>
                </div>
              @endforeach
            </div>
            @if($subordinates->count() > 10)
              <div class="text-center mt-3">
                <a href="{{ route('staff.index') }}?supervisor_id={{ $staff->id }}" class="btn btn-sm btn-outline-primary">View All</a>
              </div>
            @endif
          @endif
        </div>
      </div>

      {{-- Quick Actions --}}
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Quick Actions</h5>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="{{ route('academics.lesson-plans.index') }}" class="btn btn-outline-primary">
              <i class="bi bi-journal-text"></i> Review Lesson Plans
            </a>
            <a href="{{ route('academics.exams.index') }}" class="btn btn-outline-success">
              <i class="bi bi-file-earmark-text"></i> View Exams
            </a>
            <a href="{{ route('academics.timetable.index') }}" class="btn btn-outline-info">
              <i class="bi bi-calendar-week"></i> Manage Timetable
            </a>
            <a href="{{ route('supervisor.leave-requests.index') }}" class="btn btn-outline-warning">
              <i class="bi bi-calendar-event"></i> Leave Requests
            </a>
            <a href="{{ route('supervisor.attendance.index') }}" class="btn btn-outline-secondary">
              <i class="bi bi-clock-history"></i> Staff Attendance
            </a>
          </div>
        </div>
      </div>

      {{-- Assigned Classrooms --}}
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-building me-2"></i>Assigned Classrooms</h5>
        </div>
        <div class="card-body">
          @if($subordinateClassrooms->isEmpty())
            <p class="text-muted mb-0">No classrooms assigned to subordinates.</p>
          @else
            <div class="list-group list-group-flush">
              @foreach($subordinateClassrooms->take(10) as $classroom)
                <div class="list-group-item px-0">
                  <strong>{{ $classroom->name }}</strong>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

