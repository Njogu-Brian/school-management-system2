{{-- Teacher-only navigation - Optimized arrangement --}}
@php
  $attActive = Request::is('attendance*');
  $marksActive = Request::is('exam-marks*');
  $reportsActive = Request::is('academics/report_cards*');
  $homeworkActive = Request::is('academics/homework*');
  $diariesActive = Request::is('academics/diaries*');
  $behaviourActive = Request::is('academics/student-behaviours*');
  $curriculumActive = Request::is('academics/curriculum-designs*') || Request::is('academics/schemes-of-work*') || Request::is('academics/lesson-plans*') || Request::is('academics/portfolio-assessments*');
  $studentsActive = Request::is('teacher/my-students*');
  $transportActive = Request::is('teacher/transport*');
  $salaryActive = Request::is('teacher/salary*');
  $leaveActive = Request::is('teacher/leaves*');
  $timetableActive = Request::is('academics/timetable*') || Request::is('teacher/timetable*');
  $announcementsActive = Request::is('teacher/announcements*');
  $eventsActive = Request::is('teacher/events*') || Request::is('events*');
  $teacherInventoryActive = Request::is('inventory/student-requirements*') || Request::is('inventory/requisitions*');
@endphp

{{-- Dashboard --}}
<a href="{{ route('teacher.dashboard') }}"
   class="{{ Request::is('teacher/home') ? 'active' : '' }}">
  <i class="bi bi-speedometer2"></i> Dashboard
</a>

{{-- My Profile --}}
<a href="{{ route('staff.profile.show') }}" class="{{ Request::is('my/profile') ? 'active' : '' }}">
  <i class="bi bi-person-circle"></i> My Profile
</a>

{{-- My Students --}}
<a href="{{ route('teacher.students.index') }}" class="{{ $studentsActive ? 'active' : '' }}">
  <i class="bi bi-people"></i> My Students
</a>

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
        <i class="bi bi-list-check"></i> My Class Marks
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
    <a href="{{ route('teacher.timetable.my-timetable') }}"
       class="sublink {{ Request::is('academics/timetable') && request('teacher_id') ? 'active' : '' }}">
      <i class="bi bi-person"></i> My Timetable
    </a>
    <a href="{{ route('academics.timetable.index') }}"
       class="sublink {{ Request::is('academics/timetable') && !request('teacher_id') ? 'active' : '' }}">
      <i class="bi bi-building"></i> Class Timetables
    </a>
  </div>
@endif

{{-- Transport --}}
<a href="{{ route('teacher.transport.index') }}" class="{{ $transportActive ? 'active' : '' }}">
  <i class="bi bi-truck"></i> School Transport
</a>

{{-- Salary & Payslips --}}
<a href="{{ route('teacher.salary.index') }}" class="{{ $salaryActive ? 'active' : '' }}">
  <i class="bi bi-cash-stack"></i> Salary & Payslips
</a>

{{-- Advances --}}
<a href="{{ route('teacher.advances.index') }}" class="{{ Request::is('teacher/advances*') ? 'active' : '' }}">
  <i class="bi bi-wallet2"></i> Advance Requests
</a>

{{-- Leaves --}}
<a href="{{ route('teacher.leave.index') }}" class="{{ $leaveActive ? 'active' : '' }}">
  <i class="bi bi-calendar-event"></i> Leaves
</a>

{{-- Announcements --}}
<a href="{{ route('teacher.announcements.index') }}" class="{{ $announcementsActive ? 'active' : '' }}">
  <i class="bi bi-megaphone"></i> Announcements
</a>

{{-- Events Calendar --}}
<a href="{{ route('events.index') }}" class="{{ $eventsActive ? 'active' : '' }}">
  <i class="bi bi-calendar3"></i> Events Calendar
</a>

{{-- CBC Curriculum & Planning --}}
@php 
  $cbcActive = Request::is('academics/learning-areas*') 
    || Request::is('academics/competencies*')
    || Request::is('academics/cbc-strands*')
    || Request::is('academics/cbc-substrands*')
    || Request::is('academics/curriculum-designs*')
    || Request::is('academics/schemes-of-work*') 
    || Request::is('academics/lesson-plans*')
    || Request::is('academics/portfolio-assessments*');
@endphp
@if (can_access('schemes_of_work.view') || can_access('lesson_plans.view') || can_access('portfolio_assessments.view') || can_access('curriculum_designs.view'))
  <a href="#cbcMenu" data-bs-toggle="collapse" aria-expanded="{{ $cbcActive ? 'true' : 'false' }}" class="{{ $cbcActive ? 'parent-active' : '' }}">
    <i class="bi bi-diagram-3"></i> CBC Curriculum & Planning
  </a>
  <div class="collapse {{ $cbcActive ? 'show' : '' }}" id="cbcMenu">
    @if (can_access('curriculum_designs.view'))
      <a href="{{ route('academics.curriculum-designs.index') }}" class="sublink {{ Request::is('academics/curriculum-designs*') ? 'active' : '' }}">
        <i class="bi bi-layer-forward"></i> Curriculum Designs
      </a>
    @endif
    @if (can_access('schemes_of_work.view'))
      <a href="{{ route('academics.schemes-of-work.index') }}" class="sublink {{ Request::is('academics/schemes-of-work') ? 'active' : '' }}">
        <i class="bi bi-journal-text"></i> Schemes of Work
      </a>
    @endif
    @if (can_access('lesson_plans.view'))
      <a href="{{ route('academics.lesson-plans.index') }}" class="sublink {{ Request::is('academics/lesson-plans') ? 'active' : '' }}">
        <i class="bi bi-calendar-check"></i> Lesson Plans
      </a>
    @endif
    @if (can_access('portfolio_assessments.view'))
      <a href="{{ route('academics.portfolio-assessments.index') }}" class="sublink {{ Request::is('academics/portfolio-assessments*') ? 'active' : '' }}">
        <i class="bi bi-folder"></i> Portfolio Assessments
      </a>
    @endif
  </div>
@endif

{{-- Inventory & Requirements --}}
@if (can_access('inventory.view') || can_access('student_requirements.view'))
  <a href="#teacherInventoryMenu" data-bs-toggle="collapse"
     aria-expanded="{{ $teacherInventoryActive ? 'true' : 'false' }}"
     class="{{ $teacherInventoryActive ? 'parent-active' : '' }}">
    <i class="bi bi-box-seam"></i> Inventory & Requirements
  </a>
  <div class="collapse {{ $teacherInventoryActive ? 'show' : '' }}" id="teacherInventoryMenu">
    <a href="{{ route('inventory.student-requirements.collect') }}"
       class="sublink {{ Request::is('inventory/student-requirements/collect*') ? 'active' : '' }}">
      <i class="bi bi-clipboard-check"></i> Collect Requirements
    </a>
    <a href="{{ route('inventory.student-requirements.index') }}"
       class="sublink {{ Request::is('inventory/student-requirements') ? 'active' : '' }}">
      <i class="bi bi-list-task"></i> My Collections
    </a>
    <a href="{{ route('inventory.requisitions.create') }}"
       class="sublink {{ Request::is('inventory/requisitions/create') ? 'active' : '' }}">
      <i class="bi bi-plus-circle"></i> New Requisition
    </a>
    <a href="{{ route('inventory.requisitions.index') }}"
       class="sublink {{ Request::is('inventory/requisitions') && !Request::is('inventory/requisitions/create') ? 'active' : '' }}">
      <i class="bi bi-ui-checks"></i> Track Requisitions
    </a>
  </div>
@endif
