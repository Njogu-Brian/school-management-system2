@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'M-PESA Payment Dashboard',
        'icon' => 'bi bi-phone',
        'subtitle' => 'Monitor M-PESA transactions and payment links',
        'actions' => '<a href="' . route('finance.mpesa.prompt-payment.form') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-send"></i> Prompt Payment</a><a href="' . route('finance.mpesa.links.create') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-link-45deg"></i> Create Link</a>'
    ])

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="finance-stat-card finance-animate">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small">Today's Collections</div>
                        <div class="fw-bold fs-4 text-success">KES {{ number_format($stats['today_amount'], 2) }}</div>
                    </div>
                    <div class="text-success">
                        <i class="bi bi-cash-stack fs-3"></i>
                    </div>
                </div>
                <div class="text-muted small">{{ $stats['today_transactions'] }} transactions today</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="finance-stat-card finance-animate">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small">Today's Transactions</div>
                        <div class="fw-bold fs-4 text-primary">{{ $stats['today_transactions'] }}</div>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-arrow-left-right fs-3"></i>
                    </div>
                </div>
                <div class="text-muted small">Payment transactions</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="finance-stat-card finance-animate">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small">Pending</div>
                        <div class="fw-bold fs-4 text-warning">{{ $stats['pending_transactions'] }}</div>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-hourglass-split fs-3"></i>
                    </div>
                </div>
                <div class="text-muted small">Awaiting confirmation</div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="finance-stat-card finance-animate">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <div class="text-muted small">Active Links</div>
                        <div class="fw-bold fs-4 text-info">{{ $stats['active_payment_links'] }}</div>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-link-45deg fs-3"></i>
                    </div>
                </div>
                <div class="text-muted small">Payment links available</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="finance-card finance-animate mb-4">
        <div class="finance-card-header">
            <h5 class="finance-card-title">
                <i class="bi bi-lightning me-2"></i>
                Quick Actions
            </h5>
        </div>
        <div class="finance-card-body">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('finance.mpesa.prompt-payment.form') }}" class="btn btn-finance btn-finance-primary btn-lg">
                    <i class="bi bi-send"></i> Prompt Parent to Pay (STK Push)
                </a>
                <a href="{{ route('finance.mpesa.links.create') }}" class="btn btn-finance btn-finance-secondary btn-lg">
                    <i class="bi bi-link-45deg"></i> Generate Payment Link
                </a>
                <a href="{{ route('finance.mpesa.links.index') }}" class="btn btn-finance btn-finance-outline btn-lg">
                    <i class="bi bi-list"></i> View All Payment Links
                </a>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Transactions -->
        <div class="col-lg-8">
            <div class="finance-card finance-animate">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-clock-history me-2"></i>
                        Recent Transactions
                    </h5>
                </div>
                <div class="finance-card-body p-0">
                    <div class="finance-table-wrapper">
                        <table class="finance-table">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Student</th>
                                    <th class="text-end">Amount</th>
                                    <th>Phone</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTransactions as $transaction)
                                <tr>
                                    <td>
                                        <span class="text-muted small">
                                            {{ $transaction->created_at->format('H:i') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>{{ $transaction->student->first_name }} {{ $transaction->student->last_name }}</strong>
                                        </div>
                                        <small class="text-muted">{{ $transaction->student->admission_number }}</small>
                                    </td>
                                    <td class="text-end">
                                        <strong>KES {{ number_format($transaction->amount, 2) }}</strong>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $transaction->phone_number }}</small>
                                    </td>
                                    <td>
                                        @if($transaction->status === 'completed')
                                            <span class="finance-badge badge-success">Completed</span>
                                        @elseif($transaction->status === 'processing')
                                            <span class="finance-badge badge-info">Processing</span>
                                        @elseif($transaction->status === 'pending')
                                            <span class="finance-badge badge-warning">Pending</span>
                                        @else
                                            <span class="finance-badge badge-danger">{{ ucfirst($transaction->status) }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('finance.mpesa.transaction.show', $transaction) }}" 
                                           class="btn btn-finance btn-finance-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        No recent transactions
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Payment Links -->
        <div class="col-lg-4">
            <div class="finance-card finance-animate">
                <div class="finance-card-header">
                    <h5 class="finance-card-title">
                        <i class="bi bi-link-45deg me-2"></i>
                        Active Payment Links
                    </h5>
                </div>
                <div class="finance-card-body p-0">
                    <div class="finance-table-wrapper">
                        <table class="finance-table">
                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th class="text-end">Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($activeLinks as $link)
                                <tr>
                                    <td>
                                        <div>
                                            <strong>{{ $link->student->first_name }}</strong>
                                        </div>
                                        <small class="text-muted">{{ $link->student->admission_number }}</small>
                                    </td>
                                    <td class="text-end">
                                        <strong>KES {{ number_format($link->amount, 2) }}</strong>
                                    </td>
                                    <td>
                                        <a href="{{ route('finance.mpesa.link.show', $link) }}" 
                                           class="btn btn-finance btn-finance-sm">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                        No active links
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($activeLinks->count() > 0)
                <div class="finance-card-footer text-center">
                    <a href="{{ route('finance.mpesa.links.index') }}" class="btn btn-finance btn-finance-outline">
                        View All Links <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection
