<div class="dash-card card h-100">
    <div class="card-header">
        <strong>Attendance Summary</strong>
    </div>

    <div class="card-body text-center">
        {{-- Placeholder message or chart --}}
        <p class="dash-muted mb-2">
            Attendance trend chart or summary will appear here.
        </p>

        {{-- Example dummy data (safe to remove later) --}}
        <ul class="list-unstyled small text-muted">
            <li>Present Today: {{ $attendance['present_today'] ?? 0 }}</li>
            <li>Absent Today: {{ $attendance['absent_today'] ?? 0 }}</li>
            <li>Total Students: {{ $attendance['students_total'] ?? 0 }}</li>
        </ul>

        {{-- Optional: Canvas for Chart.js --}}
        <canvas id="attendanceLineChart" height="120"></canvas>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('attendanceLineChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Mon','Tue','Wed','Thu','Fri','Sat'],
            datasets: [{
                label: 'Attendance Trend',
                data: [12, 15, 13, 17, 19, 14],
                fill: false,
                borderColor: '#007bff',
                tension: 0.1
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
});
</script>
@endpush
