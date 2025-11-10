        {{-- TEMPORARY: Dashboard Links (for testing all roles) --}}
<!-- <li class="nav-item mt-3">
    <span class="text-muted small fw-bold px-3">Dashboards (Testing)</span>
</li> -->

<li>
    <a href="{{ route('admin.dashboard') }}"
       class="{{ Request::is('admin/home') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i>
        <span>Admin Dashboard</span>
    </a>
</li>

<li>
    <a href="{{ route('teacher.dashboard') }}"
       class="{{ Request::is('teacher/home') ? 'active' : '' }}">
        <i class="bi bi-easel2"></i>
        <span>Teacher Dashboard</span>
    </a>
</li>

<li>
    <a href="{{ route('student.dashboard') }}"
       class="{{ Request::is('student/home') ? 'active' : '' }}">
        <i class="bi bi-person-badge"></i>
        <span>Student Dashboard</span>
    </a>
</li>

<li>
    <a href="{{ route('parent.dashboard') }}"
       class="{{ Request::is('parent/home') ? 'active' : '' }}">
        <i class="bi bi-people"></i>
        <span>Parent Dashboard</span>
    </a>
</li>

<li>
    <a href="{{ route('finance.dashboard') }}"
       class="{{ Request::is('finance/home') ? 'active' : '' }}">
        <i class="bi bi-cash-stack"></i>
        <span>Finance Dashboard</span>
    </a>
</li>

<li>
    <a href="{{ route('transport.dashboard') }}"
       class="{{ Request::is('transport/home') ? 'active' : '' }}">
        <i class="bi bi-truck"></i>
        <span>Transport Dashboard</span>
    </a>
</li>
<!-- Profile -->
<li>
    <a href="{{ route('staff.profile.show') }}"
       class="{{ Request::is('my/profile') ? 'active' : '' }}">
        <i class="bi bi-person-circle"></i>
        <span>My Profile</span>
    </a>
</li>

<!-- Students -->
@php 
$studentsActive = Request::is('students*') || Request::is('online-admissions*');
$studentRecordsActive = Request::is('students/*/medical-records*') || Request::is('students/*/disciplinary-records*') || Request::is('students/*/activities*') || Request::is('students/*/academic-history*');
@endphp
<a href="#studentsMenu" data-bs-toggle="collapse" aria-expanded="{{ $studentsActive ? 'true' : 'false' }}" class="{{ $studentsActive ? 'parent-active' : '' }}">
    <i class="bi bi-person"></i> Students
</a>
<div class="collapse {{ $studentsActive ? 'show' : '' }}" id="studentsMenu">
    <a href="{{ route('students.index') }}" class="{{ Request::is('students') && !$studentRecordsActive ? 'active' : '' }}">Student Details</a>
    <a href="{{ route('students.create') }}" class="{{ Request::is('students/create') ? 'active' : '' }}">Admissions</a>
    <a href="{{ route('students.bulk') }}" class="{{ Request::is('students/bulk*') ? 'active' : '' }}">Bulk Upload</a>
    <a href="{{ route('families.index') }}" class="{{ Request::is('families*') ? 'active' : '' }}"><i class="bi bi-people"></i> Families (Siblings)</a>
    <a href="{{ route('online-admissions.index') }}" class="{{ Request::is('online-admissions*') && !Request::is('online-admissions/apply*') ? 'active' : '' }}">
        <i class="bi bi-globe"></i> Online Admissions
    </a>
    <a href="{{ route('online-admissions.public-form') }}" target="_blank" class="text-muted small" style="padding-left: 2rem; font-size: 0.85rem;">
        <i class="bi bi-box-arrow-up-right"></i> View Public Form
    </a>
    @if($studentRecordsActive)
    <div class="px-3 py-2 mt-2 bg-light rounded">
        <small class="text-muted fw-bold d-block mb-1">Student Records</small>
        <small class="text-muted d-block">Access from student profile → Records tabs</small>
    </div>
    @endif
