@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h3 class="mb-0">
                    <i class="bi bi-list-check"></i> Allocated Discounts
                </h3>
                <a href="{{ route('finance.discounts.allocate') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Allocate Discount
                </a>
            </div>
        </div>
    </div>

    @include('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('finance.discounts.allocations.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select">
                        <option value="">All Students</option>
                        @foreach($students as $student)
                            <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                {{ $student->first_name }} {{ $student->last_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Term</label>
                    <select name="term" class="form-select">
                        <option value="">All Terms</option>
                        <option value="1" {{ request('term') == '1' ? 'selected' : '' }}>Term 1</option>
                        <option value="2" {{ request('term') == '2' ? 'selected' : '' }}>Term 2</option>
                        <option value="3" {{ request('term') == '3' ? 'selected' : '' }}>Term 3</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Year</label>
                    <input type="number" name="year" class="form-control" value="{{ request('year') }}" placeholder="e.g., 2025">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="approval_status" class="form-select">
                        <option value="">All</option>
                        <option value="pending" {{ request('approval_status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('approval_status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('approval_status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('finance.discounts.allocations.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Allocations Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Template</th>
                            <th>Votehead</th>
                            <th>Term/Year</th>
                            <th class="text-end">Value</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($allocations as $allocation)
                        <tr>
                            <td>
                                @if($allocation->student)
                                    {{ $allocation->student->first_name }} {{ $allocation->student->last_name }}
                                    <br><small class="text-muted">{{ $allocation->student->admission_number }}</small>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($allocation->discountTemplate)
                                    {{ $allocation->discountTemplate->name }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if($allocation->votehead)
                                    {{ $allocation->votehead->name }}
                                @else
                                    <span class="text-muted">All</span>
                                @endif
                            </td>
                            <td>
                                Term {{ $allocation->term }} / {{ $allocation->year }}
                                @if($allocation->academicYear)
                                    <br><small class="text-muted">{{ $allocation->academicYear->year }}</small>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($allocation->type === 'percentage')
                                    <strong>{{ number_format($allocation->value, 1) }}%</strong>
                                @else
                                    <strong>Ksh {{ number_format($allocation->value, 2) }}</strong>
                                @endif
                            </td>
                            <td>
                                @if($allocation->approval_status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($allocation->approval_status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>
                            <td>
                                {{ $allocation->created_at->format('d M Y') }}
                                @if($allocation->creator)
                                    <br><small class="text-muted">by {{ $allocation->creator->name }}</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('finance.discounts.show', $allocation) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <p class="text-muted mb-0">No allocations found.</p>
                                <a href="{{ route('finance.discounts.allocate') }}" class="btn btn-primary btn-sm mt-2">
                                    <i class="bi bi-plus-circle"></i> Allocate First Discount
                                </a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($allocations->hasPages())
        <div class="card-footer">
            {{ $allocations->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

