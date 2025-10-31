<div class="row g-3 mb-3">
@php
    // Readable numbers & money (helpers from app/helpers.php)
    $n = fn($v, $d = 0) => format_number($v, $d);
    $m = fn($v) => format_money($v);

    // Build your KPI cards (values already computed in the controller's $kpis array)
    $cards = [
        [
            'label' => 'Total Students',
            'value' => $n($kpis['students']),
            'icon'  => 'bi-people',
            'delta'=> $kpis['students_delta'] ?? null,
            'muted'=> null,
        ],
        [
            'label' => 'Present Today',
            'value' => $n($kpis['present_today']),
            'icon'  => 'bi-person-check',
            'delta'=> $kpis['attendance_delta'] ?? null,
            'muted'=> 'of '. $n(($kpis['students'] ?? 0)),
        ],
        [
            'label' => 'Absent Today',
            'value' => $n($kpis['absent_today']),
            'icon'  => 'bi-person-x',
            'delta'=> $kpis['attendance_delta'] ?? null,
            'muted'=> 'of '. $n(($kpis['students'] ?? 0)),
        ],
        [
            'label' => 'Fees Collected',
            'value' => $m($kpis['fees_collected'] ?? 0),
            'icon'  => 'bi-cash-coin',
            'delta'=> $kpis['fees_delta'] ?? null,
            'muted'=> "Last 30 days",
            'hide' => !in_array(($role ?? 'admin'), ['admin','finance']),
        ],
        [
            'label' => 'Outstanding Fees',
            'value' => $m($kpis['fees_outstanding'] ?? 0),
            'icon'  => 'bi-wallet2',
            'delta'=> $kpis['fees_delta'] ?? null,
            'muted'=> null,
            'hide' => !in_array(($role ?? 'admin'), ['admin','finance']),
        ],
        [
            'label' => 'Teachers on Leave',
            'value' => $n($kpis['teachers_on_leave'] ?? 0),
            'icon'  => 'bi-calendar2-week',
            'delta'=> null,
            'muted'=> 'today',
        ],
    ];
@endphp

@foreach($cards as $card)
    @continue(!empty($card['hide']))
    <div class="col-12 col-sm-6 col-lg-4">
        <div class="card h-100 shadow-sm border-0">
            <div class="card-body d-flex">
                <div class="flex-grow-1">
                    <div class="text-muted small mb-1">{{ $card['label'] }}</div>
                    <div class="fs-4 fw-semibold">{{ $card['value'] }}</div>
                    @if(!empty($card['muted']))
                        <div class="text-muted small">{{ $card['muted'] }}</div>
                    @endif
                </div>

                <div class="ms-3 d-flex align-items-start">
                    <span class="badge rounded-circle p-3 bg-light border">
                        <i class="bi {{ $card['icon'] }} fs-5"></i>
                    </span>
                </div>
            </div>

            @if(!is_null($card['delta']))
                @php
                    $delta = (float)$card['delta'];
                    $deltaUp = $delta >= 0;
                @endphp
                <div class="card-footer bg-transparent border-0 pt-0">
                    <span class="small {{ $deltaUp ? 'text-success' : 'text-danger' }}">
                        <i class="bi {{ $deltaUp ? 'bi-arrow-up-right' : 'bi-arrow-down-right' }}"></i>
                        {{ $deltaUp ? '+' : '' }}{{ number_format($delta, 1) }}%
                    </span>
                    <span class="small text-muted"> vs. previous period</span>
                </div>
            @endif
        </div>
    </div>
@endforeach
</div>