</div>

<!-- Attendance -->
@php $isAttendanceActive = Request::is('attendance*'); @endphp
<a href="#attendanceMenu" data-bs-toggle="collapse" 
aria-expanded="{{ $isAttendanceActive ? 'true' : 'false' }}"
class="{{ $isAttendanceActive ? 'parent-active' : '' }}">
<i class="bi bi-calendar-check"></i><span> Attendance</span>
</a>
<div class="collapse {{ $isAttendanceActive ? 'show' : '' }}" id="attendanceMenu">
    <a href="{{ route('attendance.mark.form') }}" 
    class="sublink {{ Request::is('attendance/mark*') ? 'active' : '' }}">
    <i class="bi bi-pencil"></i> Mark Attendance
    </a>
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
    <a href="{{ route('attendance.notifications.notify.form') }}" 
    class="sublink {{ Request::is('attendance/notifications/notify*') ? 'active' : '' }}">
    <i class="bi bi-bell"></i> Notify Recipients
    </a>
    <a href="{{ route('attendance.notifications.index') }}" 
    class="sublink {{ Request::is('attendance/notifications*') ? 'active' : '' }}">
    <i class="bi bi-people"></i> Recipients
    </a>
    <a href="{{ route('attendance.reason-codes.index') }}" 
    class="sublink {{ Request::is('attendance/reason-codes*') ? 'active' : '' }}">
    <i class="bi bi-tags"></i> Reason Codes
    </a>
</div>

{{-- Academics --}}
@php
    $academicsActive = Request::is('academics/classrooms*')
        || Request::is('academics/streams*')
        || Request::is('academics/subjects*')
        || Request::is('academics/subject_groups*');
@endphp
<a href="#academicsMenu" data-bs-toggle="collapse" aria-expanded="{{ $academicsActive ? 'true' : 'false' }}" class="{{ $academicsActive ? 'parent-active' : '' }}">
    <i class="bi bi-journal-bookmark"></i> Academics
</a>
<div class="collapse {{ $academicsActive ? 'show' : '' }}" id="academicsMenu">
    <a href="{{ route('academics.classrooms.index') }}" class="{{ Request::is('academics/classrooms*') ? 'active' : '' }}">Classrooms</a>
    <a href="{{ route('academics.streams.index') }}" class="{{ Request::is('academics/streams*') ? 'active' : '' }}">Streams</a>
    <a href="{{ route('academics.subjects.index') }}" class="{{ Request::is('academics/subjects*') ? 'active' : '' }}">Subjects</a>
    <a href="{{ route('academics.subject_groups.index') }}" class="{{ Request::is('academics/subject_groups*') ? 'active' : '' }}">Subject Groups</a>
</div>

{{-- Exams --}}
@php
    $examsActive = Request::is('academics/exams*')
        || Request::is('academics/exam-groups*')
        || Request::is('academics/exam-types*')
        || Request::is('academics/exam-grades*')
        || Request::is('academics/exam-marks*')
        || Request::is('academics/exams/results*')
        || Request::is('academics/exams/timetable');
@endphp

<a href="#examsMenu" data-bs-toggle="collapse"
aria-expanded="{{ $examsActive ? 'true' : 'false' }}"
class="{{ $examsActive ? 'parent-active' : '' }}">
<i class="bi bi-file-earmark-text"></i> Exams
</a>

