{{-- Senior Teacher navigation - Teacher permissions + Supervisory functions --}}
@php
  $attActive = Request::is('attendance*');
  $swimmingActive = Request::is('swimming*');
  $activityFeesActive = Request::is('activity-fees*');
  $marksActive = Request::is('exam-marks*') || Request::is('academics/exams/grading*');
  $examReportsActive = Request::is('academics/exam-reports*');
  $reportsActive = Request::is('academics/report_cards*') || Request::is('academics/skills/grade*');
  $homeworkActive = Request::is('academics/homework*') || Request::is('academics/diaries*');
  $diariesActive = Request::is('academics/diaries*');
  $behaviourActive = Request::is('academics/behaviours*') || Request::is('academics/student-behaviours*');
  $studentsActive = Request::is('senior-teacher/students*')
    || (Request::is('students*') && !Request::is('students/bulk-assign-streams*'));
  $classroomMgmtActive = Request::is('academics/classrooms*')
    || Request::is('academics/streams*')
    || Request::is('academics/subjects*')
    || Request::is('academics/assign-teachers*')
    || Request::is('academics/teacher-assignments*');
  $cbcActive = Request::is('academics/learning-areas*')
    || Request::is('academics/competencies*')
    || Request::is('academics/cbc-strands*')
    || Request::is('academics/cbc-substrands*')
    || Request::is('academics/curriculum-designs*')
    || Request::is('academics/schemes-of-work*')
    || Request::is('academics/lesson-plans*')
    || Request::is('academics/portfolio-assessments*')
    || Request::is('academics/exam-analytics*');
  $supervisedClassActive = Request::is('senior-teacher/supervised-classrooms*');
  $supervisedStaffActive = Request::is('senior-teacher/supervised-staff*');
  $feeBalancesActive = Request::is('finance/fee-balances*') || Request::is('senior-teacher/fee-balances*');
  $salaryActive = Request::is('senior-teacher/salary*');
  $leaveActive = Request::is('senior-teacher/leaves*');
  $timetableActive = Request::is('academics/timetable*') || Request::is('senior-teacher/timetable*') || Request::is('academics/activities*');
  $announcementsActive = Request::is('senior-teacher/announcements*');
  $eventsActive = Request::is('events*');
  $transportActive = Request::is('transport*');
  $specialAssignmentsActive = Request::is('transport/special-assignments*');
  $inventoryActive = Request::is('inventory*');
  $hrReportsActive = Request::is('hr/reports*') || Request::is('senior-teacher/reports*');
@endphp

{{-- Dashboard --}}
@include('layouts.partials.nav-section', ['label' => 'Main'])
<a href="{{ route('senior_teacher.dashboard') }}"
   class="{{ Request::is('senior-teacher/home') ? 'active' : '' }}">
  <i class="bi bi-speedometer2"></i> Dashboard
</a>

{{-- My Profile --}}
<a href="{{ route('staff.profile.show') }}" class="{{ Request::is('my/profile') ? 'active' : '' }}">
  <i class="bi bi-person-circle"></i> My Profile
</a>

