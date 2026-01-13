{{-- Senior Teacher navigation - Teacher permissions + Supervisory functions --}}
@php
  $attActive = Request::is('attendance*');
  $marksActive = Request::is('exam-marks*');
  $reportsActive = Request::is('academics/report_cards*');
  $homeworkActive = Request::is('academics/homework*');
  $diariesActive = Request::is('academics/diaries*');
  $behaviourActive = Request::is('academics/student-behaviours*');
  $studentsActive = Request::is('senior-teacher/students*');
  $supervisedClassActive = Request::is('senior-teacher/supervised-classrooms*');
  $supervisedStaffActive = Request::is('senior-teacher/supervised-staff*');
  $feeBalancesActive = Request::is('senior-teacher/fee-balances*');
  $salaryActive = Request::is('senior-teacher/salary*');
  $leaveActive = Request::is('senior-teacher/leaves*');
  $timetableActive = Request::is('academics/timetable*') || Request::is('senior-teacher/timetable*');
  $announcementsActive = Request::is('senior-teacher/announcements*');
  $eventsActive = Request::is('events*');
@endphp

{{-- Dashboard --}}
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
  <div class="text-muted small fw-bold px-3 mb-2">Supervisory</div>
  
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
  
  {{-- Students (All) --}}
  <a href="{{ route('senior_teacher.students.index') }}" 
     class="{{ $studentsActive ? 'active' : '' }}">
    <i class="bi bi-people"></i> All Students
  </a>
  
  {{-- Fee Balances --}}
  <a href="{{ route('senior_teacher.fee_balances') }}" 
     class="{{ $feeBalancesActive ? 'active' : '' }}">
    <i class="bi bi-currency-exchange"></i> Fee Balances
  </a>
</div>

{{-- Teaching Section --}}
<div class="mt-3 pt-3 border-top">
  <div class="text-muted small fw-bold px-3 mb-2">Teaching & Academics</div>

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
          <i class="bi bi-journal-text"></i> View Records
        </a>
      @endif
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
           class="sublink {{ Request::is('academics/report_cards') && !Request::is('academics/report_cards/*/skills*') ? 'active' : '' }}">
          <i class="bi bi-list-ul"></i> All Reports
        </a>
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

  {{-- Homework --}}
  @if (can_access('homework.view') || can_access('homework.create') || can_access('homework.edit'))
    <a href="{{ route('academics.homework.index') }}"
       class="{{ $homeworkActive ? 'active' : '' }}">
      <i class="bi bi-journal"></i> Homework
    </a>
  @endif

  {{-- Student Behaviour --}}
  @if (can_access('student_behaviours.view') || can_access('student_behaviours.create') || can_access('student_behaviours.edit'))
    <a href="{{ route('academics.student-behaviours.index') }}"
       class="{{ $behaviourActive ? 'active' : '' }}">
      <i class="bi bi-emoji-smile"></i> Student Behaviour
    </a>
  @endif

  {{-- Digital Diaries --}}
  @if (can_access('diaries.view') || can_access('diaries.create') || can_access('diaries.edit'))
    <a href="{{ route('academics.diaries.index') }}"
       class="{{ $diariesActive ? 'active' : '' }}">
      <i class="bi bi-journals"></i> Digital Diaries
    </a>
  @endif

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
         class="sublink {{ Request::is('academics/timetable') && !request('teacher_id') ? 'active' : '' }}">
        <i class="bi bi-building"></i> Class Timetables
      </a>
    </div>
  @endif
</div>

{{-- Personal Section --}}
<div class="mt-3 pt-3 border-top">
  <div class="text-muted small fw-bold px-3 mb-2">Personal</div>

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
  <a href="{{ route('senior_teacher.leave.index') }}" class="{{ $leaveActive ? 'active' : '' }}">
    <i class="bi bi-calendar-event"></i> Leaves
  </a>
</div>

{{-- Communication Section --}}
<div class="mt-3 pt-3 border-top">
  <div class="text-muted small fw-bold px-3 mb-2">Communication</div>

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

