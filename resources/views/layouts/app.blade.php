<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Royal Kings Education Centre</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <link rel="icon" href="{{ asset('images/logo.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            width: 240px;
            height: 100vh;
            background: #3a1a59;
            color: white;
            position: fixed;
            top: 0; left: 0;
            padding-top: 20px;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
        }
        .sidebar .brand {
            text-align: center;
            margin-bottom: 25px;
        }
        .sidebar .brand img {
            width: 70px;
            margin-bottom: 8px;
        }
        .sidebar .brand h5 {
            font-size: 15px;
            font-weight: 600;
            color: #f1f1f1;
        }
        .sidebar a {
            color: #ffffff;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 500;
            transition: background 0.3s;
        }
        .sidebar a i {
            margin-right: 10px;
        }
        .sidebar a:hover,
        .sidebar a.active,
        .sidebar a.parent-active {
            background: #5a2c8a;
            color: #fff;
        }
        .collapse a {
            margin-left: 25px;
            font-size: 14px;
            color: #d1c4e9;
        }
        .collapse a.active {
            color: #ffc107;
            font-weight: 600;
        }
        .content {
            margin-left: 240px;
            padding: 20px;
            min-height: 100vh;
        }
        .sidebar-toggle {
            position: fixed;
            top: 15px; left: 15px;
            background: #3a1a59;
            color: white;
            border: none;
            font-size: 20px;
            border-radius: 4px;
            z-index: 1100;
        }
        @media(max-width:992px){
            .sidebar{ left:-240px; }
            .sidebar.active{ left:0; }
            .content{ margin-left:0; }
        }
    </style>
