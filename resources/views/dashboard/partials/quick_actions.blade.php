@php
  $qa = [];
  if (Route::has('students.create') && nav_can('students')) {
      $qa[] = ['href' => route('students.create'), 'icon' => 'bi-person-plus', 'label' => 'Admit Student'];
  }
  if (Route::has('attendance.mark.form') && nav_can('attendance')) {
      $qa[] = ['href' => route('attendance.mark.form'), 'icon' => 'bi-clipboard-check', 'label' => 'Mark Attendance'];
  }
  if (Route::has('finance.payments.create') && nav_can('finance')) {
      $qa[] = ['href' => route('finance.payments.create'), 'icon' => 'bi-cash', 'label' => 'Record Payment'];
  }
  if (Route::has('finance.invoices.index') && nav_can('finance')) {
      $qa[] = ['href' => route('finance.invoices.index'), 'icon' => 'bi-receipt', 'label' => 'Create Invoice'];
  }
  if (Route::has('announcements.create') && nav_can('communication')) {
      $qa[] = ['href' => route('announcements.create'), 'icon' => 'bi-megaphone', 'label' => 'Send Communication'];
  } elseif (Route::has('communication.announcements.create') && nav_can('communication')) {
      $qa[] = ['href' => route('communication.announcements.create'), 'icon' => 'bi-megaphone', 'label' => 'Send Communication'];
  }
  if (Route::has('staff.create') && nav_can('hr')) {
      $qa[] = ['href' => route('staff.create'), 'icon' => 'bi-person-badge', 'label' => 'Add Staff'];
  }
  if (Route::has('transport.trips.create') && nav_can('transport')) {
      $qa[] = ['href' => route('transport.trips.create'), 'icon' => 'bi-geo', 'label' => 'Create Trip'];
  }
  if (Route::has('reports.class-reports.index')) {
      $qa[] = ['href' => route('reports.class-reports.index'), 'icon' => 'bi-bar-chart', 'label' => 'View Reports'];
  }
@endphp
@if(count($qa))
  <div class="erp-quick-grid">
    @foreach($qa as $action)
      <a class="erp-quick-btn" href="{{ $action['href'] }}">
        <i class="bi {{ $action['icon'] }}"></i>
        {{ $action['label'] }}
      </a>
    @endforeach
  </div>
@endif
