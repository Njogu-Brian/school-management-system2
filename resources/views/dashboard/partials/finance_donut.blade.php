<div class="dash-card card h-100">
    <div class="card-header">
        <strong>Finance Snapshot</strong>
    </div>

    <div class="card-body">
        @if((float)($kpis['fees_collected'] ?? 0) <= 0 && (float)($kpis['fees_outstanding'] ?? 0) <= 0)
            <div class="erp-empty"><i class="bi bi-pie-chart"></i>No finance totals for this period.</div>
        @else
            <canvas id="financeDonut" height="160"></canvas>
        @endif
    </div>

    @php
        // Use helper added in app/helpers.php
        $formatMoney = fn($value) => format_money($value ?? 0);
    @endphp

    <div class="mt-2 small dash-muted text-center pb-3">
        Collected:
        <strong>{{ $formatMoney($kpis['fees_collected'] ?? 0) }}</strong> ·
        Outstanding:
        <strong>{{ $formatMoney($kpis['fees_outstanding'] ?? 0) }}</strong>
    </div>
</div>