<div class="collapse {{ $examsActive ? 'show' : '' }}" id="examsMenu">
    {{-- New exam structure --}}
    <a href="{{ route('academics.exams.groups.index') }}"
    class="sublink {{ Request::is('academics/exam-groups*') ? 'active' : '' }}">
        <i class="bi bi-collection"></i> Exam Groups
    </a>

    <a href="{{ route('academics.exams.types.index') }}"
    class="sublink {{ Request::is('academics/exam-types*') ? 'active' : '' }}">
        <i class="bi bi-sliders2"></i> Exam Types
    </a>

    <a href="{{ route('academics.exams.index') }}"
    class="sublink {{ Request::is('academics/exams') ? 'active' : '' }}">
        <i class="bi bi-journal-check"></i> Manage Exams
    </a>

    <a href="{{ route('academics.exam-marks.bulk.form') }}"
    class="sublink {{ Request::is('academics/exam-marks*') ? 'active' : '' }}">
        <i class="bi bi-pencil-square"></i> Enter Marks
    </a>

    <a href="{{ route('academics.exams.results.index') }}"
    class="sublink {{ Request::is('exams/results*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart"></i> Exam Results
    </a>

    <a href="{{ route('academics.exams.timetable') }}"
    class="sublink {{ Request::is('academics/exams/timetable') ? 'active' : '' }}">
        <i class="bi bi-printer"></i> Exam Timetable
    </a>
</div>

{{-- Homework & Diaries --}}
@php $homeworkActive = Request::is('academics/homework*') || Request::is('academics/diaries*'); @endphp
<a href="#homeworkMenu" data-bs-toggle="collapse" aria-expanded="{{ $homeworkActive ? 'true' : 'false' }}" class="{{ $homeworkActive ? 'parent-active' : '' }}">
    <i class="bi bi-journal"></i> Homework & Diaries
</a>
<div class="collapse {{ $homeworkActive ? 'show' : '' }}" id="homeworkMenu">
    <a href="{{ route('academics.homework.index') }}" class="{{ Request::is('academics/homework*') ? 'active' : '' }}">Homework</a>
    <a href="{{ route('academics.diaries.index') }}" class="{{ Request::is('academics/diaries*') ? 'active' : '' }}">Digital Diaries</a>
</div>

{{-- Report Cards --}}
@php
    $reportActive = Request::is('academics/report_cards*')
        || Request::is('academics/skills/grade*');
@endphp
<a href="#reportMenu" data-bs-toggle="collapse"
aria-expanded="{{ $reportActive ? 'true' : 'false' }}"
class="{{ $reportActive ? 'parent-active' : '' }}">
    <i class="bi bi-card-text"></i> Report Cards
</a>
<div class="collapse {{ $reportActive ? 'show' : '' }}" id="reportMenu">
    <a href="{{ route('academics.report_cards.index') }}"
    class="{{ Request::is('academics/report_cards') ? 'active' : '' }}">
        Report Cards
    </a>

    <a href="{{ route('academics.report_cards.generate.form') }}"
    class="{{ Request::is('academics/report_cards/generate*') ? 'active' : '' }}">
        Generate Reports
    </a>

    <a href="{{ route('academics.skills.grade.index') }}"
    class="{{ Request::is('academics/skills/grade*') ? 'active' : '' }}">
        Skills Grading
    </a>

    {{-- Optional per-report skills editor --}}
    <a href="{{ route('academics.report_cards.skills.index', \App\Models\Academics\ReportCard::query()->latest()->value('id') ?? 1) }}"
    class="{{ Request::is('academics/report_cards/*/skills*') ? 'active' : '' }}">
    Report Card Skills (per report)
    </a>
</div>

{{-- Behaviours --}}
@php $behaviourActive = Request::is('academics/behaviours*') || Request::is('academics/student-behaviours*'); @endphp
<a href="#behaviourMenu" data-bs-toggle="collapse" aria-expanded="{{ $behaviourActive ? 'true' : 'false' }}" class="{{ $behaviourActive ? 'parent-active' : '' }}">
    <i class="bi bi-emoji-smile"></i> Behaviours
</a>
<div class="collapse {{ $behaviourActive ? 'show' : '' }}" id="behaviourMenu">
    <a href="{{ route('academics.behaviours.index') }}" class="{{ Request::is('academics/behaviours*') ? 'active' : '' }}">Behaviours</a>
    <a href="{{ route('academics.student-behaviours.index') }}" class="{{ Request::is('academics/student-behaviours*') ? 'active' : '' }}">Student Behaviours</a>
