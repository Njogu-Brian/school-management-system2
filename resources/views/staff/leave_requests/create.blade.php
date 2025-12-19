@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Staff</div>
                <h1 class="mb-1">New Leave Request</h1>
                <p class="text-muted mb-0">Submit a leave request for a staff member.</p>
            </div>
            <a href="{{ route('staff.leave-requests.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Leave Request Information</h5>
                    <p class="text-muted small mb-0">Select staff, leave type, and date range.</p>
                </div>
                <span class="pill-badge pill-secondary">Required fields *</span>
            </div>
            <div class="card-body">
                <form action="{{ route('staff.leave-requests.store') }}" method="POST" id="leaveForm" class="row g-3">
                    @csrf

                    <div class="col-md-6">
                        <label class="form-label">Staff <span class="text-danger">*</span></label>
                        <select name="staff_id" class="form-select" required>
                            <option value="">-- Select Staff --</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(old('staff_id') == $s->id)>{{ $s->full_name }} ({{ $s->staff_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="form-select" required id="leave_type_id">
                            <option value="">-- Select Leave Type --</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required id="start_date" min="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required id="end_date">
                        <small class="text-muted" id="daysInfo"></small>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Reason for leave request...">{{ old('reason') }}</textarea>
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('staff.leave-requests.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-circle"></i> Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    const daysInfo = document.getElementById('daysInfo');

    function calculateDays() {
        if (startDate.value && endDate.value) {
            const start = new Date(startDate.value);
            const end = new Date(endDate.value);
            let days = 0;
            const current = new Date(start);

            while (current <= end) {
                const dayOfWeek = current.getDay();
                if (dayOfWeek !== 0 && dayOfWeek !== 6) {
                    days++;
                }
                current.setDate(current.getDate() + 1);
            }

            daysInfo.textContent = `Working days: ${days} (excluding weekends)`;
        } else {
            daysInfo.textContent = '';
        }
    }

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

