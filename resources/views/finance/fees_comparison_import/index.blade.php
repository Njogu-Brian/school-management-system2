@extends('layouts.app')

@section('content')
<div class="finance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Fees Comparison Import',
            'icon' => 'bi bi-file-earmark-spreadsheet',
            'subtitle' => 'Upload an Excel sheet to compare your manual fees data with system totals (including balance brought forward). Comparison only — no changes are made.'
        ])

        <div class="alert alert-info alert-dismissible fade show finance-animate" role="alert">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle me-2 mt-1"></i>
                <div>
                    <strong>How it works</strong>
                    <ul class="mb-0 mt-1">
                        <li>Upload an Excel file with <strong>Student name</strong>, <strong>Admission number</strong>, and <strong>Total fees paid</strong>.</li>
                        <li>System totals use <strong>total fees invoice (including balance brought forward)</strong> and <strong>total paid</strong> per student for the selected year and term.</li>
                        <li>Siblings are processed together: if family total matches import total, that’s OK; we’ll flag where <strong>individual allocations differ</strong>. Family total mismatches are reported as issues.</li>
                        <li><strong>Missing students</strong> (in import but not in system) and other differences are highlighted. <strong>No actions</strong> are taken — this view is for comparison only.</li>
                    </ul>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
                <strong><i class="bi bi-exclamation-triangle me-1"></i> Validation error</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
                    <div class="finance-card-header d-flex align-items-center gap-2">
                        <i class="bi bi-upload"></i>
                        <span>Upload & Compare</span>
                    </div>
                    <div class="finance-card-body p-4">
                        <form method="POST" action="{{ route('finance.fees-comparison-import.preview') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="finance-form-label">Year</label>
                                    <input type="number" name="year" class="finance-form-control form-control" min="2020" max="2030" value="{{ old('year', $year) }}" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="finance-form-label">Term</label>
                                    <select name="term" class="finance-form-select form-select" required>
                                        @foreach([1 => 'Term 1', 2 => 'Term 2', 3 => 'Term 3'] as $num => $label)
                                            <option value="{{ $num }}" {{ (int) old('term', $term) === $num ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="finance-form-label">Excel file (.xlsx, .xls, .csv)</label>
                                    <input type="file" name="file" class="finance-form-control form-control" accept=".xlsx,.xls,.csv" required>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-2 mt-4">
                                <button type="submit" class="btn btn-finance btn-finance-primary">
                                    <i class="bi bi-eye"></i> Preview & Compare
                                </button>
                                <a href="{{ route('finance.fees-comparison-import.index') }}" class="btn btn-finance btn-finance-outline">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="finance-card shadow-sm rounded-4 border-0">
                    <div class="finance-card-header d-flex align-items-center gap-2">
                        <i class="bi bi-download"></i>
                        <span>Template</span>
                    </div>
                    <div class="finance-card-body p-4">
                        <p class="text-muted small mb-3">
                            Use the template to ensure correct column names: <strong>Student Name</strong>, <strong>Admission Number</strong>, <strong>Total Fees Paid</strong>.
                        </p>
                        <a href="{{ route('finance.fees-comparison-import.template') }}" class="btn btn-finance btn-finance-outline w-100">
                            <i class="bi bi-download"></i> Download template
                        </a>
                        <p class="text-muted small mt-3 mb-0">
                            Accepted column variants: <code>admission_no</code> / <code>adm_no</code>; <code>name</code> / <code>full_name</code>; <code>fees_paid</code> / <code>total_paid</code> / <code>amount</code>.
                        </p>
                    </div>
                </div>
                <div class="finance-card shadow-sm rounded-4 border-0 mt-4">
                    <div class="finance-card-header d-flex align-items-center gap-2">
                        <i class="bi bi-link-45deg"></i>
                        <span>Quick links</span>
                    </div>
                    <div class="finance-card-body p-4">
                        <a href="{{ route('finance.fee-balances.index') }}" class="d-block mb-2"><i class="bi bi-wallet2 me-1"></i> Fee Balance Report</a>
                        <a href="{{ route('finance.balance-brought-forward.index') }}" class="d-block mb-2"><i class="bi bi-arrow-left-circle me-1"></i> Balance Brought Forward</a>
                        <a href="{{ route('finance.student-statements.index') }}" class="d-block mb-0"><i class="bi bi-file-earmark-text me-1"></i> Student Statements</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
