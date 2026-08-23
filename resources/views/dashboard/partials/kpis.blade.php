<div class="row g-3 mb-3">
@php
    $n = fn($v, $d = 0) => format_number($v, $d);
    $m = fn($v) => format_money($v);
    $roleName = $role ?? 'admin';
    $showFinanceKpis = in_array($roleName, ['admin', 'finance']);
    $cards = [
        [
            'label' => 'Total Students',
            'value' => $n($kpis['students'] ?? 0),
            'icon'  => 'bi-people',
            'delta' => $kpis['students_delta'] ?? null,
            'muted' => 'Active students',
            'scope' => 'overview',
            'href'  => Route::has('students.index') ? route('students.index') : null,
            'hide'  => in_array($roleName, ['finance', 'teacher']),
        ],
        [
            'label' => 'Staff Members',
            'value' => $n($kpis['staff_active'] ?? 0),
            'icon'  => 'bi-person-badge',
            'muted' => ($kpis['teachers_on_leave'] ?? 0) ? ($n($kpis['teachers_on_leave']).' on leave today') : 'Active staff',
            'scope' => 'today',
            'href'  => Route::has('staff.index') ? route('staff.index') : null,
            'hide'  => in_array($roleName, ['teacher', 'finance']),
        ],
        [
            'label' => 'Total Invoiced',
            'value' => $m($kpis['total_invoiced'] ?? 0),
            'icon'  => 'bi-receipt',
            'muted' => ($kpis['collection_rate'] ?? null) !== null ? ($n($kpis['collection_rate'], 1).'% collected') : 'Selected term',
            'scope' => 'term',
            'hide'  => ! $showFinanceKpis,
            'clickable' => true,
        ],
        [
            'label' => 'Total Paid',
            'value' => $m($kpis['fees_collected'] ?? 0),
            'icon'  => 'bi-cash-coin',
            'muted' => ($kpis['finance_scope'] ?? '') === 'term' ? 'Term collections' : 'Selected dates',
            'scope' => 'term',
            'hide'  => ! $showFinanceKpis,
            'href'  => Route::has('finance.payments.index') ? route('finance.payments.index') : null,
        ],
        [
            'label' => 'Total Outstanding',
            'value' => $m($kpis['fees_outstanding'] ?? 0),
            'icon'  => 'bi-wallet2',
            'muted' => ($kpis['owing_students'] ?? 0) ? ($n($kpis['owing_students']).' students owing') : 'Unpaid balance',
            'scope' => 'term',
            'hide'  => ! $showFinanceKpis,
            'href'  => Route::has('finance.fee-balances.index') ? route('finance.fee-balances.index') : null,
        ],
        [
            'label' => 'Overdue',
            'value' => $m($kpis['fees_overdue'] ?? 0),
            'icon'  => 'bi-exclamation-triangle',
            'muted' => ($kpis['overdue_invoice_count'] ?? 0) ? ($n($kpis['overdue_invoice_count']).' invoices past due') : 'Due date has passed',
            'scope' => 'term',
            'hide'  => ! $showFinanceKpis,
            'href'  => Route::has('finance.fee-balances.index') ? route('finance.fee-balances.index') : null,
        ],
        [
            'label' => 'Present Today',
            'value' => isset($kpis['attendance_pct']) && $kpis['attendance_pct'] !== null ? $n($kpis['attendance_pct'], 1).'%' : '—',
            'icon'  => 'bi-clipboard-check',
            'muted' => empty($kpis['is_school_day'])
                ? 'Not a school day'
                : ($n($kpis['present_today'] ?? 0).' present · '.$n($kpis['absent_today'] ?? 0).' absent'),
            'scope' => 'today',
            'href'  => Route::has('attendance.records') ? route('attendance.records') : null,
            'hide'  => $roleName === 'finance',
        ],
        [
            'label' => 'Payments Today',
            'value' => $m($kpis['payments_today'] ?? 0),
            'icon'  => 'bi-cash-stack',
            'muted' => 'Today-based',
            'scope' => 'today',
            'hide'  => ! $showFinanceKpis,
            'href'  => Route::has('finance.payments.index') ? route('finance.payments.index') : null,
        ],
    ];
@endphp
@foreach($cards as $card)
    @continue(!empty($card['hide']))
    <div class="col-6 col-lg-4 col-xxl-3">
      @php $tag = !empty($card['href']) ? 'a' : 'div'; @endphp
      <{{ $tag }} @if(!empty($card['href'])) href="{{ $card['href'] }}" @endif
         @if(!empty($card['clickable'])) data-bs-toggle="modal" data-bs-target="#voteheadBreakdownModal" style="cursor:pointer" @endif
         class="dash-card card h-100 erp-kpi text-decoration-none">
        <div class="card-body d-flex">
          <div class="flex-grow-1">
            <div class="dash-muted small mb-1">{{ $card['label'] }}</div>
            <div class="erp-kpi-value">{{ $card['value'] }}</div>
            @if(!empty($card['muted']))
              <div class="dash-muted small">{{ $card['muted'] }}</div>
            @endif
          </div>
          <span class="dash-kpi-icon"><i class="bi {{ $card['icon'] }}"></i></span>
        </div>
      </{{ $tag }}>
    </div>
@endforeach
</div>
