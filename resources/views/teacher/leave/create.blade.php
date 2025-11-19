@extends('layouts.app')

@section('content')
<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h2 class="mb-0">Request Leave</h2>
      <small class="text-muted">Submit a new leave request</small>
    </div>
    <a href="{{ route('teacher.leave.index') }}" class="btn btn-secondary">
      <i class="bi bi-arrow-left"></i> Back
    </a>
  </div>

  <div class="row g-3">
    {{-- Leave Balances --}}
    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
          <h5 class="mb-0"><i class="bi bi-calendar-check"></i> Available Leaves</h5>
        </div>
        <div class="card-body">
          @forelse($leaveBalances as $balance)
            <div class="border-bottom pb-2 mb-2">
              <div class="d-flex justify-content-between align-items-center">
                <span class="fw-semibold">{{ $balance->leaveType->name }}</span>
                <span class="badge bg-primary">{{ $balance->remaining_days }} days</span>
              </div>
              <small class="text-muted">{{ $balance->entitlement_days }} total</small>
            </div>
          @empty
            <p class="text-muted mb-0">No leave balances available.</p>
          @endforelse
        </div>
      </div>
    </div>

    {{-- Leave Request Form --}}
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header">
          <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Leave Request Form</h5>
        </div>
        <div class="card-body">
          <form action="{{ route('teacher.leave.store') }}" method="POST" id="leaveForm">
            @csrf

            <div class="row">
              <div class="col-md-6 mb-3">
                <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                <select name="leave_type_id" class="form-select" required id="leave_type_id">
                  <option value="">-- Select Leave Type --</option>
                  @foreach($leaveTypes as $type)
                    <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>{{ $type->name }}</option>
                  @endforeach
                </select>
                @error('leave_type_id')
                  <div class="text-danger small">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">&nbsp;</label>
                <div id="leaveBalanceInfo" class="alert alert-info mb-0" style="display:none;">
                  <i class="bi bi-info-circle"></i> <span id="balanceText"></span>
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">Start Date <span class="text-danger">*</span></label>
                <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required id="start_date" min="{{ date('Y-m-d') }}">
                @error('start_date')
                  <div class="text-danger small">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-6 mb-3">
                <label class="form-label">End Date <span class="text-danger">*</span></label>
                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required id="end_date">
                <small class="text-muted" id="daysInfo"></small>
                @error('end_date')
                  <div class="text-danger small">{{ $message }}</div>
                @enderror
              </div>
              <div class="col-md-12 mb-3">
                <label class="form-label">Reason</label>
                <textarea name="reason" class="form-control" rows="3" placeholder="Reason for leave request...">{{ old('reason') }}</textarea>
                @error('reason')
                  <div class="text-danger small">{{ $message }}</div>
                @enderror
              </div>
            </div>

            <div class="d-flex justify-content-between">
              <a href="{{ route('teacher.leave.index') }}" class="btn btn-secondary">Cancel</a>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-circle"></i> Submit Request
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const startDate = document.getElementById('start_date');
  const endDate = document.getElementById('end_date');
  const daysInfo = document.getElementById('daysInfo');
  const leaveType = document.getElementById('leave_type_id');
  const balanceInfo = document.getElementById('leaveBalanceInfo');
  const balanceText = document.getElementById('balanceText');
  const balances = @json($leaveBalances->keyBy('leave_type_id'));

  function calculateDays() {
    if (startDate.value && endDate.value) {
      const start = new Date(startDate.value);
      const end = new Date(endDate.value);
      let days = 0;
      const current = new Date(start);

      while (current <= end) {
        const dayOfWeek = current.getDay();
        if (dayOfWeek !== 0 && dayOfWeek !== 6) { // Exclude weekends
          days++;
        }
        current.setDate(current.getDate() + 1);
      }

      daysInfo.textContent = `Working days: ${days} (excluding weekends)`;
      daysInfo.className = 'text-muted';
      
      // Check balance
      if (leaveType.value && balances[leaveType.value]) {
        const balance = balances[leaveType.value];
        if (balance.remaining_days < days) {
          daysInfo.className = 'text-danger';
          daysInfo.textContent += ` - Insufficient balance! Available: ${balance.remaining_days} days`;
        }
      }
    } else {
      daysInfo.textContent = '';
    }
  }

  leaveType.addEventListener('change', function() {
    if (this.value && balances[this.value]) {
      const balance = balances[this.value];
      balanceText.textContent = `Available: ${balance.remaining_days} / ${balance.entitlement_days} days`;
      balanceInfo.style.display = 'block';
    } else {
      balanceInfo.style.display = 'none';
    }
    calculateDays();
  });

  startDate.addEventListener('change', function() {
    if (endDate.value && endDate.value < startDate.value) {
      endDate.value = startDate.value;
    }
    endDate.min = startDate.value;
    calculateDays();
  });

  endDate.addEventListener('change', calculateDays);
});
</script>
@endsection

