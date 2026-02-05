@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Fee Payment Plans',
        'icon' => 'bi bi-calendar-check',
        'subtitle' => 'Manage installment payment plans for students',
        'actions' => '<a href="' . route('finance.fee-payment-plans.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Create Payment Plan</a>'
    ])

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="finance-filter-card finance-animate">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label class="finance-form-label">Student</label>
                @include('partials.student_live_search', [
                    'hiddenInputId' => 'student_id',
                    'displayInputId' => 'studentFilterSearchFPPIndex',
                    'resultsId' => 'studentFilterResultsFPPIndex',
                    'placeholder' => 'Type name or admission #',
                    'initialLabel' => request('student_id') ? optional(\App\Models\Student::find(request('student_id')))->search_display : ''
                ])
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">Status</label>
                <select name="status" class="finance-form-select">
                    <option value="">All</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-finance btn-finance-primary w-100">Filter</button>
            </div>
        </form>
    </div>

    <div class="finance-table-wrapper finance-animate">
        <div class="table-responsive">
            <table class="finance-table">
                <thead>
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
                                <td>{{ $plan->student->full_name }}</td>
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
                                <td colspan="8">
                                    <div class="finance-empty-state">
                                        <div class="finance-empty-state-icon">
                                            <i class="bi bi-calendar-check"></i>
                                        </div>
                                        <h4>No payment plans found</h4>
                                        <p class="text-muted mb-3">Create your first payment plan to get started</p>
                                        <a href="{{ route('finance.fee-payment-plans.create') }}" class="btn btn-finance btn-finance-primary">
                                            <i class="bi bi-plus-circle"></i> Create Payment Plan
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
        </div>
        @if($plans->hasPages())
        <div class="finance-card-body" style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            {{ $plans->links() }}
        </div>
        @endif
    </div>
  </div>
</div>
@endsection

