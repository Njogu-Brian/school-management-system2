@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Swimming Wallet Details',
        'icon' => 'bi bi-water',
        'subtitle' => $student->full_name . ' (' . $student->admission_number . ')',
        'actions' => '<a href="' . route('swimming.wallets.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back to Wallets</a><a href="' . route('swimming.payments.create', ['student_id' => $student->id]) . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> Record Payment</a>'
    ])

    @include('finance.invoices.partials.alerts')

    {{-- Student Info --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-person-circle"></i> <span>Student Information</span>
        </div>
        <div class="finance-card-body p-4">
            <div class="row">
                <div class="col-md-3">
                    <strong>Name:</strong> {{ $student->full_name }}
                </div>
                <div class="col-md-3">
                    <strong>Admission Number:</strong> {{ $student->admission_number }}
                </div>
                <div class="col-md-3">
                    <strong>Class:</strong> {{ optional($student->classroom)->name ?? 'N/A' }}
                </div>
                <div class="col-md-3">
                    <strong>Stream:</strong> 
                    @if($student->stream_id && $student->stream)
                        {{ $student->stream->name }}
                    @else
                        N/A
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Wallet Summary --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="finance-stat-card {{ $wallet->balance >= 0 ? 'border-success' : 'border-danger' }} finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Current Balance</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700; color: {{ $wallet->balance >= 0 ? '#10b981' : '#dc3545' }};">
                            Ksh {{ number_format($wallet->balance, 2) }}
                        </h4>
                    </div>
                    <i class="bi bi-wallet2" style="font-size: 2rem; color: {{ $wallet->balance >= 0 ? '#10b981' : '#dc3545' }};"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="finance-stat-card border-success finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Credited</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">Ksh {{ number_format($wallet->total_credited, 2) }}</h4>
                    </div>
                    <i class="bi bi-arrow-down-circle" style="font-size: 2rem; color: var(--finance-success);"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="finance-stat-card border-danger finance-animate">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2" style="font-size: 0.8rem; font-weight: 600;">Total Debited</h6>
                        <h4 class="mb-0" style="font-size: 1.4rem; font-weight: 700;">Ksh {{ number_format($wallet->total_debited, 2) }}</h4>
                    </div>
                    <i class="bi bi-arrow-up-circle" style="font-size: 2rem; color: var(--finance-danger);"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Balance Adjustment Form (Admin Only) --}}
    @if(Auth::user()->hasAnyRole(['Super Admin', 'Admin']))
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-sliders"></i> <span>Adjust Balance</span>
        </div>
        <div class="finance-card-body">
            <form action="{{ route('swimming.wallets.adjust', $student) }}" method="POST">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="finance-form-label">Transaction Type <span class="text-danger">*</span></label>
                        <select name="type" class="finance-form-select @error('type') is-invalid @enderror" required>
                            <option value="">-- Select Type --</option>
                            <option value="credit" {{ old('type') == 'credit' ? 'selected' : '' }}>Credit (Add Funds)</option>
                            <option value="debit" {{ old('type') == 'debit' ? 'selected' : '' }}>Debit (Deduct Funds)</option>
                        </select>
                        @error('type')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="finance-form-label">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Ksh</span>
                            <input type="number" 
                                   name="amount" 
                                   step="0.01" 
                                   min="0.01" 
                                   class="finance-form-control @error('amount') is-invalid @enderror" 
                                   value="{{ old('amount') }}" 
                                   required>
                        </div>
                        @error('amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="finance-form-label">Description <span class="text-danger">*</span></label>
                        <input type="text" 
                               name="description" 
                               class="finance-form-control @error('description') is-invalid @enderror" 
                               value="{{ old('description') }}" 
                               placeholder="Reason for adjustment"
                               maxlength="500"
                               required>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-finance btn-finance-primary w-100">
                            <i class="bi bi-check-circle"></i> Adjust
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Transaction History --}}
    <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
            <i class="bi bi-list-ul"></i> <span>Transaction History</span>
        </div>
        <div class="finance-card-body p-0">
            <div class="table-responsive">
                <table class="table table-modern align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Source</th>
                            <th>Description</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Balance After</th>
                            <th>Created By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($wallet->ledgerEntries as $entry)
                            <tr>
                                <td>
                                    <small>{{ $entry->created_at->format('d M Y') }}</small><br>
                                    <small class="text-muted">{{ $entry->created_at->format('h:i A') }}</small>
                                </td>
                                <td>
                                    @if($entry->type === 'credit')
                                        <span class="badge bg-success">Credit</span>
                                    @else
                                        <span class="badge bg-danger">Debit</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $sourceLabels = [
                                            'transaction' => 'Payment',
                                            'optional_fee' => 'Optional Fee',
                                            'adjustment' => 'Adjustment',
                                            'attendance' => 'Attendance',
                                        ];
                                        $sourceLabel = $sourceLabels[$entry->source] ?? ucfirst($entry->source);
                                    @endphp
                                    <span class="badge bg-info">{{ $sourceLabel }}</span>
                                </td>
                                <td>
                                    {{ $entry->description ?? 'N/A' }}
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $entry->type === 'credit' ? 'text-success' : 'text-danger' }}">
                                        {{ $entry->type === 'credit' ? '+' : '-' }}Ksh {{ number_format($entry->amount, 2) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold {{ $entry->balance_after >= 0 ? 'text-success' : 'text-danger' }}">
                                        Ksh {{ number_format($entry->balance_after, 2) }}
                                    </span>
                                </td>
                                <td>
                                    @if($entry->createdBy)
                                        <small>{{ $entry->createdBy->name }}</small>
                                    @else
                                        <small class="text-muted">System</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                        <p class="mt-3 mb-0">No transactions found</p>
                                        <small>Transaction history will appear here</small>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
  </div>
</div>
@endsection
