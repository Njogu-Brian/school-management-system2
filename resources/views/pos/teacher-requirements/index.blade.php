@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Student Requirements Tracker</h1>
            <p class="text-muted mb-0">Track requirements including POS purchases and outside purchases</p>
        </div>
        <a href="{{ route('inventory.student-requirements.collect') }}" class="btn btn-primary">
            <i class="bi bi-clipboard-check"></i> Collect / Verify
        </a>
    </div>

    @include('partials.alerts')

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Requirements</h6>
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Complete</h6>
                    <h3 class="mb-0">{{ $stats['complete'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="card-title">Pending</h6>
                    <h3 class="mb-0">{{ $stats['pending'] }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">POS Purchases</h6>
                    <h3 class="mb-0">{{ $stats['pos_purchases'] }}</h3>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="card card-body mb-4">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Classroom</label>
                <select name="classroom_id" class="form-select">
                    <option value="">All</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" @selected(request('classroom_id') == $classroom->id)>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Any</option>
                    <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                    <option value="partial" @selected(request('status') === 'partial')>Partial</option>
                    <option value="complete" @selected(request('status') === 'complete')>Complete</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Purchase Source</label>
                <select name="purchase_source" class="form-select">
                    <option value="">All</option>
                    <option value="pos" @selected(request('purchase_source') === 'pos')>POS Purchase</option>
                    <option value="outside" @selected(request('purchase_source') === 'outside')>Outside Purchase</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Academic Year</label>
                <select name="academic_year_id" class="form-select">
                    <option value="">All</option>
                    @foreach($academicYears as $year)
                        <option value="{{ $year->id }}" @selected(request('academic_year_id') == $year->id)>
                            {{ $year->year }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Term</label>
                <select name="term_id" class="form-select">
                    <option value="">All</option>
                    @foreach($terms as $term)
                        <option value="{{ $term->id }}" @selected(request('term_id') == $term->id)>
                            {{ $term->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 text-end">
                <button class="btn btn-secondary" type="submit">Filter</button>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Requirement</th>
                        <th>Purchase Source</th>
                        <th>Status</th>
                        <th class="text-end">Collected</th>
                        <th class="text-end">Missing</th>
                        <th>Updated</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requirements as $requirement)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $requirement->student->first_name }} {{ $requirement->student->last_name }}</div>
                                <div class="small text-muted">{{ $requirement->student->admission_number }} - {{ $requirement->student->classroom->name ?? '—' }}</div>
                            </td>
                            <td>
                                <div>{{ $requirement->requirementTemplate->requirementType->name }}</div>
                                @if($requirement->requirementTemplate->posProduct)
                                    <div class="small">
                                        <i class="bi bi-shop text-primary"></i> Available in Shop
                                    </div>
                                @endif
                                @if($requirement->requirementTemplate->brand)
                                    <div class="small text-muted">{{ $requirement->requirementTemplate->brand }}</div>
                                @endif
                            </td>
                            <td>
                                @if($requirement->purchased_through_pos)
                                    <span class="badge bg-success">
                                        <i class="bi bi-shop"></i> POS Purchase
                                    </span>
                                    @if($requirement->posOrder)
                                        <div class="small text-muted mt-1">Order: {{ $requirement->posOrder->order_number }}</div>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-bag"></i> Outside Purchase
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($requirement->status === 'complete')
                                    <span class="badge bg-success">Complete</span>
                                @elseif($requirement->status === 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-secondary">Pending</span>
                                @endif
                            </td>
                            <td class="text-end">
                                {{ number_format($requirement->quantity_collected, 2) }} / {{ number_format($requirement->quantity_required, 2) }}
                                <div class="small text-muted">{{ $requirement->requirementTemplate->unit }}</div>
                            </td>
                            <td class="text-end">
                                <span class="{{ $requirement->quantity_missing > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format(max($requirement->quantity_missing, 0), 2) }}
                                </span>
                            </td>
                            <td>
                                @if($requirement->collected_at)
                                    {{ $requirement->collected_at->format('M d, Y') }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('pos.teacher-requirements.show', $requirement) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                <p class="mt-2">No requirement records found</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requirements->hasPages())
            <div class="card-footer">
                {{ $requirements->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection



