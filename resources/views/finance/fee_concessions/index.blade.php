@extends('layouts.app')

@section('content')
<div class="container-fluid">
    @include('finance.partials.header', [
        'title' => 'Fee Concessions',
        'icon' => 'bi bi-tag-fill',
        'subtitle' => 'Manage fee discounts and concessions for students',
        'actions' => '<a href="' . route('finance.fee-concessions.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Create Concession</a>'
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
                <select name="student_id" class="finance-form-select">
                    <option value="">All Students</option>
                    @foreach(\App\Models\Student::orderBy('first_name')->get() as $student)
                        <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                            {{ $student->first_name }} {{ $student->last_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="finance-form-label">Status</label>
                <select name="is_active" class="finance-form-select">
                    <option value="">All</option>
                    <option value="1" {{ request('is_active') == '1' ? 'selected' : '' }}>Active</option>
                    <option value="0" {{ request('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
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
                            <th>Type</th>
                            <th>Value</th>
                            <th>Votehead</th>
                            <th>Reason</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($concessions as $concession)
                            <tr>
                                <td>{{ $concession->student->first_name }} {{ $concession->student->last_name }}</td>
                                <td>{{ ucfirst($concession->type) }}</td>
                                <td>
                                    {{ $concession->type == 'percentage' ? number_format($concession->value, 1) . '%' : 'KES ' . number_format($concession->value, 2) }}
                                </td>
                                <td>{{ $concession->votehead->name ?? 'All Voteheads' }}</td>
                                <td>{{ $concession->reason }}</td>
                                <td>{{ $concession->start_date->format('M d, Y') }}</td>
                                <td>{{ $concession->end_date ? $concession->end_date->format('M d, Y') : 'No End' }}</td>
                                <td>
                                    <span class="badge bg-{{ $concession->is_active ? 'success' : 'secondary' }}">
                                        {{ $concession->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('finance.fee-concessions.show', $concession) }}" class="btn btn-sm btn-primary">View</a>
                                    @if(!$concession->is_active)
                                        <form action="{{ route('finance.fee-concessions.approve', $concession) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                    @else
                                        <form action="{{ route('finance.fee-concessions.deactivate', $concession) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-warning">Deactivate</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9">
                                    <div class="finance-empty-state">
                                        <div class="finance-empty-state-icon">
                                            <i class="bi bi-tag-fill"></i>
                                        </div>
                                        <h4>No concessions found</h4>
                                        <p class="text-muted mb-3">Create your first fee concession to get started</p>
                                        <a href="{{ route('finance.fee-concessions.create') }}" class="btn btn-finance btn-finance-primary">
                                            <i class="bi bi-plus-circle"></i> Create Concession
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
        </div>
        @if($concessions->hasPages())
        <div class="finance-card-body" style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
            {{ $concessions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

