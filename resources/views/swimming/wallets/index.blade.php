@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Swimming Wallets',
        'icon' => 'bi bi-water',
        'subtitle' => 'View and manage student swimming wallet balances',
        'actions' => '<a href="' . route('swimming.payments.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Record Payment</a>'
    ])

    @include('finance.invoices.partials.alerts')

    <!-- Filters -->
    <div class="finance-filter-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <form method="GET" action="{{ route('swimming.wallets.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="finance-form-label">Search</label>
                <input type="text" 
                       name="search" 
                       class="finance-form-control" 
                       value="{{ $filters['search'] ?? '' }}" 
                       placeholder="Student name or admission number">
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Classroom</label>
                <select name="classroom_id" class="finance-form-select">
                    <option value="">All Classrooms</option>
                    @foreach($classrooms as $classroom)
                        <option value="{{ $classroom->id }}" {{ ($filters['classroom_id'] ?? '') == $classroom->id ? 'selected' : '' }}>
                            {{ $classroom->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="finance-form-label">Balance Filter</label>
                <select name="balance_filter" class="finance-form-select">
                    <option value="">All Balances</option>
                    <option value="positive" {{ ($filters['balance_filter'] ?? '') == 'positive' ? 'selected' : '' }}>Positive Balance</option>
                    <option value="zero" {{ ($filters['balance_filter'] ?? '') == 'zero' ? 'selected' : '' }}>Zero Balance</option>
                    <option value="negative" {{ ($filters['balance_filter'] ?? '') == 'negative' ? 'selected' : '' }}>Negative Balance</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-finance btn-finance-primary flex-fill">
                    <i class="bi bi-funnel"></i> Filter
                </button>
                <a href="{{ route('swimming.wallets.index') }}" class="btn btn-finance btn-finance-secondary" title="Clear Filters">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Wallets Table -->
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0">Student Wallets</h5>
                <p class="text-muted small mb-0">{{ $wallets->total() }} wallet(s) found</p>
            </div>
            @if(auth()->user()->hasAnyRole(['Super Admin', 'Admin']))
            <form method="POST" action="{{ route('swimming.wallets.credit-from-optional-fees') }}" onsubmit="return confirm('Credit wallets for all students who have fully paid their swimming optional fees? This will add the optional fee amount to their wallets if not already credited.');">
                @csrf
                <button type="submit" class="btn btn-finance btn-finance-info">
                    <i class="bi bi-wallet2"></i> Credit Wallets from Optional Fees
                </button>
            </form>
            @endif
        </div>
        <div class="finance-card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Admission #</th>
                            <th>Student Name</th>
                            <th>Classroom</th>
                            <th class="text-end">Balance</th>
                            <th class="text-end">Total Credited</th>
                            <th class="text-end">Total Debited</th>
                            <th>Last Transaction</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wallets as $wallet)
                            <tr>
                                <td>
                                    <strong>{{ $wallet->student->admission_number ?? 'N/A' }}</strong>
                                </td>
                                <td>
                                    {{ $wallet->student->first_name ?? '' }} {{ $wallet->student->last_name ?? '' }}
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $wallet->student->classroom->name ?? 'N/A' }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $wallet->balance >= 0 ? 'text-success' : 'text-danger' }}">
                                        Ksh {{ number_format($wallet->balance, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="text-success">Ksh {{ number_format($wallet->total_credited, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-danger">Ksh {{ number_format($wallet->total_debited, 2) }}</span>
                                </td>
                                <td>
                                    @if($wallet->last_transaction_at)
                                        <small class="text-muted">{{ $wallet->last_transaction_at->format('M d, Y') }}</small>
                                    @else
                                        <small class="text-muted">Never</small>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('swimming.wallets.show', $wallet->student) }}" 
                                       class="btn btn-sm btn-finance btn-finance-outline" 
                                       title="View Wallet Details">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-3 mb-0">No wallets found</p>
                                        <small>Try adjusting your filters</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($wallets->hasPages())
            <div class="finance-card-footer">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        Showing {{ $wallets->firstItem() }} to {{ $wallets->lastItem() }} of {{ $wallets->total() }} wallets
                    </div>
                    <div>
                        {{ $wallets->links() }}
                    </div>
                </div>
            </div>
        @endif
    </div>
  </div>
</div>
@endsection
