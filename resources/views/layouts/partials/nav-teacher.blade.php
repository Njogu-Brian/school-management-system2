{{-- Teacher-only navigation --}}
@php
  $attActive = Request::is('attendance*');
  $marksActive = Request::is('exam-marks*');
  $reportsActive = Request::is('academics/report_cards*');
  $homeworkActive = Request::is('academics/homework*');
  $diariesActive = Request::is('academics/diaries*');
  $behaviourActive = Request::is('academics/student-behaviours*');
  $curriculumActive = Request::is('academics/curriculum-designs*');
@endphp

<a href="{{ route('teacher.dashboard') }}"
   class="{{ Request::is('teacher/home') ? 'active' : '' }}">
  <i class="bi bi-easel2"></i> Teacher Dashboard
</a>

<a href="{{ route('staff.profile.show') }}" class="{{ Request::is('my/profile') ? 'active' : '' }}">
  <i class="bi bi-person-circle"></i>
  <span>My Profile</span>
</a>

@if (can_access('curriculum_designs.view') || can_access('curriculum_designs.view_own'))
  <a href="{{ route('academics.curriculum-designs.index') }}" class="{{ $curriculumActive ? 'active' : '' }}">
    <i class="bi bi-layer-forward"></i> Curriculum Designs
  </a>
@endif

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
         class="sublink {{ Request::is('exam-marks') ? 'active' : '' }}">
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
         class="{{ Request::is('academics/report_cards') ? 'active' : '' }}">
        All Reports
      </a>
    @endif
    @if (can_access('report_card_skills.edit'))
      <a href="{{ route('academics.report_cards.skills.index', \App\Models\Academics\ReportCard::query()->latest()->value('id') ?? 1) }}"
         class="{{ Request::is('academics/report_cards/*/skills*') ? 'active' : '' }}">
        Skills Editor
      </a>
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

{{-- Digital Diaries --}}
@if (can_access('diaries.view') || can_access('diaries.create') || can_access('diaries.edit'))
  <a href="{{ route('academics.diaries.index') }}"
     class="{{ $diariesActive ? 'active' : '' }}">
    <i class="bi bi-journals"></i> Digital Diaries
  </a>
@endif

{{-- Student Behaviour --}}
@if (can_access('student_behaviours.view') || can_access('student_behaviours.create') || can_access('student_behaviours.edit'))
  <a href="{{ route('academics.student-behaviours.index') }}"
     class="{{ $behaviourActive ? 'active' : '' }}">
    <i class="bi bi-emoji-smile"></i> Student Behaviour
  </a>
@endif

{{-- Timetable --}}
@php $timetableActive = Request::is('academics/timetable*'); @endphp
@if (can_access('timetable.view'))
  <a href="{{ route('academics.timetable.index') }}"
     class="{{ $timetableActive ? 'active' : '' }}">
    <i class="bi bi-calendar-week"></i> My Timetable
  </a>
@endif

{{-- Schemes of Work & Lesson Plans --}}
@php 
  $planningActive = Request::is('academics/schemes-of-work*') 
    || Request::is('academics/lesson-plans*')
    || Request::is('academics/portfolio-assessments*');
@endphp
@if (can_access('schemes_of_work.view') || can_access('lesson_plans.view') || can_access('portfolio_assessments.view'))
  <a href="#planningMenu" data-bs-toggle="collapse" aria-expanded="{{ $planningActive ? 'true' : 'false' }}" class="{{ $planningActive ? 'parent-active' : '' }}">
    <i class="bi bi-calendar-check"></i> Planning & Assessment
  </a>
  <div class="collapse {{ $planningActive ? 'show' : '' }}" id="planningMenu">
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