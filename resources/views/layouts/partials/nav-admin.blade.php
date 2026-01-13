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
$studentsActive = Request::is('students*')
    || Request::is('online-admissions*')
    || Request::is('families*')
    || Request::is('admin/family-update*');
$studentRecordsActive = Request::is('students/*/medical-records*') || Request::is('students/*/disciplinary-records*') || Request::is('students/*/activities*') || Request::is('students/*/academic-history*');
@endphp
<a href="#studentsMenu" data-bs-toggle="collapse" aria-expanded="{{ $studentsActive ? 'true' : 'false' }}" class="{{ $studentsActive ? 'parent-active' : '' }}">
    <i class="bi bi-person"></i> Students
</a>
<div class="collapse {{ $studentsActive ? 'show' : '' }}" id="studentsMenu">
    <a href="{{ route('students.index') }}" class="{{ Request::is('students') && !$studentRecordsActive ? 'active' : '' }}">Student Details</a>
    <a href="{{ route('students.create') }}" class="{{ Request::is('students/create') ? 'active' : '' }}">Admissions</a>
    <a href="{{ route('students.bulk.assign-categories') }}" class="{{ Request::is('students/bulk-assign-categories*') ? 'active' : '' }}"><i class="bi bi-tag"></i> Assign Categories</a>
    <a href="{{ route('students.bulk') }}" class="{{ Request::is('students/bulk*') ? 'active' : '' }}">Bulk Upload</a>
    @if(Route::has('students.archived'))
    <a href="{{ route('students.archived') }}" class="{{ Request::is('students/archived*') ? 'active' : '' }}">
        <i class="bi bi-archive"></i> Archived Students
    </a>
    @endif
    <a href="{{ route('student-categories.index') }}" class="{{ Request::is('student-categories*') ? 'active' : '' }}"><i class="bi bi-collection"></i> Student Categories</a>
    <a href="{{ route('families.index') }}" class="{{ Request::is('families*') ? 'active' : '' }}"><i class="bi bi-people"></i> Families (Siblings)</a>
    <a href="{{ route('family-update.admin.index') }}" class="{{ Request::is('admin/family-update*') ? 'active' : '' }}"><i class="bi bi-link-45deg"></i> Profile Update Links</a>
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
        || Request::is('academics/subject_groups*')
        || Request::is('academics/assign-teachers*')
        || Request::is('academics/promotions*');
@endphp
<a href="#academicsMenu" data-bs-toggle="collapse" aria-expanded="{{ $academicsActive ? 'true' : 'false' }}" class="{{ $academicsActive ? 'parent-active' : '' }}">
    <i class="bi bi-journal-bookmark"></i> Academics
</a>
<div class="collapse {{ $academicsActive ? 'show' : '' }}" id="academicsMenu">
    <a href="{{ route('academics.classrooms.index') }}" class="{{ Request::is('academics/classrooms*') ? 'active' : '' }}">Classrooms</a>
    <a href="{{ route('academics.streams.index') }}" class="{{ Request::is('academics/streams*') ? 'active' : '' }}">Streams</a>
    <a href="{{ route('academics.subjects.index') }}" class="{{ Request::is('academics/subjects*') && !Request::is('academics/subjects/teacher-assignments*') ? 'active' : '' }}">Subjects</a>
    <a href="{{ route('academics.subjects.teacher-assignments') }}" class="sublink {{ Request::is('academics/subjects/teacher-assignments*') ? 'active' : '' }}">
        <i class="bi bi-person-lines-fill"></i> Subject Teacher Map
    </a>
    <a href="{{ route('academics.subject_groups.index') }}" class="{{ Request::is('academics/subject_groups*') ? 'active' : '' }}">Subject Groups</a>
    <a href="{{ route('academics.assign-teachers') }}" class="{{ Request::is('academics/assign-teachers*') ? 'active' : '' }}">
        <i class="bi bi-person-check"></i> Assign Teachers
    </a>
    <a href="{{ route('academics.promotions.index') }}" class="{{ Request::is('academics/promotions*') ? 'active' : '' }}">
        <i class="bi bi-arrow-up-circle"></i> Student Promotions
    </a>
</div>

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
<a href="#cbcMenu" data-bs-toggle="collapse" aria-expanded="{{ $cbcActive ? 'true' : 'false' }}" class="{{ $cbcActive ? 'parent-active' : '' }}">
    <i class="bi bi-diagram-3"></i> CBC Curriculum & Planning
</a>
<div class="collapse {{ $cbcActive ? 'show' : '' }}" id="cbcMenu">
    <a href="{{ route('academics.curriculum-designs.index') }}" class="sublink {{ Request::is('academics/curriculum-designs*') ? 'active' : '' }}">
        <i class="bi bi-layer-forward"></i> Curriculum Designs
    </a>
    <a href="{{ route('academics.learning-areas.index') }}" class="sublink {{ Request::is('academics/learning-areas*') ? 'active' : '' }}">
        <i class="bi bi-book"></i> Learning Areas
    </a>
    <a href="{{ route('academics.cbc-strands.index') }}" class="sublink {{ Request::is('academics/cbc-strands*') ? 'active' : '' }}">
        <i class="bi bi-diagram-3"></i> CBC Strands
    </a>
    <a href="{{ route('academics.competencies.index') }}" class="sublink {{ Request::is('academics/competencies*') ? 'active' : '' }}">
        <i class="bi bi-star"></i> Competencies
    </a>
    <a href="{{ route('academics.schemes-of-work.index') }}" class="sublink {{ Request::is('academics/schemes-of-work') ? 'active' : '' }}">
        <i class="bi bi-journal-text"></i> Schemes of Work
    </a>
    <a href="{{ route('academics.lesson-plans.index') }}" class="sublink {{ Request::is('academics/lesson-plans') ? 'active' : '' }}">
        <i class="bi bi-calendar-check"></i> Lesson Plans
    </a>
    <a href="{{ route('academics.portfolio-assessments.index') }}" class="sublink {{ Request::is('academics/portfolio-assessments*') ? 'active' : '' }}">
        <i class="bi bi-folder"></i> Portfolio Assessments
    </a>
    <a href="{{ route('academics.exam-analytics.index') }}" class="sublink {{ Request::is('academics/exam-analytics*') ? 'active' : '' }}">
        <i class="bi bi-graph-up"></i> Exam Analytics
    </a>
</div>

{{-- Timetable --}}
@php 
    $timetableActive = Request::is('academics/timetable*') 
        || Request::is('academics/activities*');
@endphp
<a href="#timetableMenu" data-bs-toggle="collapse" aria-expanded="{{ $timetableActive ? 'true' : 'false' }}" class="{{ $timetableActive ? 'parent-active' : '' }}">
    <i class="bi bi-calendar-week"></i> Timetable
</a>
<div class="collapse {{ $timetableActive ? 'show' : '' }}" id="timetableMenu">
    <a href="{{ route('academics.timetable.index') }}" class="sublink {{ Request::is('academics/timetable') && !Request::is('academics/timetable/*') ? 'active' : '' }}">
        <i class="bi bi-calendar-week"></i> View Timetable
    </a>
    <a href="{{ route('academics.timetable.index', ['view' => 'classrooms']) }}" class="sublink {{ Request::is('academics/timetable/classroom*') ? 'active' : '' }}">
        <i class="bi bi-building"></i> Classroom Timetable
    </a>
    <a href="{{ route('academics.timetable.index', ['view' => 'teacher']) }}" class="sublink {{ Request::is('academics/timetable/teacher*') ? 'active' : '' }}">
        <i class="bi bi-person"></i> Teacher Timetable
    </a>
    <a href="{{ route('academics.activities.index') }}" class="sublink {{ Request::is('academics/activities*') ? 'active' : '' }}">
        <i class="bi bi-trophy"></i> Activities
    </a>
</div>

{{-- Homework & Diaries --}}
@php $homeworkActive = Request::is('academics/homework*') || Request::is('academics/diaries*'); @endphp
<a href="#homeworkMenu" data-bs-toggle="collapse" aria-expanded="{{ $homeworkActive ? 'true' : 'false' }}" class="{{ $homeworkActive ? 'parent-active' : '' }}">
    <i class="bi bi-journal"></i> Homework & Diaries
</a>
<div class="collapse {{ $homeworkActive ? 'show' : '' }}" id="homeworkMenu">
    <a href="{{ route('academics.homework.index') }}" class="{{ Request::is('academics/homework*') ? 'active' : '' }}">Homework</a>
    <a href="{{ route('diaries.index') }}" class="{{ Request::is('academics/diaries*') ? 'active' : '' }}">Digital Diaries</a>
</div>

{{-- Exams --}}
@php
    $examsActive = Request::is('academics/exams*')
        || Request::is('academics/exam-types*')
        || Request::is('academics/exam-grades*')
        || Request::is('academics/exam-marks*')
        || Request::is('academics/exams/results*')
        || Request::is('academics/exams/timetable*');
@endphp

<a href="#examsMenu" data-bs-toggle="collapse"
aria-expanded="{{ $examsActive ? 'true' : 'false' }}"
class="{{ $examsActive ? 'parent-active' : '' }}">
<i class="bi bi-file-earmark-text"></i> Exams
</a>

<div class="collapse {{ $examsActive ? 'show' : '' }}" id="examsMenu">
    {{-- New exam structure --}}
    <a href="{{ route('academics.exams.types.index') }}"
    class="sublink {{ Request::is('academics/exam-types*') ? 'active' : '' }}">
        <i class="bi bi-sliders2"></i> Exam Types
    </a>

    <a href="{{ route('academics.exams.index') }}"
    class="sublink {{ Request::is('academics/exams') && !Request::is('academics/exams/*') ? 'active' : '' }}">
        <i class="bi bi-journal-check"></i> Manage Exams
    </a>

    <a href="{{ route('academics.exam-marks.bulk.form') }}"
    class="sublink {{ Request::is('academics/exam-marks*') ? 'active' : '' }}">
        <i class="bi bi-pencil-square"></i> Enter Marks
    </a>

    <a href="{{ route('academics.exams.results.index') }}"
    class="sublink {{ Request::is('academics/exams/results*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart"></i> Exam Results
    </a>

    <a href="{{ route('academics.exams.timetable') }}"
    class="sublink {{ Request::is('academics/exams/timetable*') ? 'active' : '' }}">
        <i class="bi bi-printer"></i> Exam Timetable
    </a>
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
    @php
      $latestReportCard = \App\Models\Academics\ReportCard::query()->latest()->first();
    @endphp
    @if($latestReportCard)
      <a href="{{ route('academics.report_cards.skills.index', $latestReportCard) }}"
      class="sublink {{ Request::is('academics/report_cards/*/skills*') ? 'active' : '' }}">
        <i class="bi bi-pencil-square"></i> Skills Editor
      </a>
    @else
      <a href="{{ route('academics.report_cards.index') }}"
      class="sublink">
        <i class="bi bi-pencil-square"></i> Skills Editor
      </a>
    @endif
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
    {{-- Fee Setup --}}
    <a href="{{ route('finance.voteheads.index') }}" class="{{ Request::is('finance/voteheads*') ? 'active' : '' }}"><i class="bi bi-list-ul"></i> Voteheads</a>
    <a href="{{ route('finance.fee-structures.manage') }}"class="{{ Request::is('finance/fee-structures*') ? 'active' : '' }}"><i class="bi bi-table"></i> Fee Structures</a>
    <a href="{{ route('finance.posting.index') }}"class="{{ Request::is('finance/posting*') ? 'active' : '' }}"><i class="bi bi-arrow-right-circle"></i> Posting (Pending → Active)</a>
    <a href="{{ route('finance.optional_fees.index') }}"class="{{ Request::is('finance/optional-fees*') || Request::is('finance/optional_fees*') ? 'active' : '' }}"><i class="bi bi-toggle-on"></i> Optional Fees</a>
    <a href="{{ route('finance.transport-fees.index') }}"class="{{ Request::is('finance/transport-fees*') ? 'active' : '' }}"><i class="bi bi-bus-front"></i> Transport Fees</a>
    
    {{-- Discounts --}}
    @php
        $discountsActive = Request::is('finance/discounts*');
    @endphp
    <a href="#discountsMenu" data-bs-toggle="collapse" aria-expanded="{{ $discountsActive ? 'true' : 'false' }}" class="{{ $discountsActive ? 'parent-active' : '' }}"><i class="bi bi-percent"></i> Discounts</a>
    <div class="collapse {{ $discountsActive ? 'show' : '' }}" id="discountsMenu" style="padding-left: 20px;">
        <a href="{{ route('finance.discounts.index') }}" class="sublink {{ Request::is('finance/discounts') && !Request::is('finance/discounts/*') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="{{ route('finance.discounts.templates.index') }}" class="sublink {{ Request::is('finance/discounts/templates*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text"></i> Templates</a>
        <a href="{{ route('finance.discounts.allocations.index') }}" class="sublink {{ Request::is('finance/discounts/allocations*') ? 'active' : '' }}"><i class="bi bi-list-check"></i> Allocations & Allocate</a>
        <a href="{{ route('finance.discounts.bulk-allocate-sibling') }}" class="sublink {{ Request::is('finance/discounts/bulk-allocate-sibling*') ? 'active' : '' }}"><i class="bi bi-people"></i> Bulk Sibling</a>
        <a href="{{ route('finance.discounts.replicate.form') }}" class="sublink {{ Request::is('finance/discounts/replicate*') ? 'active' : '' }}"><i class="bi bi-copy"></i> Replicate</a>
    </div>
    
    {{-- Invoicing & Payments --}}
    <a href="{{ route('finance.invoices.index') }}"class="{{ Request::is('finance/invoices*') ? 'active' : '' }}"><i class="bi bi-file-text"></i> Invoices</a>
    <a href="{{ route('finance.journals.index') }}"class="{{ Request::is('finance/journals*') || Request::is('finance/credits*') || Request::is('finance/debits*') ? 'active' : '' }}"><i class="bi bi-arrow-left-right"></i> Credit / Debit Adjustments</a>
    <a href="{{ route('finance.payments.index') }}"class="{{ Request::is('finance/payments*') && !Request::is('finance/mpesa*') ? 'active' : '' }}"><i class="bi bi-cash-stack"></i> Payments</a>
    
    {{-- M-PESA Payments --}}
    @php
        $mpesaActive = Request::is('finance/mpesa*');
    @endphp
    <a href="#mpesaMenu" data-bs-toggle="collapse" aria-expanded="{{ $mpesaActive ? 'true' : 'false' }}" class="{{ $mpesaActive ? 'parent-active' : '' }}"><i class="bi bi-phone text-success"></i> M-PESA Payments</a>
    <div class="collapse {{ $mpesaActive ? 'show' : '' }}" id="mpesaMenu" style="padding-left: 20px;">
        <a href="{{ route('finance.mpesa.dashboard') }}" class="sublink {{ Request::is('finance/mpesa/dashboard') ? 'active' : '' }}"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="{{ route('finance.mpesa.prompt-payment.form') }}" class="sublink {{ Request::is('finance/mpesa/prompt-payment*') ? 'active' : '' }}"><i class="bi bi-phone-vibrate"></i> Prompt Parent to Pay</a>
        <a href="{{ route('finance.mpesa.links.create') }}" class="sublink {{ Request::is('finance/mpesa/links/create') ? 'active' : '' }}"><i class="bi bi-link-45deg"></i> Generate Payment Link</a>
        <a href="{{ route('finance.mpesa.links.index') }}" class="sublink {{ Request::is('finance/mpesa/links') && !Request::is('finance/mpesa/links/create') ? 'active' : '' }}"><i class="bi bi-list-ul"></i> View Payment Links</a>
    </div>
    
    {{-- Bank Statements --}}
    @php
        $bankStatementsActive = Request::is('finance/bank-statements*');
    @endphp
    <a href="#bankStatementsMenu" data-bs-toggle="collapse" aria-expanded="{{ $bankStatementsActive ? 'true' : 'false' }}" class="{{ $bankStatementsActive ? 'parent-active' : '' }}"><i class="bi bi-file-earmark-pdf"></i> Bank Statements</a>
    <div class="collapse {{ $bankStatementsActive ? 'show' : '' }}" id="bankStatementsMenu" style="padding-left: 20px;">
        <a href="{{ route('finance.bank-statements.statements') }}" class="sublink {{ Request::is('finance/bank-statements/statements') ? 'active' : '' }}"><i class="bi bi-folder2-open"></i> Imported Statements</a>
        <a href="{{ route('finance.bank-statements.index') }}" class="sublink {{ Request::is('finance/bank-statements') && !Request::is('finance/bank-statements/statements') ? 'active' : '' }}"><i class="bi bi-list-ul"></i> Transactions</a>
        <a href="{{ route('finance.bank-statements.create') }}" class="sublink {{ Request::is('finance/bank-statements/create') ? 'active' : '' }}"><i class="bi bi-upload"></i> Upload Statement</a>
    </div>
    <a href="{{ route('finance.student-statements.index') }}"class="{{ Request::is('finance/student-statements*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text"></i> Student Statements</a>
    <a href="{{ route('finance.balance-brought-forward.index') }}"class="{{ Request::is('finance/balance-brought-forward*') ? 'active' : '' }}"><i class="bi bi-arrow-left-circle"></i> Balance Brought Forward</a>
    <a href="{{ route('finance.legacy-imports.index') }}" class="{{ Request::is('finance/legacy-imports*') ? 'active' : '' }}"><i class="bi bi-upload"></i> Legacy Imports</a>
    
    {{-- Payment Setup --}}
    <a href="{{ route('finance.bank-accounts.index') }}"class="{{ Request::is('finance/bank-accounts*') ? 'active' : '' }}"><i class="bi bi-bank"></i> Bank Accounts</a>
    <a href="{{ route('finance.payment-methods.index') }}"class="{{ Request::is('finance/payment-methods*') ? 'active' : '' }}"><i class="bi bi-credit-card"></i> Payment Methods</a>
    
    {{-- Other Features --}}
    <a href="{{ route('finance.fee-payment-plans.index') }}"class="{{ Request::is('finance/fee-payment-plans*') ? 'active' : '' }}"><i class="bi bi-calendar-check"></i> Payment Plans</a>
    <a href="{{ route('finance.fee-concessions.index') }}"class="{{ Request::is('finance/fee-concessions*') ? 'active' : '' }}"><i class="bi bi-tag-fill"></i> Fee Concessions</a>
    <a href="{{ route('finance.fee-reminders.index') }}"class="{{ Request::is('finance/fee-reminders*') ? 'active' : '' }}"><i class="bi bi-bell"></i> Fee Reminders</a>
    <a href="{{ route('finance.accountant-dashboard.index') }}"class="{{ Request::is('finance/accountant-dashboard*') ? 'active' : '' }}"><i class="bi bi-graph-up"></i> Accountant Dashboard</a>
    <a href="{{ route('finance.document-settings.index') }}"class="{{ Request::is('finance/document-settings*') ? 'active' : '' }}"><i class="bi bi-file-earmark-text"></i> Document Settings</a>
</div>

<!-- Transport -->
@php $isTransportActive = Request::is('transport*') || Request::is('driver*'); @endphp
<a href="#transportMenu" data-bs-toggle="collapse" 
aria-expanded="{{ $isTransportActive ? 'true' : 'false' }}"
class="{{ $isTransportActive ? 'parent-active' : '' }}">
<i class="bi bi-truck"></i><span> Transport</span>
</a>
<div class="collapse {{ $isTransportActive ? 'show' : '' }}" id="transportMenu">
    @if(auth()->user()->hasRole('Driver'))
        {{-- Driver-specific links --}}
        <a href="{{ route('driver.index') }}" 
        class="sublink {{ Request::is('driver') && !Request::is('driver/*') ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> My Trips
        </a>
        <a href="{{ route('driver.transport-sheet') }}" 
        class="sublink {{ Request::is('driver/transport-sheet*') ? 'active' : '' }}">
        <i class="bi bi-printer"></i> Transport Sheet
        </a>
        <a href="{{ route('transport.driver-change-requests.create') }}" 
        class="sublink {{ Request::is('transport/driver-change-requests/create') ? 'active' : '' }}">
        <i class="bi bi-plus-circle"></i> Request Change
        </a>
        <a href="{{ route('transport.driver-change-requests.index') }}" 
        class="sublink {{ Request::is('transport/driver-change-requests*') && !Request::is('transport/driver-change-requests/create') ? 'active' : '' }}">
        <i class="bi bi-list-check"></i> My Requests
        </a>
    @endif
    
    @if(auth()->user()->hasAnyRole(['Super Admin', 'Admin', 'Secretary']))
        {{-- Admin/Secretary links --}}
        <a href="{{ route('transport.dashboard') }}" 
        class="sublink {{ Request::is('transport/home*') || (Request::is('transport') && !Request::is('transport/*')) ? 'active' : '' }}">
        <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="{{ route('transport.import.form') }}" 
        class="sublink {{ Request::is('transport/import*') ? 'active' : '' }}">
        <i class="bi bi-upload"></i> Import Assignments
        </a>
        <a href="{{ route('transport.daily-list.index') }}" 
        class="sublink {{ Request::is('transport/daily-list*') ? 'active' : '' }}">
        <i class="bi bi-list-check"></i> Daily Transport List
        </a>
        <a href="{{ route('transport.vehicles.index') }}" 
        class="sublink {{ Request::is('transport/vehicles*') ? 'active' : '' }}">
        <i class="bi bi-bus-front"></i> Vehicles
        </a>
        <a href="{{ route('transport.trips.index') }}" 
        class="sublink {{ Request::is('transport/trips*') ? 'active' : '' }}">
        <i class="bi bi-geo"></i> Trips
        </a>
        <a href="{{ route('transport.student-assignments.index') }}" 
        class="sublink {{ Request::is('transport/student-assignments*') ? 'active' : '' }}">
        <i class="bi bi-people"></i> Assignments
        </a>
        <a href="{{ route('transport.special-assignments.index') }}" 
        class="sublink {{ Request::is('transport/special-assignments*') ? 'active' : '' }}">
        <i class="bi bi-star"></i> Special Assignments
        </a>
        <a href="{{ route('transport.driver-change-requests.index') }}" 
        class="sublink {{ Request::is('transport/driver-change-requests*') ? 'active' : '' }}">
        <i class="bi bi-arrow-repeat"></i> Driver Change Requests
        </a>
    @endif
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
    <a href="{{ route('communication.send.whatsapp') }}" 
    class="sublink {{ Request::is('communication/send-whatsapp*') ? 'active' : '' }}">
    <i class="bi bi-whatsapp"></i> Send WhatsApp
    </a>
    <a href="{{ route('communication.wasender.sessions') }}" 
    class="sublink {{ Request::is('communication/whatsapp-sessions*') ? 'active' : '' }}">
    <i class="bi bi-hdd-network"></i> WhatsApp Sessions
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

<!-- HR -->
@php
  $hrActive = Request::is('staff*')
    || Request::is('hr/access-lookups*')
    || Request::is('hr/roles*')
    || Request::is('lookups*')
    || Request::is('hr/profile-requests*')
    || Request::is('hr/reports*')
    || Request::is('hr/analytics*')
    || Request::is('staff/leave-types*')
    || Request::is('staff/leave-requests*')
    || Request::is('staff/leave-balances*')
    || Request::is('staff/attendance*')
    || Request::is('staff/documents*');
@endphp


<a href="#hrMenu" data-bs-toggle="collapse"
   aria-expanded="{{ $hrActive ? 'true' : 'false' }}"
   class="{{ $hrActive ? 'parent-active' : '' }}">
  <i class="bi bi-briefcase"></i> HR
</a>
<div class="collapse {{ $hrActive ? 'show' : '' }}" id="hrMenu">
  <a href="{{ route('staff.index') }}" class="{{ Request::is('staff') && !Request::is('staff/*') ? 'active' : '' }}">
    <i class="bi bi-people"></i> Staff
  </a>
  <a href="{{ route('hr.access-lookups') }}" class="{{ Request::is('hr/access-lookups*') || Request::is('hr/roles*') || Request::is('lookups*') ? 'active' : '' }}">
    <i class="bi bi-shield-lock"></i> Roles & Lookups
  </a>
  @if(auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
    <a href="{{ route('admin.senior_teacher_assignments.index') }}" class="{{ Request::is('admin/senior-teacher-assignments*') ? 'active' : '' }}">
      <i class="bi bi-person-badge"></i> Senior Teacher Assignments
    </a>
  @endif
  <a href="{{ route('staff.leave-types.index') }}" class="{{ Request::is('staff/leave-types*') ? 'active' : '' }}">
    <i class="bi bi-calendar-check"></i> Leave Types
  </a>
  <a href="{{ route('staff.leave-requests.index') }}" class="{{ Request::is('staff/leave-requests*') ? 'active' : '' }}">
    <i class="bi bi-calendar-event"></i> Leave Requests
  </a>
  <a href="{{ route('staff.leave-balances.index') }}" class="{{ Request::is('staff/leave-balances*') ? 'active' : '' }}">
    <i class="bi bi-calendar-minus"></i> Leave Balances
  </a>
  <a href="{{ route('staff.attendance.index') }}" class="{{ Request::is('staff/attendance*') ? 'active' : '' }}">
    <i class="bi bi-clock-history"></i> Staff Attendance
  </a>
  <a href="{{ route('staff.documents.index') }}" class="{{ Request::is('staff/documents*') ? 'active' : '' }}">
    <i class="bi bi-file-earmark"></i> Documents
  </a>
  <a href="{{ route('hr.reports.index') }}" class="{{ Request::is('hr/reports*') ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text"></i> Reports
  </a>
  <a href="{{ route('hr.analytics.index') }}" class="{{ Request::is('hr/analytics*') ? 'active' : '' }}">
    <i class="bi bi-graph-up"></i> Analytics
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


<!-- Payroll -->
@php
  $payrollActive = Request::is('hr/payroll*');
@endphp
<a href="#payrollMenu" data-bs-toggle="collapse"
   aria-expanded="{{ $payrollActive ? 'true' : 'false' }}"
   class="{{ $payrollActive ? 'parent-active' : '' }}">
  <i class="bi bi-cash-stack"></i> Payroll
</a>
<div class="collapse {{ $payrollActive ? 'show' : '' }}" id="payrollMenu">
  <span class="small text-muted text-uppercase px-3 d-block mt-2">Operations</span>
  <a href="{{ route('hr.payroll.records.index') }}" class="{{ Request::is('hr/payroll/records*') ? 'active' : '' }}">
    <i class="bi bi-receipt"></i> Payroll Records
  </a>
  <a href="{{ route('hr.payroll.periods.index') }}" class="{{ Request::is('hr/payroll/periods*') ? 'active' : '' }}">
    <i class="bi bi-calendar-range"></i> Payroll Periods
  </a>
  <a href="{{ route('hr.payroll.advances.index') }}" class="{{ Request::is('hr/payroll/advances*') ? 'active' : '' }}">
    <i class="bi bi-arrow-down-circle"></i> Staff Advances
  </a>

  <span class="small text-muted text-uppercase px-3 d-block mt-3">Setup</span>
  <a href="{{ route('hr.payroll.salary-structures.index') }}" class="{{ Request::is('hr/payroll/salary-structures*') ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text"></i> Salary Structures
  </a>
  <a href="{{ route('hr.payroll.deduction-types.index') }}" class="{{ Request::is('hr/payroll/deduction-types*') ? 'active' : '' }}">
    <i class="bi bi-tags"></i> Deduction Types
  </a>
  <a href="{{ route('hr.payroll.custom-deductions.index') }}" class="{{ Request::is('hr/payroll/custom-deductions*') ? 'active' : '' }}">
    <i class="bi bi-list-check"></i> Custom Deductions
  </a>
</div>

<!-- Events Calendar -->
@php $eventsActive = Request::is('events*'); @endphp
<a href="{{ route('events.index') }}" class="{{ $eventsActive ? 'active' : '' }}">
    <i class="bi bi-calendar-event"></i> Events Calendar
</a>

<!-- Inventory & Requirements -->
@php $inventoryActive = Request::is('inventory*'); @endphp
<a href="#inventoryMenu" data-bs-toggle="collapse" 
aria-expanded="{{ $inventoryActive ? 'true' : 'false' }}"
class="{{ $inventoryActive ? 'parent-active' : '' }}">
    <i class="bi bi-box-seam"></i> Inventory & Requirements
</a>
<div class="collapse {{ $inventoryActive ? 'show' : '' }}" id="inventoryMenu">
    <a href="{{ route('inventory.items.index') }}" 
    class="sublink {{ Request::is('inventory/items*') ? 'active' : '' }}">
        <i class="bi bi-box"></i> Inventory Items
    </a>
    <a href="{{ route('inventory.requirement-types.index') }}" 
    class="sublink {{ Request::is('inventory/requirement-types*') ? 'active' : '' }}">
        <i class="bi bi-list-check"></i> Requirement Types
    </a>
    <a href="{{ route('inventory.requirement-templates.index') }}" 
    class="sublink {{ Request::is('inventory/requirement-templates*') ? 'active' : '' }}">
        <i class="bi bi-file-earmark-text"></i> Requirement Templates
    </a>
    <a href="{{ route('inventory.student-requirements.index') }}" 
    class="sublink {{ Request::is('inventory/student-requirements*') ? 'active' : '' }}">
        <i class="bi bi-person-check"></i> Student Requirements
    </a>
    <a href="{{ route('inventory.requisitions.index') }}" 
    class="sublink {{ Request::is('inventory/requisitions*') ? 'active' : '' }}">
        <i class="bi bi-cart-check"></i> Requisitions
    </a>
</div>

<!-- Point of Sale (POS) -->
@php $posActive = Request::is('pos*'); @endphp
<a href="#posMenu" data-bs-toggle="collapse" 
aria-expanded="{{ $posActive ? 'true' : 'false' }}"
class="{{ $posActive ? 'parent-active' : '' }}">
    <i class="bi bi-shop"></i> Point of Sale
</a>
<div class="collapse {{ $posActive ? 'show' : '' }}" id="posMenu">
    <a href="{{ route('pos.products.index') }}" 
    class="sublink {{ Request::is('pos/products*') ? 'active' : '' }}">
        <i class="bi bi-box-seam"></i> Products
    </a>
    <a href="{{ route('pos.orders.index') }}" 
    class="sublink {{ Request::is('pos/orders*') ? 'active' : '' }}">
        <i class="bi bi-receipt"></i> Orders
    </a>
    <a href="{{ route('pos.discounts.index') }}" 
    class="sublink {{ Request::is('pos/discounts*') ? 'active' : '' }}">
        <i class="bi bi-tag"></i> Discounts
    </a>
    <a href="{{ route('pos.public-links.index') }}" 
    class="sublink {{ Request::is('pos/public-links*') ? 'active' : '' }}">
        <i class="bi bi-link-45deg"></i> Public Links
    </a>
    <a href="{{ route('pos.uniforms.index') }}" 
    class="sublink {{ Request::is('pos/uniforms*') ? 'active' : '' }}">
        <i class="bi bi-person-badge"></i> Uniforms
    </a>
</div>

<!-- Documents -->
@php $documentsActive = Request::is('documents*'); @endphp
<a href="{{ route('documents.index') }}" class="{{ $documentsActive ? 'active' : '' }}">
    <i class="bi bi-file-earmark"></i> Documents
</a>

<!-- Settings -->
@php $isSettingsActive = Request::is('settings*'); @endphp
@php 
    $isAcademicConfig = Request::is('settings/academic*') || Request::is('settings/school-days*');
    $isTermHolidays = Request::is('settings/academic/term-holidays*');
@endphp
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
    <a href="{{ route('settings.academic.index') }}" 
    class="sublink {{ ($isAcademicConfig && !$isTermHolidays) ? 'active' : '' }}">
    <i class="bi bi-calendar-check"></i> Academic Calendar (Years, Terms, Days)
    </a>
    <a href="{{ route('settings.academic.term-holidays') }}" 
    class="sublink {{ $isTermHolidays ? 'active' : '' }}">
    <i class="bi bi-umbrella"></i> Term Holidays
    </a>
    @if(auth()->user()->hasRole('Super Admin') || auth()->user()->hasRole('Admin'))
    <a href="{{ route('activity-logs.index') }}" 
    class="sublink {{ Request::is('activity-logs*') ? 'active' : '' }}">
    <i class="bi bi-clock-history"></i> Activity Logs
    </a>
    <a href="{{ route('system-logs.index') }}" 
    class="sublink {{ Request::is('system-logs*') ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text"></i> System Logs
    </a>
    @endif
</div>