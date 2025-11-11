@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">Set Leave Balance</h2>
            <small class="text-muted">Set leave balance for staff member</small>
        </div>
        <a href="{{ route('staff.leave-balances.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Leave Balance Information</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('staff.leave-balances.store') }}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Staff <span class="text-danger">*</span></label>
                        <select name="staff_id" class="form-select" required>
                            <option value="">-- Select Staff --</option>
                            @foreach($staff as $s)
                                <option value="{{ $s->id }}" @selected(old('staff_id', $staffId) == $s->id)>{{ $s->full_name }} ({{ $s->staff_id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="form-select" required>
                            <option value="">-- Select Leave Type --</option>
                            @foreach($leaveTypes as $type)
                                <option value="{{ $type->id }}" @selected(old('leave_type_id') == $type->id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Academic Year <span class="text-danger">*</span></label>
                        <select name="academic_year_id" class="form-select" required>
                            <option value="">-- Select Academic Year --</option>
                            @foreach(\App\Models\AcademicYear::orderBy('year', 'desc')->get() as $year)
                                <option value="{{ $year->id }}" @selected(old('academic_year_id', $currentYear?->id) == $year->id)>{{ $year->year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Entitlement Days <span class="text-danger">*</span></label>
                        <input type="number" name="entitlement_days" class="form-control" value="{{ old('entitlement_days') }}" required min="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Carried Forward Days</label>
                        <input type="number" name="carried_forward" class="form-control" value="{{ old('carried_forward', 0) }}" min="0">
                        <small class="text-muted">Days carried from previous year</small>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="{{ route('staff.leave-balances.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> Create Balance
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

