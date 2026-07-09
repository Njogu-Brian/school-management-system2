@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
    @include('hr.payroll.partials.styles')
@endpush

@section('content')
<div class="settings-page payroll-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Payroll / Imports</div>
                <h1 class="mb-1">Import Budget PDF</h1>
                <p class="text-muted mb-0">Dry-run parse and safely match staff before committing.</p>
            </div>
            <a href="{{ route('hr.payroll.periods.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back to Periods
            </a>
        </div>

        @include('partials.alerts')

        <div class="settings-card">
            <div class="card-header">
                <h5 class="mb-0">Upload</h5>
                <p class="text-muted small mb-0">PDF must be the active staff budget for the month.</p>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('hr.payroll.imports.budget.parse') }}" enctype="multipart/form-data" class="row g-3">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label">Budget PDF</label>
                        <input type="file" name="pdf" class="form-control" accept="application/pdf" required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Year</label>
                        <input type="number" name="year" class="form-control" value="{{ now()->year }}" required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Month</label>
                        <input type="number" name="month" class="form-control" value="{{ now()->month }}" min="1" max="12" required />
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Pay Date</label>
                        <input type="date" name="pay_date" class="form-control" value="{{ now()->toDateString() }}" required />
                    </div>
                    <div class="col-12">
                        <button class="btn btn-settings-primary" type="submit">
                            <i class="bi bi-search"></i> Parse &amp; Preview
                        </button>
                    </div>
                </form>
                <div class="alert alert-soft border-0 mt-3 mb-0">
                    This importer will block commit if any staff row is ambiguous/unmatched.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