{{-- Supervisory Section --}}
<div class="mt-3 pt-3 border-top">
  <div class="nav-section-label">Supervisory</div>
  
  {{-- Supervised Classrooms --}}
  <a href="{{ route('senior_teacher.supervised_classrooms') }}" 
     class="{{ $supervisedClassActive ? 'active' : '' }}">
    <i class="bi bi-building"></i> Supervised Classrooms
  </a>
  
  {{-- Supervised Staff --}}
  <a href="{{ route('senior_teacher.supervised_staff') }}" 
     class="{{ $supervisedStaffActive ? 'active' : '' }}">
    <i class="bi bi-person-badge"></i> Supervised Staff
  </a>
  
  {{-- Students (view only) --}}
  <a href="#stStudentsMenu" data-bs-toggle="collapse"
     aria-expanded="{{ $studentsActive ? 'true' : 'false' }}"
     class="{{ $studentsActive ? 'parent-active' : '' }}">
    <i class="bi bi-person"></i> Students
  </a>
  <div class="collapse {{ $studentsActive ? 'show' : '' }}" id="stStudentsMenu">
    <a href="{{ route('students.index') }}"
       class="sublink {{ Request::is('students') && !Request::is('students/*') ? 'active' : '' }}">
      <i class="bi bi-card-list"></i> Student Details
    </a>
    <a href="{{ route('senior_teacher.students.index') }}"
       class="sublink {{ Request::is('senior-teacher/students*') ? 'active' : '' }}">
      <i class="bi bi-people"></i> Supervised Students
    </a>
    @if(Route::has('students.parents-contact'))
    <a href="{{ route('students.parents-contact') }}"
       class="sublink {{ Request::is('students/parents-contact*') ? 'active' : '' }}">
      <i class="bi bi-telephone"></i> Parents Contact
    </a>
    @endif
    @if(Route::has('students.enrollment-report'))
    <a href="{{ route('students.enrollment-report') }}"
       class="sublink {{ request()->routeIs('students.enrollment-report*') ? 'active' : '' }}">
      <i class="bi bi-bar-chart-steps"></i> Enrollment by Class
    </a>
    @endif
  </div>

  {{-- Classroom management --}}
  <a href="#stClassroomMenu" data-bs-toggle="collapse"
     aria-expanded="{{ $classroomMgmtActive ? 'true' : 'false' }}"
     class="{{ $classroomMgmtActive ? 'parent-active' : '' }}">
    <i class="bi bi-journal-bookmark"></i> Classroom Management
  </a>
  <div class="collapse {{ $classroomMgmtActive ? 'show' : '' }}" id="stClassroomMenu">
    @if (Route::has('academics.classrooms.index'))
    <a href="{{ route('academics.classrooms.index') }}" class="sublink {{ Request::is('academics/classrooms*') ? 'active' : '' }}">
      <i class="bi bi-building"></i> Classrooms
    </a>
    @endif
    @if (Route::has('academics.streams.index'))
    <a href="{{ route('academics.streams.index') }}" class="sublink {{ Request::is('academics/streams*') ? 'active' : '' }}">
      <i class="bi bi-diagram-3"></i> Class Streams
    </a>
    @endif
    @if (Route::has('academics.subjects.teacher-assignments'))
    <a href="{{ route('academics.subjects.teacher-assignments') }}" class="sublink {{ Request::is('academics/subjects/teacher-assignments*') ? 'active' : '' }}">
      <i class="bi bi-person-lines-fill"></i> Subject Teacher Map
    </a>
    @endif
    @if (Route::has('academics.assign-teachers'))
    <a href="{{ route('academics.assign-teachers') }}" class="sublink {{ Request::is('academics/assign-teachers*') ? 'active' : '' }}">
      <i class="bi bi-person-check"></i> Assign Class Teachers
    </a>
    @endif
    @if (Route::has('academics.teacher-assignments.index'))
    <a href="{{ route('academics.teacher-assignments.index') }}" class="sublink {{ Request::is('academics/teacher-assignments*') ? 'active' : '' }}">
      <i class="bi bi-mortarboard"></i> Teacher Assignments
    </a>
    @endif
    @if (Route::has('students.bulk.assign-streams'))
    <a href="{{ route('students.bulk.assign-streams') }}" class="sublink {{ Request::is('students/bulk-assign-streams*') ? 'active' : '' }}">
      <i class="bi bi-people-fill"></i> Bulk Assign Streams
    </a>
    @endif
  </div>
  
  {{-- Fee Balance Report --}}
  <a href="{{ route('finance.fee-balances.index') }}" 
     class="{{ Request::is('finance/fee-balances*') ? 'active' : '' }}">
    <i class="bi bi-wallet2"></i> Fee Balance Report
  </a>
  <a href="{{ route('senior_teacher.fee_balances') }}" 
     class="{{ Request::is('senior-teacher/fee-balances*') ? 'active' : '' }}">
    <i class="bi bi-currency-exchange"></i> Supervised Fee Balances
  </a>
  
  {{-- Leave Approval --}}
  <a href="{{ route('supervisor.leave-requests.index') }}" 
     class="{{ Request::is('supervisor/leave-requests*') ? 'active' : '' }}">
    <i class="bi bi-check-circle"></i> Leave Approval
  </a>

  {{-- Staff Attendance --}}
  <a href="{{ route('senior_teacher.staff_attendance.report') }}"
     class="{{ Request::is('senior-teacher/staff-attendance/report*') ? 'active' : '' }}">
    <i class="bi bi-clock-history"></i> Staff Attendance
  </a>
  <a href="{{ route('senior_teacher.staff_attendance.gate-logs') }}"
     class="{{ Request::is('senior-teacher/staff-attendance/gate-logs*') ? 'active' : '' }}">
    <i class="bi bi-fingerprint"></i> Gate Punch Log
  </a>
</div>

