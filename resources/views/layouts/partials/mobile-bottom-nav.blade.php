@php
  $user = auth()->user();
  $home = route('home');
  $items = [];

  $isTeacher = $user?->hasAnyRole(['Teacher', 'teacher']) && ! $user?->hasAnyRole(['Super Admin', 'Admin', 'Secretary', 'Senior Teacher', 'Deputy Senior Teacher']);
  $isSenior = $user?->hasAnyRole(['Senior Teacher', 'Deputy Senior Teacher']);
  $isFinance = $user?->hasAnyRole(['Accountant', 'Finance Officer']) && ! $user?->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);
  $isTransport = $user?->hasRole('Driver') && ! $user?->hasAnyRole(['Super Admin', 'Admin', 'Secretary']);

  if ($isTeacher && Route::has('teacher.dashboard')) {
      $home = route('teacher.dashboard');
      $items = [
          ['href' => $home, 'icon' => 'bi-house', 'label' => 'Home', 'active' => request()->routeIs('teacher.dashboard')],
          ['href' => Route::has('teacher.students.index') ? route('teacher.students.index') : (Route::has('students.index') ? route('students.index') : $home), 'icon' => 'bi-people', 'label' => 'Classes', 'active' => request()->is('my-students*') || request()->is('teacher/students*')],
          ['href' => Route::has('attendance.mark.form') ? route('attendance.mark.form') : $home, 'icon' => 'bi-clipboard-check', 'label' => 'Attendance', 'active' => request()->is('attendance*')],
          ['href' => Route::has('academics.exam-marks.index') ? route('academics.exam-marks.index') : $home, 'icon' => 'bi-journal-check', 'label' => 'Marks', 'active' => request()->is('academics/exam-marks*')],
      ];
  } elseif ($isSenior && Route::has('senior_teacher.dashboard')) {
      $home = route('senior_teacher.dashboard');
      $items = [
          ['href' => $home, 'icon' => 'bi-house', 'label' => 'Home', 'active' => request()->routeIs('senior_teacher.dashboard')],
          ['href' => Route::has('senior_teacher.supervised_classrooms') ? route('senior_teacher.supervised_classrooms') : $home, 'icon' => 'bi-building', 'label' => 'Classes', 'active' => request()->routeIs('senior_teacher.supervised_classrooms')],
          ['href' => Route::has('attendance.mark.form') ? route('attendance.mark.form') : $home, 'icon' => 'bi-clipboard-check', 'label' => 'Attendance', 'active' => request()->is('attendance*')],
          ['href' => Route::has('senior_teacher.students.index') ? route('senior_teacher.students.index') : $home, 'icon' => 'bi-people', 'label' => 'Students', 'active' => request()->routeIs('senior_teacher.students.*')],
      ];
  } elseif ($isFinance && Route::has('finance.dashboard')) {
      $home = route('finance.dashboard');
      $items = [
          ['href' => $home, 'icon' => 'bi-grid', 'label' => 'Dashboard', 'active' => request()->routeIs('finance.dashboard')],
          ['href' => Route::has('finance.student-statements.index') ? route('finance.student-statements.index') : $home, 'icon' => 'bi-people', 'label' => 'Students', 'active' => request()->is('finance/student-statements*')],
          ['href' => Route::has('finance.payments.index') ? route('finance.payments.index') : $home, 'icon' => 'bi-cash', 'label' => 'Payments', 'active' => request()->is('finance/payments*')],
          ['href' => Route::has('finance.invoices.index') ? route('finance.invoices.index') : $home, 'icon' => 'bi-receipt', 'label' => 'Invoices', 'active' => request()->is('finance/invoices*')],
      ];
  } elseif ($isTransport && Route::has('transport.dashboard')) {
      $home = route('transport.dashboard');
      $items = [
          ['href' => $home, 'icon' => 'bi-house', 'label' => 'Home', 'active' => request()->routeIs('transport.dashboard')],
          ['href' => Route::has('transport.trips.index') ? route('transport.trips.index') : $home, 'icon' => 'bi-geo', 'label' => 'Trips', 'active' => request()->is('transport/trips*')],
          ['href' => Route::has('transport.student-assignments.index') ? route('transport.student-assignments.index') : $home, 'icon' => 'bi-people', 'label' => 'Students', 'active' => request()->is('transport/student-assignments*')],
          ['href' => Route::has('transport.vehicles.index') ? route('transport.vehicles.index') : $home, 'icon' => 'bi-bus-front', 'label' => 'Vehicles', 'active' => request()->is('transport/vehicles*')],
      ];
  } else {
      if (Route::has('admin.dashboard') && \App\Support\NavAccess::canDashboard('admin.dashboard')) {
          $home = route('admin.dashboard');
      }
      $items = [
          ['href' => $home, 'icon' => 'bi-grid', 'label' => 'Dashboard', 'active' => request()->routeIs('*.dashboard') || request()->is('admin/home')],
          ['href' => Route::has('students.index') ? route('students.index') : $home, 'icon' => 'bi-people', 'label' => 'Students', 'active' => request()->is('students*')],
          ['href' => Route::has('finance.dashboard') && \App\Support\NavAccess::canDashboard('finance.dashboard') ? route('finance.dashboard') : (Route::has('finance.invoices.index') ? route('finance.invoices.index') : $home), 'icon' => 'bi-cash-stack', 'label' => 'Finance', 'active' => request()->is('finance*'), 'hide' => ! \App\Support\NavAccess::can('finance')],
          ['href' => Route::has('reports.class-reports.index') ? route('reports.class-reports.index') : $home, 'icon' => 'bi-bar-chart', 'label' => 'Reports', 'active' => request()->is('weekly-reports*') || request()->is('reports*')],
      ];
  }
@endphp
<nav class="erp-bottom-nav" aria-label="Mobile primary">
  @foreach($items as $item)
    @continue(!empty($item['hide']))
    <a href="{{ $item['href'] }}" class="{{ !empty($item['active']) ? 'active' : '' }}">
      <i class="bi {{ $item['icon'] }}"></i> {{ $item['label'] }}
    </a>
  @endforeach
  <a href="#" id="mobileMoreToggle">
    <i class="bi bi-three-dots"></i> More
  </a>
</nav>