</div>
        

<!-- Finance -->
{{-- Finance --}}
@php
    $financeActive = Request::is('finance*') || Request::is('voteheads*');
@endphp
<a href="#financeMenu" data-bs-toggle="collapse"aria-expanded="{{ $financeActive ? 'true' : 'false' }}"class="{{ $financeActive ? 'parent-active' : '' }}"><i class="bi bi-currency-dollar"></i> Finance</a>
<div class="collapse {{ $financeActive ? 'show' : '' }}" id="financeMenu">
    <a href="{{ route('finance.voteheads.index') }}" class="{{ Request::is('finance/voteheads*') ? 'active' : '' }}">Voteheads</a>
    <a href="{{ route('finance.fee-structures.manage') }}"class="{{ Request::is('finance/fee-structures*') ? 'active' : '' }}">Fee Structures</a>
    <a href="{{ route('finance.invoices.index') }}"class="{{ Request::is('finance/invoices*') ? 'active' : '' }}">Invoices</a>
    <a href="{{ route('finance.optional_fees.index') }}"class="{{ Request::is('finance/optional-fees*') || Request::is('finance/optional_fees*') ? 'active' : '' }}">Optional Fees</a>
    <a href="{{ route('finance.posting.index') }}"class="{{ Request::is('finance/posting*') ? 'active' : '' }}">Posting (Pending → Active)</a>
    <a href="{{ route('finance.journals.create') }}"class="{{ Request::is('finance/journals*') || Request::is('finance/credits*') || Request::is('finance/debits*') ? 'active' : '' }}">Credit / Debit Adjustments</a>
</div>

<!-- HR -->
@php
  $hrActive = Request::is('staff*')
    || Request::is('settings/access-lookups*')
    || Request::is('hr/profile-requests*');   // <<< add this
@endphp


<a href="#hrMenu" data-bs-toggle="collapse"
   aria-expanded="{{ $hrActive ? 'true' : 'false' }}"
   class="{{ $hrActive ? 'parent-active' : '' }}">
  <i class="bi bi-briefcase"></i> HR
</a>
<div class="collapse {{ $hrActive ? 'show' : '' }}" id="hrMenu">
  <a href="{{ route('staff.index') }}" class="{{ Request::is('staff*') ? 'active' : '' }}">
    <i class="bi bi-people"></i> Staff
  </a>

  <a href="{{ route('settings.access_lookups') }}" class="{{ Request::is('settings/access-lookups*') ? 'active' : '' }}">
    <i class="bi bi-shield-lock"></i> Roles & Lookups
  </a>

  {{-- Profile Edit Approvals (Admin only) --}}
  @if(auth()->check() && auth()->user()->hasAnyRole(['Super Admin','Admin']))
    @php
      // Safe badge count (works even if the model namespace changes later)
      $profilePendingCount = 0;
      try {
          if (class_exists(\App\Models\Hr\ProfileChange::class)) {
              $profilePendingCount = \App\Models\Hr\ProfileChange::where('status','pending')->count();
          }
      } catch (\Throwable $e) { /* ignore */ }
    @endphp

    <a href="{{ route('hr.profile_requests.index') }}"
       class="{{ Request::is('hr/profile-requests*') ? 'active' : '' }}">
      <i class="bi bi-check2-square"></i> Profile Edit Approvals
      @if($profilePendingCount > 0)
        <span class="badge bg-danger ms-2">{{ $profilePendingCount }}</span>
      @endif
    </a>
  @endif
</div>