{{-- Teaching Section --}}
<div class="mt-3 pt-3 border-top">
  <div class="nav-section-label">Teaching & Academics</div>

  {{-- Assessments --}}
  <a href="{{ route('academics.assessments.index') }}" class="{{ Request::is('academics/assessments*') ? 'active' : '' }}">
    <i class="bi bi-clipboard-data"></i> Assessments
  </a>

  {{-- CBC Curriculum & Planning --}}
  <a href="#cbcMenuSt" data-bs-toggle="collapse" aria-expanded="{{ $cbcActive ? 'true' : 'false' }}" class="{{ $cbcActive ? 'parent-active' : '' }}">
    <i class="bi bi-diagram-3"></i> CBC Curriculum & Planning
  </a>
  <div class="collapse {{ $cbcActive ? 'show' : '' }}" id="cbcMenuSt">
    @if (Route::has('academics.curriculum-designs.index'))
    <a href="{{ route('academics.curriculum-designs.index') }}" class="sublink {{ Request::is('academics/curriculum-designs*') ? 'active' : '' }}">
      <i class="bi bi-layer-forward"></i> Curriculum Designs
    </a>
    @endif
    @if (Route::has('academics.learning-areas.index'))
    <a href="{{ route('academics.learning-areas.index') }}" class="sublink {{ Request::is('academics/learning-areas*') ? 'active' : '' }}">
      <i class="bi bi-book"></i> Learning Areas
    </a>
    @endif
    @if (Route::has('academics.cbc-strands.index'))
    <a href="{{ route('academics.cbc-strands.index') }}" class="sublink {{ Request::is('academics/cbc-strands*') ? 'active' : '' }}">
      <i class="bi bi-diagram-3"></i> CBC Strands
    </a>
    @endif
    @if (Route::has('academics.competencies.index'))
    <a href="{{ route('academics.competencies.index') }}" class="sublink {{ Request::is('academics/competencies*') ? 'active' : '' }}">
      <i class="bi bi-star"></i> Competencies
    </a>
    @endif
    @if (Route::has('academics.schemes-of-work.index'))
    <a href="{{ route('academics.schemes-of-work.index') }}" class="sublink {{ Request::is('academics/schemes-of-work*') ? 'active' : '' }}">
      <i class="bi bi-journal-text"></i> Schemes of Work
    </a>
    @endif
    @if (Route::has('academics.lesson-plans.index'))
    <a href="{{ route('academics.lesson-plans.index') }}" class="sublink {{ Request::is('academics/lesson-plans*') ? 'active' : '' }}">
      <i class="bi bi-calendar-check"></i> Lesson Plans
    </a>
    @endif
    @if (Route::has('academics.portfolio-assessments.index'))
    <a href="{{ route('academics.portfolio-assessments.index') }}" class="sublink {{ Request::is('academics/portfolio-assessments*') ? 'active' : '' }}">
      <i class="bi bi-folder"></i> Portfolio Assessments
    </a>
    @endif
    @if (Route::has('academics.exam-analytics.index'))
    <a href="{{ route('academics.exam-analytics.index') }}" class="sublink {{ Request::is('academics/exam-analytics*') ? 'active' : '' }}">
      <i class="bi bi-graph-up"></i> Exam Analytics
    </a>
    @endif
  </div>

  {{-- Attendance --}}
  @if (can_access('attendance.view') || can_access('attendance.create'))
    <a href="#attendanceMenu" data-bs-toggle="collapse"
       aria-expanded="{{ $attActive ? 'true' : 'false' }}"
       class="{{ $attActive ? 'parent-active' : '' }}">
      <i class="bi bi-calendar-check"></i> Attendance
    </a>
    <div class="collapse {{ $attActive ? 'show' : '' }}" id="attendanceMenu">
      @if (can_access('attendance.create'))
        <a href="{{ route('attendance.mark.form') }}"
           class="sublink {{ Request::is('attendance/mark*') ? 'active' : '' }}">
          <i class="bi bi-pencil"></i> Mark Attendance
        </a>
      @endif
      @if (can_access('attendance.view'))
        <a href="{{ route('attendance.records') }}"
           class="sublink {{ Request::is('attendance/records*') ? 'active' : '' }}">
          <i class="bi bi-journal-text"></i> Reports
        </a>
        <a href="{{ route('attendance.at-risk') }}"
           class="sublink {{ Request::is('attendance/at-risk*') ? 'active' : '' }}">
          <i class="bi bi-exclamation-triangle"></i> At-Risk Students
        </a>
        <a href="{{ route('attendance.consecutive') }}"
           class="sublink {{ Request::is('attendance/consecutive*') ? 'active' : '' }}">
          <i class="bi bi-calendar-x"></i> Consecutive Absences
        </a>
      @endif
      @if (Route::has('attendance.notifications.notify.form'))
        <a href="{{ route('attendance.notifications.notify.form') }}"
           class="sublink {{ Request::is('attendance/notifications/notify*') ? 'active' : '' }}">
          <i class="bi bi-bell"></i> Notify Recipients
        </a>
      @endif
      @if (Route::has('attendance.notifications.index'))
        <a href="{{ route('attendance.notifications.index') }}"
           class="sublink {{ Request::is('attendance/notifications*') && !Request::is('attendance/notifications/notify*') ? 'active' : '' }}">
          <i class="bi bi-people"></i> Recipients
        </a>
      @endif
      @if (Route::has('attendance.reason-codes.index'))
        <a href="{{ route('attendance.reason-codes.index') }}"
           class="sublink {{ Request::is('attendance/reason-codes*') ? 'active' : '' }}">
          <i class="bi bi-tags"></i> Reason Codes
        </a>
      @endif
      @php $seniorAttendanceReportUrl = Route::has('senior_teacher.attendance.report') ? route('senior_teacher.attendance.report') : url('/senior-teacher/my-attendance/report'); @endphp
      <a href="{{ $seniorAttendanceReportUrl }}"
         class="sublink {{ Request::is('senior-teacher/my-attendance/report*') ? 'active' : '' }}">
        <i class="bi bi-person-check"></i> My Attendance Report
      </a>
    </div>
  @endif

  {{-- Exam Marks --}}
  @if (can_access('exam_marks.view') || can_access('exam_marks.create'))
    <a href="#examMarksMenu" data-bs-toggle="collapse"
       aria-expanded="{{ $marksActive ? 'true' : 'false' }}"
       class="{{ $marksActive ? 'parent-active' : '' }}">
      <i class="bi bi-journal-check"></i> Exam Marks
    </a>
    <div class="collapse {{ $marksActive ? 'show' : '' }}" id="examMarksMenu">
      @if (can_access('exam_marks.create'))
        <a href="{{ route('academics.exam-marks.bulk.form') }}"
           class="sublink {{ Request::is('exam-marks/bulk*') ? 'active' : '' }}">
          <i class="bi bi-pencil-square"></i> Enter Marks
        </a>
      @endif
      @if (can_access('exam_marks.view'))
        <a href="{{ route('academics.exam-marks.index') }}"
           class="sublink {{ Request::is('exam-marks') && !Request::is('exam-marks/bulk*') ? 'active' : '' }}">
          <i class="bi bi-list-check"></i> View Marks
        </a>
      @endif
      @if (can_access('exams.view'))
        <a href="{{ route('academics.exams.grading.index') }}"
           class="sublink {{ Request::is('academics/exams/grading*') ? 'active' : '' }}">
          <i class="bi bi-ui-radios-grid"></i> Class grading schemes
        </a>
      @endif
    </div>
  @endif

  {{-- Exams --}}
  @php
    $examsActive = Request::is('academics/exams*')
      || Request::is('academics/exam-types*')
      || Request::is('academics/exam-grades*')
      || Request::is('academics/exam-marks*')
      || Request::is('academics/exams/results*')
      || Request::is('academics/exams/grading*')
      || Request::is('academics/exam-reports*')
      || Request::is('academics/exams/timetable*');
  @endphp

  @if (can_access('exams.view') || can_access('exams.create') || can_access('exams.edit') || can_access('exams.delete') || can_access('exam_types.view'))
    <a href="#examsMenuSt" data-bs-toggle="collapse"
       aria-expanded="{{ $examsActive ? 'true' : 'false' }}"
       class="{{ $examsActive ? 'parent-active' : '' }}">
      <i class="bi bi-file-earmark-text"></i> Exams
    </a>
    <div class="collapse {{ $examsActive ? 'show' : '' }}" id="examsMenuSt">
      @if (can_access('exam_types.view') && Route::has('academics.exams.types.index'))
        <a href="{{ route('academics.exams.types.index') }}"
           class="sublink {{ Request::is('academics/exam-types*') ? 'active' : '' }}">
          <i class="bi bi-sliders2"></i> Exam Types
        </a>
      @endif

      @if (can_access('exams.view') && Route::has('academics.exams.index'))
        <a href="{{ route('academics.exams.index') }}"
           class="sublink {{ Request::is('academics/exams') && !Request::is('academics/exams/*') ? 'active' : '' }}">
          <i class="bi bi-journal-check"></i> Manage Exams
        </a>
      @endif

      @if (can_access('exams.view') && Route::has('academics.exams.grading.index'))
        <a href="{{ route('academics.exams.grading.index') }}"
           class="sublink {{ Request::is('academics/exams/grading*') ? 'active' : '' }}">
          <i class="bi bi-ui-radios-grid"></i> Class grading schemes
        </a>
      @endif

      @if (can_access('exam_marks.create') && Route::has('academics.exam-marks.bulk.form'))
        <a href="{{ route('academics.exam-marks.bulk.form') }}"
           class="sublink {{ Request::is('academics/exam-marks*') ? 'active' : '' }}">
          <i class="bi bi-pencil-square"></i> Enter Marks
        </a>
      @endif

      @if (can_access('exams.view') && Route::has('academics.exams.results.index'))
        <a href="{{ route('academics.exams.results.index') }}"
           class="sublink {{ Request::is('academics/exams/results*') ? 'active' : '' }}">
          <i class="bi bi-bar-chart"></i> Exam Results
        </a>
      @endif

      @if (can_access('exams.view') && Route::has('academics.exam-reports.class-sheet'))
        <a href="{{ route('academics.exam-reports.class-sheet') }}"
           class="sublink {{ Request::is('academics/exam-reports*') ? 'active' : '' }}">
          <i class="bi bi-table"></i> Exam Reports &amp; Analysis
        </a>
      @endif

      @if (can_access('exams.view') && Route::has('academics.exams.timetable'))
        <a href="{{ route('academics.exams.timetable') }}"
           class="sublink {{ Request::is('academics/exams/timetable*') ? 'active' : '' }}">
          <i class="bi bi-printer"></i> Exam Timetable
        </a>
      @endif
    </div>
  @endif

  {{-- Report Cards --}}
  @if (can_access('report_cards.view') || can_access('report_card_skills.edit') || can_access('report_cards.remarks.edit'))
    <a href="#reportMenu" data-bs-toggle="collapse"
       aria-expanded="{{ $reportsActive ? 'true' : 'false' }}"
       class="{{ $reportsActive ? 'parent-active' : '' }}">
      <i class="bi bi-card-text"></i> Report Cards
    </a>
    <div class="collapse {{ $reportsActive ? 'show' : '' }}" id="reportMenu">
      @if (can_access('report_cards.view'))
        <a href="{{ route('academics.report_cards.index') }}"
           class="sublink {{ Request::is('academics/report_cards') && !Request::is('academics/report_cards/*/skills*') && !Request::is('academics/report_cards/generate*') ? 'active' : '' }}">
          <i class="bi bi-list-ul"></i> All Reports
        </a>
        @if (Route::has('academics.report_cards.generate.form'))
        <a href="{{ route('academics.report_cards.generate.form') }}"
           class="sublink {{ Request::is('academics/report_cards/generate*') ? 'active' : '' }}">
          <i class="bi bi-plus-square"></i> Generate Reports
        </a>
        @endif
        @if (Route::has('academics.skills.grade.index'))
        <a href="{{ route('academics.skills.grade.index') }}"
           class="sublink {{ Request::is('academics/skills/grade*') ? 'active' : '' }}">
          <i class="bi bi-award"></i> Skills Grading
        </a>
        @endif
      @endif
      @if (can_access('report_card_skills.edit'))
        @php
          $latestReportCard = \App\Models\Academics\ReportCard::query()->latest()->first();
        @endphp
        @if($latestReportCard)
          <a href="{{ route('academics.report_cards.skills.index', $latestReportCard) }}"
             class="sublink {{ Request::is('academics/report_cards/*/skills*') ? 'active' : '' }}">
            <i class="bi bi-sliders"></i> Skills Editor
          </a>
        @else
          <a href="{{ route('academics.report_cards.index') }}"
             class="sublink">
            <i class="bi bi-sliders"></i> Skills Editor
          </a>
        @endif
      @endif
    </div>
  @endif

  {{-- Homework & Diaries --}}
  @if (can_access('homework.view') || can_access('homework.create') || can_access('diaries.view') || can_access('diaries.create'))
    <a href="#homeworkMenuSt" data-bs-toggle="collapse"
       aria-expanded="{{ $homeworkActive ? 'true' : 'false' }}"
       class="{{ $homeworkActive ? 'parent-active' : '' }}">
      <i class="bi bi-journal"></i> Homework & Diaries
    </a>
    <div class="collapse {{ $homeworkActive ? 'show' : '' }}" id="homeworkMenuSt">
      <a href="{{ route('academics.homework.index') }}"
         class="sublink {{ Request::is('academics/homework*') ? 'active' : '' }}">
        Homework
      </a>
      <a href="{{ route('academics.diaries.index') }}"
         class="sublink {{ Request::is('academics/diaries*') ? 'active' : '' }}">
        Digital Diaries
      </a>
    </div>
  @endif

  {{-- Behaviours --}}
  @if (can_access('student_behaviours.view') || can_access('student_behaviours.create') || can_access('student_behaviours.edit'))
    <a href="#behaviourMenuSt" data-bs-toggle="collapse" aria-expanded="{{ $behaviourActive ? 'true' : 'false' }}" class="{{ $behaviourActive ? 'parent-active' : '' }}">
      <i class="bi bi-emoji-smile"></i> Behaviours
    </a>
    <div class="collapse {{ $behaviourActive ? 'show' : '' }}" id="behaviourMenuSt">
      @if (Route::has('academics.behaviours.index'))
      <a href="{{ route('academics.behaviours.index') }}" class="sublink {{ Request::is('academics/behaviours*') ? 'active' : '' }}">
        Behaviours
      </a>
      @endif
      <a href="{{ route('academics.student-behaviours.index') }}" class="sublink {{ Request::is('academics/student-behaviours*') ? 'active' : '' }}">
        Student Behaviours
      </a>
    </div>
  @endif

  {{-- Swimming --}}
  <a href="#swimmingMenu" data-bs-toggle="collapse"
     aria-expanded="{{ $swimmingActive ? 'true' : 'false' }}"
     class="{{ $swimmingActive ? 'parent-active' : '' }}">
    <i class="bi bi-water"></i> Swimming
  </a>
  <div class="collapse {{ $swimmingActive ? 'show' : '' }}" id="swimmingMenu">
    <a href="{{ route('swimming.attendance.create') }}"
       class="sublink {{ Request::is('swimming/attendance') && !Request::is('swimming/attendance/records*') ? 'active' : '' }}">
      <i class="bi bi-calendar-check"></i> Mark Attendance
    </a>
    <a href="{{ route('swimming.attendance.index') }}"
       class="sublink {{ Request::is('swimming/attendance/records*') ? 'active' : '' }}">
      <i class="bi bi-journal-text"></i> View Records
    </a>
    <a href="{{ route('swimming.wallets.index') }}"
       class="sublink {{ Request::is('swimming/wallets*') ? 'active' : '' }}">
      <i class="bi bi-wallet2"></i> Wallets
    </a>
    <a href="{{ route('swimming.attendance.index') }}"
       class="sublink {{ Request::is('swimming/attendance/records*') ? 'active' : '' }}">
      <i class="bi bi-file-earmark-text"></i> Reports
    </a>
  </div>

  <a href="#activityFeesMenuSt" data-bs-toggle="collapse"
     aria-expanded="{{ $activityFeesActive ? 'true' : 'false' }}"
     class="{{ $activityFeesActive ? 'parent-active' : '' }}">
    <i class="bi bi-trophy"></i> Activity fees
  </a>
  <div class="collapse {{ $activityFeesActive ? 'show' : '' }}" id="activityFeesMenuSt">
    <a href="{{ route('activity-fees.index') }}"
       class="sublink {{ Request::is('activity-fees') && !Request::is('activity-fees/*') ? 'active' : '' }}">
      <i class="bi bi-people"></i> Rosters & attendance
    </a>
    <a href="{{ route('activity-fees.parent-requests.index') }}"
       class="sublink {{ Request::is('activity-fees/parent-requests*') ? 'active' : '' }}">
      <i class="bi bi-person-check"></i> Parent join / leave
    </a>
  </div>

  {{-- Timetable --}}
  @if (can_access('timetable.view'))
    <a href="#timetableMenu" data-bs-toggle="collapse"
       aria-expanded="{{ $timetableActive ? 'true' : 'false' }}"
       class="{{ $timetableActive ? 'parent-active' : '' }}">
      <i class="bi bi-calendar-week"></i> Timetable
    </a>
    <div class="collapse {{ $timetableActive ? 'show' : '' }}" id="timetableMenu">
      <a href="{{ route('senior_teacher.timetable.my-timetable') }}"
         class="sublink {{ Request::is('academics/timetable') && request('teacher_id') ? 'active' : '' }}">
        <i class="bi bi-person"></i> My Timetable
      </a>
      <a href="{{ route('academics.timetable.index') }}"
         class="sublink {{ Request::is('academics/timetable') && !request('teacher_id') && !request('view') ? 'active' : '' }}">
        <i class="bi bi-calendar-week"></i> View Timetable
      </a>
      <a href="{{ route('academics.timetable.index', ['view' => 'classrooms']) }}"
         class="sublink {{ request('view') === 'classrooms' || Request::is('academics/timetable/classroom*') ? 'active' : '' }}">
        <i class="bi bi-building"></i> Classroom Timetable
      </a>
      <a href="{{ route('academics.timetable.index', ['view' => 'teacher']) }}"
         class="sublink {{ request('view') === 'teacher' || Request::is('academics/timetable/teacher*') ? 'active' : '' }}">
        <i class="bi bi-person-badge"></i> Teacher Timetable
      </a>
      @if (Route::has('academics.activities.index'))
      <a href="{{ route('academics.activities.index') }}"
         class="sublink {{ Request::is('academics/activities*') ? 'active' : '' }}">
        <i class="bi bi-trophy"></i> Activities
      </a>
      @endif
    </div>
  @endif
</div>

{{-- Personal Section --}}
<div class="mt-3 pt-3 border-top">
  <div class="nav-section-label">Personal</div>

  {{-- Salary & Payslips --}}
  <a href="{{ route('senior_teacher.salary.index') }}" class="{{ $salaryActive ? 'active' : '' }}">
    <i class="bi bi-cash-stack"></i> Salary & Payslips
  </a>

  {{-- Advances --}}
  <a href="{{ route('senior_teacher.advances.index') }}" 
     class="{{ Request::is('senior-teacher/advances*') ? 'active' : '' }}">
    <i class="bi bi-wallet2"></i> Advance Requests
  </a>

  {{-- Leaves --}}
  <a href="{{ route('senior_teacher.leave.index') }}" class="{{ Request::is('senior-teacher/leaves*') ? 'active' : '' }}">
    <i class="bi bi-calendar-event"></i> My Leaves
  </a>
</div>

{{-- Transport Section --}}
<div class="mt-3 pt-3 border-top">
  <div class="nav-section-label">Transport</div>

  <a href="#transportMenuSt" data-bs-toggle="collapse"
     aria-expanded="{{ $transportActive ? 'true' : 'false' }}"
     class="{{ $transportActive ? 'parent-active' : '' }}">
    <i class="bi bi-truck"></i> Transport
  </a>
  <div class="collapse {{ $transportActive ? 'show' : '' }}" id="transportMenuSt">
    <a href="{{ url('/transport') }}"
       class="sublink {{ Request::is('transport') && !Request::is('transport/*') ? 'active' : '' }}">
      <i class="bi bi-speedometer2"></i> Dashboard
    </a>
    @if (Route::has('transport.daily-list.index'))
    <a href="{{ route('transport.daily-list.index') }}"
       class="sublink {{ Request::is('transport/daily-list*') ? 'active' : '' }}">
      <i class="bi bi-list-check"></i> Daily List
    </a>
    @endif
    @if (Route::has('transport.vehicles.index'))
    <a href="{{ route('transport.vehicles.index') }}"
       class="sublink {{ Request::is('transport/vehicles*') ? 'active' : '' }}">
      <i class="bi bi-bus-front"></i> Vehicles
    </a>
    @endif
    @if (Route::has('transport.trips.index'))
    <a href="{{ route('transport.trips.index') }}"
       class="sublink {{ Request::is('transport/trips*') ? 'active' : '' }}">
      <i class="bi bi-geo"></i> Trips
    </a>
    @endif
    @if (Route::has('transport.dropoffpoints.index'))
    <a href="{{ route('transport.dropoffpoints.index') }}"
       class="sublink {{ Request::is('transport/dropoffpoints*') || Request::is('transport/student-dropoffs*') ? 'active' : '' }}">
      <i class="bi bi-pin-map"></i> Pickup / Drop-offs
    </a>
    @endif
    @if (Route::has('transport.student-assignments.index'))
    <a href="{{ route('transport.student-assignments.index') }}"
       class="sublink {{ Request::is('transport/student-assignments*') ? 'active' : '' }}">
      <i class="bi bi-people"></i> Assignments
    </a>
    @endif
    <a href="{{ route('transport.special-assignments.index') }}"
       class="sublink {{ $specialAssignmentsActive ? 'active' : '' }}">
      <i class="bi bi-star"></i> Special Assignments
    </a>
  </div>
