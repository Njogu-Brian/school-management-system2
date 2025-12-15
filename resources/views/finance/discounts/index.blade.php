@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-percent"></i> Fee Discounts (Concessions)
                </h3>
                <a href="{{ route('finance.discounts.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Create Discount
                </a>
            </div>
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

    <!-- Discounts Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student/Family</th>
                            <th>Discount Type</th>
                            <th>Type</th>
                            <th>Scope</th>
                            <th class="text-end">Value</th>
                            <th>Frequency</th>
                            <th>Status</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($discounts as $discount)
                        <tr>
                            <td>
                                @if($discount->student)
                                    {{ $discount->student->first_name }} {{ $discount->student->last_name }}
                                    <br><small class="text-muted">{{ $discount->student->admission_number }}</small>
                                @elseif($discount->family)
                                    Family: {{ $discount->family->surname ?? 'N/A' }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-info">
                                    {{ ucfirst(str_replace('_', ' ', $discount->discount_type)) }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $discount->type === 'percentage' ? 'Percentage' : 'Fixed Amount' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    {{ ucfirst($discount->scope) }}
                                </span>
                            </td>
                            <td class="text-end">
                                @if($discount->type === 'percentage')
                                    <strong>{{ number_format($discount->value, 1) }}%</strong>
                                @else
                                    <strong>Ksh {{ number_format($discount->value, 2) }}</strong>
                                @endif
                            </td>
                            <td>{{ ucfirst($discount->frequency) }}</td>
                            <td>
                                <span class="badge bg-{{ $discount->is_active ? 'success' : 'danger' }}">
                                    {{ $discount->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $discount->start_date ? \Carbon\Carbon::parse($discount->start_date)->format('d M Y') : 'N/A' }}</td>
                            <td>{{ $discount->end_date ? \Carbon\Carbon::parse($discount->end_date)->format('d M Y') : 'No expiry' }}</td>
                            <td>
                                <a href="{{ route('finance.discounts.show', $discount) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-4">
                                <p class="text-muted mb-0">No discounts found.</p>
                                <a href="{{ route('finance.discounts.create') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus-circle"></i> Create First Discount
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($discounts->hasPages())
        <div class="card-footer">
            {{ $discounts->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

