@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'M-PESA Paybill Transactions',
        'icon' => 'bi bi-list-ul',
        'subtitle' => 'All paybill transactions',
        'actions' => '<a href="' . route('finance.mpesa.c2b.dashboard') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>'
    ])

    <div class="finance-card finance-animate">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-table me-2"></i>All Transactions</h5>
            <div class="text-muted small">
                Total: {{ $transactions->total() }} transaction(s)
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Transaction ID</th>
                            <th>Phone</th>
                            <th>Amount</th>
                            <th>Account Reference</th>
                            <th>Status</th>
                            <th>Student</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $transaction)
                            <tr>
                                <td>
                                    @if($transaction->trans_time)
                                        {{ \Carbon\Carbon::parse($transaction->trans_time)->format('d M Y') }}
                                        <br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($transaction->trans_time)->format('h:i A') }}</small>
                                    @elseif($transaction->created_at)
                                        {{ $transaction->created_at->format('d M Y') }}
                                        <br>
                                        <small class="text-muted">{{ $transaction->created_at->format('h:i A') }}</small>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>
                                    <code class="small">{{ $transaction->trans_id ?? 'N/A' }}</code>
                                </td>
                                <td>
                                    @if($transaction->msisdn)
                                        {{ $transaction->formatted_phone ?? $transaction->msisdn }}
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="fw-semibold text-success">
                                    KES {{ number_format($transaction->trans_amount ?? 0, 2) }}
                                </td>
                                <td>
                                    <code class="small">{{ $transaction->bill_ref_number ?? 'N/A' }}</code>
                                </td>
                                <td>
                                    @if($transaction->allocation_status === 'manually_allocated' || $transaction->allocation_status === 'auto_matched')
                                        <span class="badge bg-success">Allocated</span>
                                    @elseif($transaction->allocation_status === 'unallocated')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($transaction->allocation_status === 'duplicate')
                                        <span class="badge bg-secondary">Duplicate</span>
                                    @elseif($transaction->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($transaction->allocation_status ?? $transaction->status ?? 'Unknown') }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->student_id && $transaction->student)
                                        <a href="{{ route('students.show', $transaction->student) }}">
                                            {{ $transaction->student->first_name }} {{ $transaction->student->last_name }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ $transaction->student->admission_number }}</small>
                                    @elseif($transaction->first_name || $transaction->last_name)
                                        <div>{{ $transaction->full_name }}</div>
                                        <small class="text-muted">Not matched</small>
                                    @else
                                        <span class="text-muted">Not allocated</span>
                                    @endif
                                </td>
                                <td>
                                    @if($transaction->allocation_status === 'unallocated' || !$transaction->student_id)
                                        <a href="{{ route('finance.mpesa.c2b.allocate', $transaction->id) }}" 
                                           class="btn btn-sm btn-finance btn-finance-primary">
                                            <i class="bi bi-person-plus"></i> Allocate
                                        </a>
                                    @else
                                        <a href="{{ route('finance.mpesa.c2b.show', $transaction->id) }}" 
                                           class="btn btn-sm btn-finance btn-finance-outline">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No transactions found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transactions->hasPages())
        <div class="card-footer">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-muted small">
                    Showing {{ $transactions->firstItem() }} to {{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions
                </div>
                <div>
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection
