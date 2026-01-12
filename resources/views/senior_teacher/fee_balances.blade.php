@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2><i class="bi bi-currency-exchange me-2"></i>Fee Balances</h2>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ route('senior_teacher.dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Fee Balances</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Alert --}}
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>
        <strong>View Only:</strong> You can view fee balances for supervised students but cannot collect fees, issue invoices, or apply discounts. Contact the finance department for fee-related transactions.
    </div>

    {{-- Filters --}}
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('senior_teacher.fee_balances') }}">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Classroom</label>
                        <select name="classroom_id" class="form-select">
                            <option value="">All Classrooms</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" {{ request('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                    {{ $classroom->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Balance Status</label>
                        <select name="balance_status" class="form-select">
                            <option value="">All</option>
                            <option value="with_balance" {{ request('balance_status') === 'with_balance' ? 'selected' : '' }}>With Balance</option>
                            <option value="cleared" {{ request('balance_status') === 'cleared' ? 'selected' : '' }}>Cleared</option>
                            <option value="overpaid" {{ request('balance_status') === 'overpaid' ? 'selected' : '' }}>Overpaid</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-search me-1"></i>Filter
                            </button>
                            <a href="{{ route('senior_teacher.fee_balances') }}" class="btn btn-secondary">
                                <i class="bi bi-x-circle me-1"></i>Clear
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    @php
        $totalInvoiced = 0;
        $totalPaid = 0;
        $totalBalance = 0;
        foreach($students as $student) {
            $totalInvoiced += $student->total_invoiced;
            $totalPaid += $student->total_paid;
            $totalBalance += $student->balance;
        }
    @endphp
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Invoiced</h6>
                    <h4 class="mb-0">KES {{ number_format($totalInvoiced, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Total Paid</h6>
                    <h4 class="mb-0 text-success">KES {{ number_format($totalPaid, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Outstanding Balance</h6>
                    <h4 class="mb-0 text-danger">KES {{ number_format($totalBalance, 2) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h6 class="text-muted mb-2">Students with Balance</h6>
                    <h4 class="mb-0 text-warning">{{ $students->where('balance', '>', 0)->count() }}</h4>
                </div>
            </div>
        </div>
    </div>

    {{-- Fee Balances Table --}}
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Student Fee Balances</h5>
            <span class="badge bg-primary">{{ $students->total() }} Students</span>
        </div>
        <div class="card-body">
            @if($students->isEmpty())
                <div class="text-center py-5">
                    <i class="bi bi-inbox fs-1 text-muted"></i>
                    <h4 class="mt-3 text-muted">No Records Found</h4>
                    <p class="text-muted">No students match your filter criteria.</p>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Admission No.</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th class="text-end">Total Invoiced</th>
                                <th class="text-end">Total Paid</th>
                                <th class="text-end">Balance</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($students as $student)
                                <tr>
                                    <td><strong>{{ $student->admission_number }}</strong></td>
                                    <td>{{ $student->full_name }}</td>
                                    <td>
                                        {{ $student->classroom->name ?? 'N/A' }}
                                        @if($student->stream)
                                            <br><small class="text-muted">{{ $student->stream->name }}</small>
                                        @endif
                                    </td>
                                    <td class="text-end">KES {{ number_format($student->total_invoiced, 2) }}</td>
                                    <td class="text-end text-success">KES {{ number_format($student->total_paid, 2) }}</td>
                                    <td class="text-end">
                                        <strong class="{{ $student->balance > 0 ? 'text-danger' : ($student->balance < 0 ? 'text-warning' : 'text-success') }}">
                                            KES {{ number_format($student->balance, 2) }}
                                        </strong>
                                    </td>
                                    <td>
                                        @if($student->balance > 0)
                                            <span class="badge bg-danger">Owing</span>
                                        @elseif($student->balance < 0)
                                            <span class="badge bg-warning">Overpaid</span>
                                        @else
                                            <span class="badge bg-success">Cleared</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('senior_teacher.students.show', $student->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="3">TOTALS (Current Page)</td>
                                <td class="text-end">KES {{ number_format($students->sum('total_invoiced'), 2) }}</td>
                                <td class="text-end text-success">KES {{ number_format($students->sum('total_paid'), 2) }}</td>
                                <td class="text-end text-danger">KES {{ number_format($students->sum('balance'), 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Pagination --}}
                <div class="mt-3">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

