<div class="dash-card card h-100">
  <div class="card-header"><strong>Exam Performance (Avg by Subject)</strong></div>
  <div class="card-body">
    @if(empty($charts['exam']['labels']) || count($charts['exam']['labels']) === 0)
      <div class="erp-empty"><i class="bi bi-journal-check"></i>No exam marks for the selected period.</div>
    @else
      <canvas id="examBar" height="140"></canvas>
    @endif
  </div>
</div>
