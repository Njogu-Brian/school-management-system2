@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Fee Payment Plans</h1>
        <a href="{{ route('finance.fee-payment-plans.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Payment Plan
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" class="row g-3 mb-3">
                <div class="col-md-4">
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
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Total Amount</th>
                            <th>Installments</th>
                            <th>Installment Amount</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($plans as $plan)
                            <tr>
                                <td>{{ $plan->student->first_name }} {{ $plan->student->last_name }}</td>
                                <td>KES {{ number_format($plan->total_amount, 2) }}</td>
                                <td>{{ $plan->installment_count }}</td>
                                <td>KES {{ number_format($plan->installment_amount, 2) }}</td>
                                <td>{{ $plan->start_date->format('M d, Y') }}</td>
                                <td>{{ $plan->end_date->format('M d, Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $plan->status == 'active' ? 'success' : ($plan->status == 'completed' ? 'info' : 'secondary') }}">
                                        {{ ucfirst($plan->status) }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('finance.fee-payment-plans.show', $plan) }}" class="btn btn-sm btn-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No payment plans found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $plans->links() }}
        </div>
    </div>
</div>
@endsection

