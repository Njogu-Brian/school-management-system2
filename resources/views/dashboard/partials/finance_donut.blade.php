<div class="card shadow-sm h-100">
    <div class="card-header bg-white">
        <strong>Finance Snapshot</strong>
    </div>

    <div class="card-body">
        <canvas id="financeDonut" height="160"></canvas>
    </div>

    @php
        // Use helper added in app/helpers.php
        $formatMoney = fn($value) => format_money($value ?? 0);
    @endphp

    <div class="mt-2 small text-muted text-center pb-3">
        Collected:
        <strong>{{ $formatMoney($kpis['fees_collected'] ?? 0) }}</strong> ·
        Outstanding:
        <strong>{{ $formatMoney($kpis['fees_outstanding'] ?? 0) }}</strong>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('financeDonut').getContext('2d');

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Collected', 'Outstanding'],
            datasets: [{
                data: [
                    {{ (float)($kpis['fees_collected'] ?? 0) }},
                    {{ (float)($kpis['fees_outstanding'] ?? 0) }}
                ],
                backgroundColor: ['#28a745', '#ffc107'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { color: '#6c757d' }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.parsed || 0;
                            return `${label}: {{ config('app.currency', 'KES') }} ${value.toLocaleString()}`;
                        }
                    }
                }
            }
        }
    });
});
</script>
@endpush
