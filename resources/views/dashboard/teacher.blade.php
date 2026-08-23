{{-- resources/views/dashboard/teacher.blade.php --}}
@extends('layouts.app')

@push('styles')
    @include('dashboard.partials.styles')
@endpush

@section('content')
<div class="dashboard-page">
  <div class="dashboard-shell">
    <div class="dash-hero d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
      <div>
        <span class="crumb">Teacher</span>
        <h2 class="mb-1">{{ now()->format('G') < 12 ? 'Good morning' : (now()->format('G') < 17 ? 'Good afternoon' : 'Good evening') }}, {{ $staff->full_name ?? auth()->user()->name }}</h2>
        <p class="mb-0">What do you need to do today?</p>
      </div>
      <div>
        <span class="dash-chip">{{ now()->format('l, F j, Y') }}</span>
      </div>
    </div>

    @if(($pendingAttendance ?? collect())->isNotEmpty() || ($pendingMarks ?? collect())->isNotEmpty() || ($pendingHomework ?? collect())->isNotEmpty())
      <div class="dash-card card card-body mb-3">
        <div class="fw-semibold mb-2">Needs attention today</div>
        <div class="d-flex flex-wrap gap-2">
          @if(($pendingAttendance ?? collect())->isNotEmpty())
            <a class="btn btn-warning" href="{{ route('attendance.mark.form') }}">Attendance to complete ({{ $pendingAttendance->count() }})</a>
          @endif
          @if(($pendingMarks ?? collect())->isNotEmpty())
            <a class="btn btn-danger" href="{{ route('academics.exam-marks.index') }}">Marks to enter ({{ $pendingMarks->count() }})</a>
          @endif
          @if(($pendingHomework ?? collect())->isNotEmpty() && Route::has('academics.homework.index'))
            <a class="btn btn-info" href="{{ route('academics.homework.index') }}">Homework ({{ $pendingHomework->count() }})</a>
          @endif
        </div>
      </div>
    @endif

  {{-- Today's Timetable first --}}
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-bottom">
      <h5 class="mb-0"><i class="bi bi-calendar-week me-2"></i>Today's Timetable</h5>
    </div>
    <div class="card-body">
      @if($upcomingLessons->isEmpty())
        <div class="erp-empty">
          <i class="bi bi-calendar-x"></i>
          No lessons scheduled for today.
          @if(Route::has('academics.timetable.index'))
            <div class="mt-2"><a href="{{ route('academics.timetable.index') }}" class="btn btn-primary btn-sm">View Full Timetable</a></div>
          @endif
        </div>
      @else
        <div class="list-group list-group-flush">
          @foreach($upcomingLessons as $lesson)
            <div class="list-group-item border-0 px-0">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <h6 class="mb-1">
                    <i class="bi bi-clock me-2 text-primary"></i>
                    Period {{ $lesson['period'] ?? 'N/A' }}
                    @if(isset($lesson['start']) && isset($lesson['end']))
                      <small class="text-muted">({{ $lesson['start'] }} - {{ $lesson['end'] }})</small>
                    @endif
                  </h6>
                  <p class="mb-1">
                    <strong>{{ $lesson['subject']->name ?? 'Subject' }}</strong> -
                    {{ $lesson['classroom']->name ?? 'Class' }}
                  </p>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  {{-- Assigned scope --}}
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted small mb-1">My Classes</div>
          <div class="fs-3 fw-bold text-primary">{{ $assignedClasses->count() }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted small mb-1">Subjects</div>
          <div class="fs-3 fw-bold">{{ $assignedSubjects->count() }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted small mb-1">My Students</div>
          <div class="fs-3 fw-bold">{{ number_format($totalStudents) }}</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-body">
          <div class="text-muted small mb-1">Today's Lessons</div>
          <div class="fs-3 fw-bold">{{ $upcomingLessons->count() }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- Main Content Row --}}
  <div class="row g-4">
    {{-- Left Column --}}
    <div class="col-lg-8">
      {{-- My Classes & Subjects --}}
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-building me-2"></i>My Classes</h5>
        </div>
        <div class="card-body">
          @if($assignedClasses->isEmpty())
            <div class="erp-empty">
              <i class="bi bi-inbox"></i>
              No classes assigned yet. Contact administrator.
            </div>
          @else
            <div class="table-responsive erp-invoice-table">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>Class</th>
                    <th>Subjects</th>
                    <th>Students</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($assignedClasses as $classroom)
                    @php
                      $classAssignments = $assignments->where('classroom_id', $classroom->id);
                      $classSubjects = $classAssignments->pluck('subject.name')->unique()->filter();
                      $classStudents = $studentsByClass->get($classroom->id, collect())->count();
                      $assignedStreamNames = $streamNamesByClass[$classroom->id] ?? [];
                    @endphp
                    <tr>
                      <td>
                        <strong>{{ $classroom->name }}</strong>
                        @if($classAssignments->first()?->stream)
                          <br><small class="text-muted">{{ $classAssignments->first()->stream->name }}</small>
                        @elseif(!empty($assignedStreamNames))
                          <br><small class="text-muted">{{ implode(', ', $assignedStreamNames) }}</small>
                        @endif
                      </td>
                      <td>
                        @foreach($classSubjects as $subject)
                          <span class="badge bg-light text-dark me-1">{{ $subject }}</span>
                        @endforeach
                      </td>
                      <td>
                        <span class="badge bg-info">{{ $classStudents }} students</span>
                      </td>
                      <td>
                        <div class="btn-group btn-group-sm">
                          <a href="{{ route('attendance.mark.form') }}?classroom_id={{ $classroom->id }}"
                             class="btn btn-outline-primary" title="Mark Attendance">
                            <i class="bi bi-calendar-check"></i>
                          </a>
                          <a href="{{ route('academics.exam-marks.index') }}?classroom_id={{ $classroom->id }}"
                             class="btn btn-outline-success" title="View Marks">
                            <i class="bi bi-journal-check"></i>
                          </a>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
            <div class="erp-invoice-cards">
              @foreach($assignedClasses as $classroom)
                @php
                  $classAssignments = $assignments->where('classroom_id', $classroom->id);
                  $classSubjects = $classAssignments->pluck('subject.name')->unique()->filter();
                  $classStudents = $studentsByClass->get($classroom->id, collect())->count();
                  $assignedStreamNames = $streamNamesByClass[$classroom->id] ?? [];
                @endphp
                <div class="erp-invoice-card">
                  <div class="fw-semibold">{{ $classroom->name }}</div>
                  <div class="small dash-muted">{{ implode(', ', $assignedStreamNames) ?: ($classAssignments->first()?->stream?->name ?? '') }}</div>
                  <div class="small mt-1">{{ $classSubjects->implode(', ') }}</div>
                  <div class="erp-kv mt-2"><span>Students</span><strong>{{ $classStudents }}</strong></div>
                  <div class="d-grid gap-2 mt-2">
                    <a href="{{ route('attendance.mark.form') }}?classroom_id={{ $classroom->id }}" class="btn btn-outline-primary btn-sm">Mark Attendance</a>
                    <a href="{{ route('academics.exam-marks.index') }}?classroom_id={{ $classroom->id }}" class="btn btn-outline-success btn-sm">Enter Marks</a>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      {{-- Pending Tasks --}}
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>Pending Tasks</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            {{-- Pending Attendance --}}
            <div class="col-md-6">
              <div class="card border-warning">
                <div class="card-body">
                  <h6 class="card-title">
                    <i class="bi bi-calendar-check text-warning me-2"></i>Attendance
                  </h6>
                  @if($pendingAttendance->isEmpty())
                    <p class="text-success mb-0"><i class="bi bi-check-circle me-1"></i>All classes marked</p>
                  @else
                    <p class="text-warning mb-2"><strong>{{ $pendingAttendance->count() }}</strong> classes need attendance</p>
                    <ul class="list-unstyled mb-0">
                      @foreach($pendingAttendance->take(3) as $classroom)
                        <li>
                          <a href="{{ route('attendance.mark.form') }}?classroom_id={{ $classroom->id }}" 
                             class="text-decoration-none">
                            <i class="bi bi-arrow-right me-1"></i>{{ $classroom->name }}
                          </a>
                        </li>
                      @endforeach
                    </ul>
                    @if($pendingAttendance->count() > 3)
                      <a href="{{ route('attendance.mark.form') }}" class="btn btn-sm btn-outline-warning mt-2">
                        View All
                      </a>
                    @endif
                  @endif
                </div>
              </div>
            </div>

            {{-- Pending Marks --}}
            <div class="col-md-6">
              <div class="card border-danger">
                <div class="card-body">
                  <h6 class="card-title">
                    <i class="bi bi-journal-check text-danger me-2"></i>Marks Entry
                  </h6>
                  @if($pendingMarks->isEmpty())
                    <p class="text-success mb-0"><i class="bi bi-check-circle me-1"></i>No pending marks</p>
                  @else
                    <p class="text-danger mb-2"><strong>{{ $pendingMarks->count() }}</strong> exams need marks</p>
                    <ul class="list-unstyled mb-0">
                      @foreach($pendingMarks->take(3) as $exam)
                        <li>
                          <a href="{{ route('academics.exam-marks.bulk.form') }}?exam_id={{ $exam->id }}" 
                             class="text-decoration-none">
                            <i class="bi bi-arrow-right me-1"></i>{{ $exam->name }}
                          </a>
                        </li>
                      @endforeach
                    </ul>
                    @if($pendingMarks->count() > 3)
                      <a href="{{ route('academics.exam-marks.index') }}" class="btn btn-sm btn-outline-danger mt-2">
                        View All
                      </a>
                    @endif
                  @endif
                </div>
              </div>
            </div>

            {{-- Pending Homework --}}
            <div class="col-md-6">
              <div class="card border-info">
                <div class="card-body">
                  <h6 class="card-title">
                    <i class="bi bi-journal text-info me-2"></i>Homework Review
                  </h6>
                  @if($pendingHomework->isEmpty())
                    <p class="text-success mb-0"><i class="bi bi-check-circle me-1"></i>No pending reviews</p>
                  @else
                    <p class="text-info mb-2"><strong>{{ $pendingHomework->count() }}</strong> assignments to review</p>
                    <ul class="list-unstyled mb-0">
                      @foreach($pendingHomework->take(3) as $homework)
                        <li>
                          <a href="{{ route('academics.homework.show', $homework->id) }}" 
                             class="text-decoration-none">
                            <i class="bi bi-arrow-right me-1"></i>{{ $homework->title }}
                          </a>
                        </li>
                      @endforeach
                    </ul>
                    @if($pendingHomework->count() > 3)
                      <a href="{{ route('academics.homework.index') }}" class="btn btn-sm btn-outline-info mt-2">
                        View All
                      </a>
                    @endif
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card shadow-sm border-0 mt-4">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-exclamation-circle me-2"></i>Student Alerts</h5>
        </div>
        <div class="card-body">
          @forelse($absenceAlerts ?? [] as $row)
            <a href="{{ route('attendance.records', ['student_id' => $row->student_id]) }}" class="dash-list-item d-flex justify-content-between text-decoration-none">
              <span>{{ $row->student_name }} · {{ $row->days_absent }} days absent</span>
              <i class="bi bi-arrow-right"></i>
            </a>
          @empty
            <div class="erp-empty"><i class="bi bi-check-circle"></i>No attendance alerts for your students in the last 7 days.</div>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Right Column --}}
    <div class="col-lg-4">
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-journal-check me-2"></i>Upcoming Assessments</h5>
        </div>
        <div class="card-body">
          @forelse($upcomingAssessments ?? [] as $exam)
            <div class="dash-list-item">
              <div class="fw-semibold">{{ $exam->name }}</div>
              <div class="small dash-muted">{{ optional($exam->starts_on)->format('M j, Y') ?? \Carbon\Carbon::parse($exam->starts_on)->format('M j, Y') }}</div>
            </div>
          @empty
            <div class="erp-empty"><i class="bi bi-calendar-x"></i>No upcoming assessments for your classes.</div>
          @endforelse
        </div>
      </div>

      {{-- Announcements --}}
      @include('dashboard.partials.announcements', ['announcements' => $announcements])

      {{-- Recent Homework --}}
      @if(isset($recentHomework) && $recentHomework->isNotEmpty())
        <div class="card shadow-sm border-0 mb-4">
          <div class="card-header bg-white border-bottom">
            <h5 class="mb-0"><i class="bi bi-journal me-2"></i>Recent Homework</h5>
          </div>
          <div class="card-body">
            <div class="list-group list-group-flush">
              @foreach($recentHomework->take(5) as $homework)
                <div class="list-group-item border-0 px-0">
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                      <h6 class="mb-1">
                        <a href="{{ route('academics.homework.show', $homework->id) }}" 
                           class="text-decoration-none">
                          {{ Str::limit($homework->title, 30) }}
                        </a>
                      </h6>
                      <small class="text-muted">
                        {{ $homework->subject->name ?? 'Subject' }} - {{ $homework->classroom->name ?? 'Class' }}
                      </small>
                      <br>
                      <small class="text-muted">
                        Due: {{ $homework->due_date->format('M j, Y') }}
                      </small>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
            <div class="mt-3 text-center">
              <a href="{{ route('academics.homework.index') }}" class="btn btn-outline-primary btn-sm">
                View All Homework
              </a>
            </div>
          </div>
        </div>
      @endif

      {{-- Quick Actions --}}
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white border-bottom">
          <h5 class="mb-0"><i class="bi bi-lightning me-2"></i>Quick Actions</h5>
        </div>
        <div class="card-body">
          <div class="d-grid gap-2">
            <a href="{{ route('attendance.mark.form') }}" class="btn btn-primary">
              <i class="bi bi-calendar-check me-2"></i>Mark Attendance
            </a>
            @if(Route::has('academics.exam-marks.bulk.form'))
            <a href="{{ route('academics.exam-marks.bulk.form') }}" class="btn btn-success">
              <i class="bi bi-journal-check me-2"></i>Enter Marks
            </a>
            @endif
            @if(Route::has('academics.homework.create'))
            <a href="{{ route('academics.homework.create') }}" class="btn btn-info">
              <i class="bi bi-journal-plus me-2"></i>Create Homework
            </a>
            @endif
            @if(Route::has('academics.diaries.index'))
            <a href="{{ route('academics.diaries.index') }}" class="btn btn-warning">
              <i class="bi bi-journals me-2"></i>Open Diaries
            </a>
            @endif
            @if(Route::has('academics.timetable.index'))
            <a href="{{ route('academics.timetable.index') }}" class="btn btn-secondary">
              <i class="bi bi-calendar-week me-2"></i>View Timetable
            </a>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