</div>

{{-- Inventory & Requirements Section --}}
<div class="mt-3 pt-3 border-top">
  <div class="nav-section-label">Inventory & Requirements</div>

  <a href="#inventoryMenuSt" data-bs-toggle="collapse"
     aria-expanded="{{ $inventoryActive ? 'true' : 'false' }}"
     class="{{ $inventoryActive ? 'parent-active' : '' }}">
    <i class="bi bi-box-seam"></i> Inventory & Requirements
  </a>
  <div class="collapse {{ $inventoryActive ? 'show' : '' }}" id="inventoryMenuSt">
    @if (Route::has('inventory.items.index'))
    <a href="{{ route('inventory.items.index') }}" class="sublink {{ Request::is('inventory/items*') ? 'active' : '' }}">
      <i class="bi bi-box"></i> Inventory Items
    </a>
    @endif
    @if (Route::has('inventory.requirement-types.index'))
    <a href="{{ route('inventory.requirement-types.index') }}" class="sublink {{ Request::is('inventory/requirement-types*') ? 'active' : '' }}">
      <i class="bi bi-list-check"></i> Requirement Types
    </a>
    @endif
    @if (Route::has('inventory.requirement-templates.index'))
    <a href="{{ route('inventory.requirement-templates.index') }}" class="sublink {{ Request::is('inventory/requirement-templates*') ? 'active' : '' }}">
      <i class="bi bi-file-earmark-text"></i> Requirement Templates
    </a>
    @endif
    @if (Route::has('inventory.requirement-template-assignments.index'))
    <a href="{{ route('inventory.requirement-template-assignments.index') }}" class="sublink {{ Request::is('inventory/requirement-template-assignments*') ? 'active' : '' }}">
      <i class="bi bi-diagram-3"></i> Requirement Assignments
    </a>
    @endif
    <a href="{{ route('inventory.student-requirements.index') }}" class="sublink {{ Request::is('inventory/student-requirements*') && !Request::is('inventory/reports*') ? 'active' : '' }}">
      <i class="bi bi-person-check"></i> Student Requirements
    </a>
    <a href="{{ route('inventory.reports.requirements') }}" class="sublink {{ Request::is('inventory/reports/requirements*') ? 'active' : '' }}">
      <i class="bi bi-clipboard-data"></i> Fulfilment report
    </a>
    <a href="{{ route('inventory.reports.receipts') }}" class="sublink {{ Request::is('inventory/reports/receipts*') ? 'active' : '' }}">
      <i class="bi bi-box-arrow-in-down"></i> What we received
    </a>
    @if (Route::has('inventory.requisitions.index'))
    <a href="{{ route('inventory.requisitions.index') }}" class="sublink {{ Request::is('inventory/requisitions*') ? 'active' : '' }}">
      <i class="bi bi-cart-check"></i> Requisitions
    </a>
    @endif
  </div>