</head>
<body class="@auth with-sidebar @endauth">
    @auth
    <button class="sidebar-toggle d-lg-none" id="sidebarToggle"> <i class="bi bi-list"></i></button>

    <div class="sidebar">
        <div class="brand">
            <img src="{{ asset('images/logo.png') }}" alt="School Logo">
            <h5>Royal Kings School</h5>
        </div>

        <!-- Dashboard -->
        <a href="{{ route('admin.dashboard') }}" class="{{ Request::is('admin/dashboard') ? 'active' : '' }}">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <!-- Students -->
        @php $studentsActive = Request::is('students*') || Request::is('online-admissions*'); @endphp
        <a href="#studentsMenu" data-bs-toggle="collapse" aria-expanded="{{ $studentsActive ? 'true' : 'false' }}" class="{{ $studentsActive ? 'parent-active' : '' }}">
            <i class="bi bi-person"></i> Students
        </a>
        <div class="collapse {{ $studentsActive ? 'show' : '' }}" id="studentsMenu">
            <a href="{{ route('students.index') }}" class="{{ Request::is('students') ? 'active' : '' }}">Student Details</a>
            <a href="{{ route('students.create') }}" class="{{ Request::is('students/create') ? 'active' : '' }}">Admissions</a>
            <a href="{{ route('students.bulk') }}" class="{{ Request::is('students/bulk*') ? 'active' : '' }}">Bulk Upload</a>
            <a href="{{ route('online-admissions.index') }}" class="{{ Request::is('online-admissions*') ? 'active' : '' }}">Online Admissions</a>
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
            <a href="{{ route('attendance.notifications.notify.form') }}" 
            class="sublink {{ Request::is('attendance/notifications/notify*') ? 'active' : '' }}">
            <i class="bi bi-bell"></i> Notify Recipients
            </a>
            <a href="{{ route('attendance.notifications.index') }}" 
            class="sublink {{ Request::is('attendance/notifications*') ? 'active' : '' }}">
            <i class="bi bi-people"></i> Recipients
            </a>
        </div>

        <!-- Academics -->
        @php $academicsActive = Request::is('academics/classrooms*') || Request::is('academics/streams*') || Request::is('academics/subjects*') || Request::is('academics/subject_groups*'); @endphp
        <a href="#academicsMenu" data-bs-toggle="collapse" aria-expanded="{{ $academicsActive ? 'true' : 'false' }}" class="{{ $academicsActive ? 'parent-active' : '' }}">
            <i class="bi bi-journal-bookmark"></i> Academics
        </a>
        <div class="collapse {{ $academicsActive ? 'show' : '' }}" id="academicsMenu">
            <a href="{{ route('academics.classrooms.index') }}" class="{{ Request::is('academics/classrooms*') ? 'active' : '' }}">Classrooms</a>
            <a href="{{ route('academics.streams.index') }}" class="{{ Request::is('academics/streams*') ? 'active' : '' }}">Streams</a>
            <a href="{{ route('academics.subjects.index') }}" class="{{ Request::is('academics/subjects*') ? 'active' : '' }}">Subjects</a>
            <a href="{{ route('academics.subject_groups.index') }}" class="{{ Request::is('academics/subject_groups*') ? 'active' : '' }}">Subject Groups</a>
        </div>

        <!-- Exams -->
        @php $examsActive = Request::is('academics/exams*') || Request::is('academics/exam-grades*') || Request::is('academics/exam-marks*'); @endphp
        <a href="#examsMenu" data-bs-toggle="collapse" aria-expanded="{{ $examsActive ? 'true' : 'false' }}" class="{{ $examsActive ? 'parent-active' : '' }}">
            <i class="bi bi-file-earmark-text"></i> Exams
        </a>
        <div class="collapse {{ $examsActive ? 'show' : '' }}" id="examsMenu">
            <a href="{{ route('academics.exams.index') }}" class="{{ Request::is('academics/exams') ? 'active' : '' }}">Manage Exams</a>
            <a href="{{ route('academics.exam-grades.index') }}" class="{{ Request::is('academics/exam-grades*') ? 'active' : '' }}">Exam Grades</a>
            <a href="{{ route('academics.exam-marks.index') }}" class="{{ Request::is('academics/exam-marks*') ? 'active' : '' }}">Exam Marks</a>
            <a href="{{ route('academics.exams.timetable') }}" class="{{ Request::is('academics/exams/timetable') ? 'active' : '' }}">Exam Timetable</a>
        </div>

        <!-- Homework & Diaries -->
        @php $homeworkActive = Request::is('academics/homework*') || Request::is('academics/diaries*'); @endphp
        <a href="#homeworkMenu" data-bs-toggle="collapse" aria-expanded="{{ $homeworkActive ? 'true' : 'false' }}" class="{{ $homeworkActive ? 'parent-active' : '' }}">
            <i class="bi bi-journal"></i> Homework & Diaries
        </a>
        <div class="collapse {{ $homeworkActive ? 'show' : '' }}" id="homeworkMenu">
            <a href="{{ route('academics.homework.index') }}" class="{{ Request::is('academics/homework*') ? 'active' : '' }}">Homework</a>
            <a href="{{ route('academics.diaries.index') }}" class="{{ Request::is('academics/diaries*') ? 'active' : '' }}">Digital Diaries</a>
        </div>

        <!-- Report Cards -->
        @php $reportActive = Request::is('academics/report-cards*'); @endphp
        <a href="#reportMenu" data-bs-toggle="collapse" aria-expanded="{{ $reportActive ? 'true' : 'false' }}" class="{{ $reportActive ? 'parent-active' : '' }}">
            <i class="bi bi-card-text"></i> Report Cards
        </a>
        <div class="collapse {{ $reportActive ? 'show' : '' }}" id="reportMenu">
            <a href="{{ route('academics.report-cards.index') }}" class="{{ Request::is('academics/report-cards') ? 'active' : '' }}">Report Cards</a>
            <a href="{{ route('academics.report-cards.skills.index', \App\Models\Academics\ReportCard::first()?->id ?? 1) }}" class="{{ Request::is('academics/report-cards/*/skills*') ? 'active' : '' }}">Report Card Skills</a>
        </div>

        <!-- Behaviours -->
        @php $behaviourActive = Request::is('academics/behaviours*') || Request::is('academics/student-behaviours*'); @endphp
        <a href="#behaviourMenu" data-bs-toggle="collapse" aria-expanded="{{ $behaviourActive ? 'true' : 'false' }}" class="{{ $behaviourActive ? 'parent-active' : '' }}">
            <i class="bi bi-emoji-smile"></i> Behaviours
        </a>
        <div class="collapse {{ $behaviourActive ? 'show' : '' }}" id="behaviourMenu">
            <a href="{{ route('academics.behaviours.index') }}" class="{{ Request::is('academics/behaviours*') ? 'active' : '' }}">Behaviours</a>
            <a href="{{ route('academics.student-behaviours.index') }}" class="{{ Request::is('academics/student-behaviours*') ? 'active' : '' }}">Student Behaviours</a>
        </div>

        <!-- Finance -->
        @php $financeActive = Request::is('finance*') || Request::is('voteheads*'); @endphp
        <a href="#financeMenu" data-bs-toggle="collapse" aria-expanded="{{ $financeActive ? 'true' : 'false' }}" class="{{ $financeActive ? 'parent-active' : '' }}">
            <i class="bi bi-currency-dollar"></i> Finance
        </a>
        <div class="collapse {{ $financeActive ? 'show' : '' }}" id="financeMenu">
            <a href="{{ route('finance.voteheads.index') }}" class="{{ Request::is('finance/voteheads*') ? 'active' : '' }}">Voteheads</a>
            <a href="{{ route('finance.fee-structures.manage') }}" class="{{ Request::is('finance/fee-structures*') ? 'active' : '' }}">Fee Structures</a>
            <a href="{{ route('finance.invoices.index') }}" class="{{ Request::is('finance/invoices*') ? 'active' : '' }}">Invoices</a>
            <a href="{{ route('finance.optional_fees.index') }}" class="{{ Request::is('finance/optional_fees*') ? 'active' : '' }}">Optional Fees</a>
        </div>

        <!-- Staff -->
        <a href="{{ route('staff.index') }}" 
        class="{{ Request::is('staff*') ? 'active parent-active' : '' }}">
        <i class="bi bi-person-badge"></i><span> Staff</span>
        </a>

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

        <!-- Logout -->
        <a href="#" onclick="event.preventDefault();document.getElementById('logout-form').submit();" class="text-danger">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
    </div>
     @endauth

    <div class="content">
        <div class="page-wrapper">@yield('content')</div>
    </div>

    <script>
        document.getElementById("sidebarToggle").addEventListener("click", function(){
            document.querySelector(".sidebar").classList.toggle("active");
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