<!-- Transport -->
@php $isTransportActive = Request::is('transport*'); @endphp
<a href="#transportMenu" data-bs-toggle="collapse" 
aria-expanded="{{ $isTransportActive ? 'true' : 'false' }}"
class="{{ $isTransportActive ? 'parent-active' : '' }}">
<i class="bi bi-truck"></i><span> Transport</span>
</a>
<div class="collapse {{ $isTransportActive ? 'show' : '' }}" id="transportMenu">
    <a href="{{ route('transport.vehicles.index') }}" 
    class="sublink {{ Request::is('transport/vehicles*') ? 'active' : '' }}">
    <i class="bi bi-bus-front"></i> Vehicles
    </a>
    <a href="{{ route('transport.routes.index') }}" 
    class="sublink {{ Request::is('transport/routes*') ? 'active' : '' }}">
    <i class="bi bi-map"></i> Routes
    </a>
    <a href="{{ route('transport.trips.index') }}" 
    class="sublink {{ Request::is('transport/trips*') ? 'active' : '' }}">
    <i class="bi bi-geo"></i> Trips
    </a>
    <a href="{{ route('transport.student-assignments.index') }}" 
    class="sublink {{ Request::is('transport/student-assignments*') ? 'active' : '' }}">
    <i class="bi bi-people"></i> Assignments
    </a>
</div>

<!-- Communication -->
@php $isCommunicationActive = Request::is('communication*') || Request::is('announcements*'); @endphp
<a href="#communicationMenu" data-bs-toggle="collapse" 
aria-expanded="{{ $isCommunicationActive ? 'true' : 'false' }}"
class="{{ $isCommunicationActive ? 'parent-active' : '' }}">
<i class="bi bi-chat-dots"></i><span> Communication</span>
</a>
<div class="collapse {{ $isCommunicationActive ? 'show' : '' }}" id="communicationMenu">
    <a href="{{ route('communication.send.email') }}" 
    class="sublink {{ Request::is('communication/send-email*') ? 'active' : '' }}">
    <i class="bi bi-envelope"></i> Send Email
    </a>
    <a href="{{ route('communication.send.sms') }}" 
    class="sublink {{ Request::is('communication/send-sms*') ? 'active' : '' }}">
    <i class="bi bi-chat"></i> Send SMS
    </a>
    <a href="{{ route('communication-templates.index') }}" 
    class="sublink {{ Request::is('communication/communication-templates*') ? 'active' : '' }}">
    <i class="bi bi-layer-forward"></i> Templates
    </a>
    <a href="{{ route('communication.logs') }}" 
    class="sublink {{ Request::is('communication/logs*') ? 'active' : '' }}">
    <i class="bi bi-clock-history"></i> Logs
    </a>
    <a href="{{ route('announcements.index') }}" 
    class="sublink {{ Request::is('communication/announcements*') ? 'active' : '' }}">
    <i class="bi bi-megaphone"></i> Announcements
    </a>
</div>

<!-- Settings -->
@php $isSettingsActive = Request::is('settings*'); @endphp
<a href="#settingsMenu" data-bs-toggle="collapse" 
aria-expanded="{{ $isSettingsActive ? 'true' : 'false' }}"
class="{{ $isSettingsActive ? 'parent-active' : '' }}">
<i class="bi bi-gear"></i><span> Settings</span>
</a>
<div class="collapse {{ $isSettingsActive ? 'show' : '' }}" id="settingsMenu">
    <a href="{{ route('settings.index') }}" 
    class="sublink {{ Request::is('settings') ? 'active' : '' }}">
    <i class="bi bi-building"></i> General Info
    </a>
    <a href="{{ route('settings.access_lookups') }}" 
    class="sublink {{ Request::is('settings/access-lookups*') ? 'active' : '' }}">
    <i class="bi bi-shield-lock"></i> Access & Lookups
    </a>
    <a href="{{ route('settings.academic.index') }}" 
    class="sublink {{ Request::is('settings/academic*') ? 'active' : '' }}">
    <i class="bi bi-calendar"></i> Academic Years & Terms
    </a>
</div>