</div>

{{-- Reports Section --}}
@php
  $campusReportsActive = Request::is('reports/heatmaps*') || Request::is('weekly-reports*');
@endphp
<div class="mt-3 pt-3 border-top">
  <div class="nav-section-label">Reports</div>

  {{-- Campus Heatmaps (access by assigned campus) --}}
  <a href="#campusReportsMenu" data-bs-toggle="collapse" aria-expanded="{{ $campusReportsActive ? 'true' : 'false' }}" class="{{ $campusReportsActive ? 'parent-active' : '' }}">
    <i class="bi bi-grid-3x3-gap"></i> Campus & Weekly Reports
  </a>
  <div class="collapse {{ $campusReportsActive ? 'show' : '' }}" id="campusReportsMenu">
    <a href="{{ route('reports.heatmaps.show', 'lower') }}" class="sublink {{ Request::is('reports/heatmaps/lower*') ? 'active' : '' }}">
      <i class="bi bi-thermometer-half"></i> Heatmap – Lower
    </a>
    <a href="{{ route('reports.heatmaps.show', 'upper') }}" class="sublink {{ Request::is('reports/heatmaps/upper*') ? 'active' : '' }}">
      <i class="bi bi-thermometer-half"></i> Heatmap – Upper
    </a>
    <a href="{{ route('reports.class-reports.index') }}" class="sublink {{ Request::is('weekly-reports/class-reports*') ? 'active' : '' }}">
      <i class="bi bi-journal-text"></i> Class Reports
    </a>
    <a href="{{ route('reports.subject-reports.index') }}" class="sublink {{ Request::is('weekly-reports/subject-reports*') ? 'active' : '' }}">
      <i class="bi bi-book"></i> Subject Reports
    </a>
    <a href="{{ route('reports.staff-weekly.index') }}" class="sublink {{ Request::is('weekly-reports/staff-weekly*') ? 'active' : '' }}">
      <i class="bi bi-person-lines-fill"></i> Staff Weekly
    </a>
    <a href="{{ route('reports.student-followups.index') }}" class="sublink {{ Request::is('weekly-reports/student-followups*') ? 'active' : '' }}">
      <i class="bi bi-person-check"></i> Student Followups
    </a>
    <a href="{{ route('reports.operations-facilities.index') }}" class="sublink {{ Request::is('weekly-reports/operations-facilities*') ? 'active' : '' }}">
      <i class="bi bi-building-gear"></i> Operations & Facilities
    </a>
  </div>

  {{-- HR Reports --}}
  <a href="{{ route('hr.reports.index') }}" class="{{ $hrReportsActive ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text"></i> HR Reports
  </a>
</div>

{{-- Communication Section --}}
<div class="mt-3 pt-3 border-top">
  <div class="nav-section-label">Communication</div>

  {{-- Announcements --}}
  <a href="{{ route('senior_teacher.announcements.index') }}" 
     class="{{ $announcementsActive ? 'active' : '' }}">
    <i class="bi bi-megaphone"></i> Announcements
  </a>

  {{-- Events Calendar --}}
  <a href="{{ route('events.index') }}" class="{{ $eventsActive ? 'active' : '' }}">
    <i class="bi bi-calendar3"></i> Events Calendar
  </a>
</div>

