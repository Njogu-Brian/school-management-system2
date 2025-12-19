@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex align-items-start justify-content-between flex-wrap gap-3">
            <div>
                <div class="crumb">HR & Payroll / Reports</div>
                <h1 class="mb-1">Staff Reports</h1>
                <p class="text-muted mb-0">Generate and export staff reports.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('hr.analytics.index') }}" class="btn btn-ghost-strong">
                    <i class="bi bi-graph-up"></i> Analytics Dashboard
                </a>
                <span class="pill-badge pill-secondary">Exports are instant</span>
            </div>
        </div>

        @include('partials.alerts')

        <div class="row g-3">
            {{-- Staff Directory Export --}}
            <div class="col-md-6 col-lg-4">
                <div class="settings-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="pill-icon pill-primary me-3">
                                <i class="bi bi-people"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Staff Directory</h5>
                                <small class="text-muted">Complete staff listing</small>
                            </div>
                        </div>
                        <p class="text-muted small">Export a comprehensive directory of all staff members with their contact details, departments, and employment information.</p>
                        <form action="{{ route('hr.reports.directory') }}" method="GET" class="mt-3">
                            <div class="mb-2">
                                <label class="form-label small">Department</label>
                                <select name="department_id" class="form-select form-select-sm">
                                    <option value="">All Departments</option>
                                    @foreach(\App\Models\Department::all() as $dept)
                                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Category</label>
                                <select name="category_id" class="form-select form-select-sm">
                                    <option value="">All Categories</option>
                                    @foreach(\App\Models\StaffCategory::all() as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Status</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-settings-primary btn-sm w-100">
                                <i class="bi bi-download"></i> Export Directory
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Department Report --}}
            <div class="col-md-6 col-lg-4">
                <div class="settings-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="pill-icon pill-info me-3">
                                <i class="bi bi-building"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Department Report</h5>
                                <small class="text-muted">Staff by department</small>
                            </div>
                        </div>
                        <p class="text-muted small">Generate a report listing all staff members organized by their departments.</p>
                        <form action="{{ route('hr.reports.department') }}" method="GET" class="mt-3">
                            <button type="submit" class="btn btn-info btn-sm w-100">
                                <i class="bi bi-download"></i> Export Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Category Report --}}
            <div class="col-md-6 col-lg-4">
                <div class="settings-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="pill-icon pill-success me-3">
                                <i class="bi bi-tags"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Category Report</h5>
                                <small class="text-muted">Staff by category</small>
                            </div>
                        </div>
                        <p class="text-muted small">Generate a report listing all staff members organized by their categories.</p>
                        <form action="{{ route('hr.reports.category') }}" method="GET" class="mt-3">
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-download"></i> Export Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- New Hires Report --}}
            <div class="col-md-6 col-lg-4">
                <div class="settings-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="pill-icon pill-warning me-3">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">New Hires</h5>
                                <small class="text-muted">Recent staff additions</small>
                            </div>
                        </div>
                        <p class="text-muted small">Export a report of newly hired staff members within a specified date range.</p>
                        <form action="{{ route('hr.reports.new-hires') }}" method="GET" class="mt-3">
                            <div class="mb-2">
                                <label class="form-label small">Start Date</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ date('Y-01-01') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">End Date</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                            </div>
                            <button type="submit" class="btn btn-warning btn-sm w-100">
                                <i class="bi bi-download"></i> Export Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Terminations Report --}}
            <div class="col-md-6 col-lg-4">
                <div class="settings-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="pill-icon pill-danger me-3">
                                <i class="bi bi-person-x"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Terminations</h5>
                                <small class="text-muted">Staff terminations</small>
                            </div>
                        </div>
                        <p class="text-muted small">Export a report of staff terminations within a specified date range.</p>
                        <form action="{{ route('hr.reports.terminations') }}" method="GET" class="mt-3">
                            <div class="mb-2">
                                <label class="form-label small">Start Date</label>
                                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ date('Y-01-01') }}">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">End Date</label>
                                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ date('Y-m-d') }}">
                            </div>
                            <button type="submit" class="btn btn-danger btn-sm w-100">
                                <i class="bi bi-download"></i> Export Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Turnover Analysis --}}
            <div class="col-md-6 col-lg-4">
                <div class="settings-card h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="pill-icon pill-secondary me-3">
                                <i class="bi bi-graph-down"></i>
                            </div>
                            <div>
                                <h5 class="mb-0">Turnover Analysis</h5>
                                <small class="text-muted">Staff turnover metrics</small>
                            </div>
                        </div>
                        <p class="text-muted small">Analyze staff turnover rates, new hires, and terminations for a specific year.</p>
                        <form action="{{ route('hr.reports.turnover') }}" method="GET" class="mt-3">
                            <div class="mb-2">
                                <label class="form-label small">Year</label>
                                <input type="number" name="year" class="form-control form-control-sm" value="{{ date('Y') }}" min="2020" max="{{ date('Y') + 1 }}">
                            </div>
                            <button type="submit" class="btn btn-secondary btn-sm w-100">
                                <i class="bi bi-download"></i> Export Analysis
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

