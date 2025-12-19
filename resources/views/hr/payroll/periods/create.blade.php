@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Payroll Periods</div>
                <h1 class="mb-1">New Payroll Period</h1>
                <p class="text-muted mb-0">Create a new payroll processing period.</p>
            </div>
            <a href="{{ route('hr.payroll.periods.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Periods
            </a>
        </div>

        <div class="settings-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="mb-0">Period Details</h5>
                    <p class="text-muted small mb-0">Define calendar range and pay date.</p>
                </div>
                <span class="pill-badge pill-secondary">Required fields *</span>
            </div>
            <div class="card-body">
                <form action="{{ route('hr.payroll.periods.store') }}" method="POST" class="row g-3">
                    @csrf

                    <div class="col-md-4">
                        <label class="form-label">Year <span class="text-danger">*</span></label>
                        <input type="number" name="year" min="2020" max="2100" class="form-control @error('year') is-invalid @enderror" value="{{ old('year', date('Y')) }}" required>
                        @error('year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Month <span class="text-danger">*</span></label>
                        <select name="month" class="form-select @error('month') is-invalid @enderror" required>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected(old('month', date('n'))==$m)>{{ date('F', mktime(0,0,0,$m,1)) }}</option>
                            @endfor
                        </select>
                        @error('month')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Pay Date <span class="text-danger">*</span></label>
                        <input type="date" name="pay_date" class="form-control @error('pay_date') is-invalid @enderror" value="{{ old('pay_date') }}" required>
                        <div class="form-text">Date when payroll will be paid</div>
                        @error('pay_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date') }}" required>
                        @error('start_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date') }}" required>
                        @error('end_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                        <a href="{{ route('hr.payroll.periods.index') }}" class="btn btn-ghost-strong">Cancel</a>
                        <button type="submit" class="btn btn-settings-primary">
                            <i class="bi bi-check-circle"></i> Create Period
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

