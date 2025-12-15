@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-percent"></i> Fee Discounts
                </h3>
                <div class="btn-group">
                    <a href="{{ route('finance.discounts.create') }}" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create Template
                    </a>
                    <a href="{{ route('finance.discounts.allocate') }}" class="btn btn-success">
                        <i class="bi bi-person-plus"></i> Allocate Discount
                    </a>
                    <a href="{{ route('finance.discounts.bulk-allocate-sibling') }}" class="btn btn-info">
                        <i class="bi bi-people"></i> Bulk Allocate Siblings
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body text-center">
                    <h5 class="text-primary">{{ \App\Models\DiscountTemplate::count() }}</h5>
                    <small class="text-muted">Templates</small>
                    <br>
                    <a href="{{ route('finance.discounts.templates.index') }}" class="btn btn-sm btn-outline-primary mt-2">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <h5 class="text-success">{{ \App\Models\FeeConcession::whereNotNull('discount_template_id')->where('approval_status', 'approved')->count() }}</h5>
                    <small class="text-muted">Approved Allocations</small>
                    <br>
                    <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-sm btn-outline-success mt-2">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <h5 class="text-warning">{{ \App\Models\FeeConcession::where('approval_status', 'pending')->count() }}</h5>
                    <small class="text-muted">Pending Approvals</small>
                    <br>
                    <a href="{{ route('finance.discounts.approvals.index') }}" class="btn btn-sm btn-outline-warning mt-2">Review</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <h5 class="text-info">{{ \App\Models\FeeConcession::whereNotNull('discount_template_id')->count() }}</h5>
                    <small class="text-muted">Total Allocations</small>
                    <br>
                    <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-sm btn-outline-info mt-2">View All</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.discounts.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All Students</option>
                        @foreach(\App\Models\Student::orderBy('first_name')->get() as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->first_name }} {{ $student->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Discount Type</label>
                    <select name="discount_type" class="form-select">
                        <option value="">All Types</option>
                        <option value="sibling" {{ request('discount_type') == 'sibling' ? 'selected' : '' }}>Sibling</option>
                        <option value="referral" {{ request('discount_type') == 'referral' ? 'selected' : '' }}>Referral</option>
                        <option value="early_repayment" {{ request('discount_type') == 'early_repayment' ? 'selected' : '' }}>Early Repayment</option>
                        <option value="transport" {{ request('discount_type') == 'transport' ? 'selected' : '' }}>Transport</option>
                        <option value="manual" {{ request('discount_type') == 'manual' ? 'selected' : '' }}>Manual</option>
                        <option value="other" {{ request('discount_type') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="is_active" class="form-select">
                        <option value="">All</option>
                        <option value="1" {{ request('is_active') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('is_active') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <a href="{{ route('finance.discounts.index') }}" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> 
        Use the quick links above to manage discount templates, allocations, and approvals.
    </div>
</div>
@endsection

