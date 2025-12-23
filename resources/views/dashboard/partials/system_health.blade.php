<div class="dash-card card">
  <div class="card-header"><strong>System Health</strong></div>
  <div class="card-body small">
    <div class="d-flex justify-content-between">
      <div>Jobs queue</div><div class="{{ $health['queue_ok'] ? 'text-success':'text-danger' }}">{{ $health['queue_ok'] ? 'OK' : 'Delayed' }}</div>
    </div>
    <div class="d-flex justify-content-between">
      <div>SMS/Email gateway</div><div class="{{ $health['gateway_ok'] ? 'text-success':'text-danger' }}">{{ $health['gateway_ok'] ? 'Connected' : 'Error' }}</div>
    </div>
    <div class="d-flex justify-content-between">
      <div>Last backup</div><div>{{ $health['last_backup_for_humans'] }}</div>
    </div>
  </div>
</div>
