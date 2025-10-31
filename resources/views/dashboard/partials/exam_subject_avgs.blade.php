<div class="card shadow-sm h-100">
    <div class="card-header bg-white">
        <strong>Exam Subject Averages</strong>
    </div>

    <div class="card-body text-center">
        {{-- Placeholder message or chart --}}
        <p class="text-muted mb-2">
            Average scores per subject will appear here.
        </p>

        {{-- Example dummy data --}}
        <ul class="list-unstyled small text-muted">
            <li>Mathematics: {{ $examAverages['Math'] ?? 'N/A' }}</li>
            <li>English: {{ $examAverages['English'] ?? 'N/A' }}</li>
            <li>Science: {{ $examAverages['Science'] ?? 'N/A' }}</li>
        </ul>

        {{-- Chart.js bar chart placeholder --}}
        <canvas id="examSubjectAvgChart" height="140"></canvas>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('examSubjectAvgChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Math', 'English', 'Science', 'Social Studies', 'Kiswahili'],
            datasets: [{
                label: 'Average Marks',
                data: [78, 81, 73, 84, 79],
                backgroundColor: '#007bff',
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, max: 100 } }
        }
    });
});
</script>
@endpush
