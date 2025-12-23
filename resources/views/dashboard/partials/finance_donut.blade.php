<div class="dash-card card h-100">
    <div class="card-header">
        <strong>Finance Snapshot</strong>
    </div>

    <div class="card-body">
        <canvas id="financeDonut" height="160"></canvas>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const canvas = document.getElementById('financeDonut');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    const rootStyles = getComputedStyle(document.body);
    const colPrimary = rootStyles.getPropertyValue('--brand-primary') || '#0f766e';
    const colAccent = rootStyles.getPropertyValue('--brand-accent') || '#14b8a6';

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Collected', 'Outstanding'],
            datasets: [{
                data: [
                    {{ (float)($kpis['fees_collected'] ?? 0) }},
                    {{ (float)($kpis['fees_outstanding'] ?? 0) }}
                ],
                backgroundColor: [colPrimary.trim(), colAccent.trim()],
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
