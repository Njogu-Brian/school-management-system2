@extends('adminlte::page')

@section('title', 'M-PESA Dashboard')

@section('content_header')
    <h1><i class="fas fa-mobile-alt text-success"></i> M-PESA Payment Dashboard</h1>
@stop

@section('content')
<div class="row">
    <!-- Statistics Cards -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ number_format($stats['today_amount'], 2) }}</h3>
                <p>Today's Collections (KES)</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $stats['today_transactions'] }}</h3>
                <p>Today's Transactions</p>
            </div>
            <div class="icon">
                <i class="fas fa-exchange-alt"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $stats['pending_transactions'] }}</h3>
                <p>Pending Transactions</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $stats['active_payment_links'] }}</h3>
                <p>Active Payment Links</p>
            </div>
            <div class="icon">
                <i class="fas fa-link"></i>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            <div class="card-body">
                <a href="{{ route('finance.mpesa.prompt-payment.form') }}" class="btn btn-success btn-lg mr-2">
                    <i class="fas fa-mobile-alt"></i> Prompt Parent to Pay (STK Push)
                </a>
                <a href="{{ route('finance.mpesa.links.create') }}" class="btn btn-primary btn-lg mr-2">
                    <i class="fas fa-link"></i> Generate Payment Link
                </a>
                <a href="{{ route('finance.mpesa.links.index') }}" class="btn btn-info btn-lg mr-2">
                    <i class="fas fa-list"></i> View All Payment Links
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Transactions -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-history"></i> Recent Transactions</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->created_at->format('H:i') }}</td>
                                <td>
                                    <small>
                                        <strong>{{ $transaction->student->first_name }} {{ $transaction->student->last_name }}</strong><br>
                                        {{ $transaction->student->admission_number }}
                                    </small>
                                </td>
                                <td><strong>KES {{ number_format($transaction->amount, 2) }}</strong></td>
                                <td><small>{{ $transaction->phone_number }}</small></td>
                                <td>
                                    @if($transaction->status === 'completed')
                                        <span class="badge badge-success">Completed</span>
                                    @elseif($transaction->status === 'processing')
                                        <span class="badge badge-info">Processing</span>
                                    @elseif($transaction->status === 'pending')
                                        <span class="badge badge-warning">Pending</span>
                                    @else
                                        <span class="badge badge-danger">{{ ucfirst($transaction->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('finance.mpesa.transaction.show', $transaction) }}" class="btn btn-xs btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted">No recent transactions</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Payment Links -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-link"></i> Active Payment Links</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($activeLinks as $link)
                            <tr>
                                <td>
                                    <small>
                                        <strong>{{ $link->student->first_name }}</strong><br>
                                        {{ $link->student->admission_number }}
                                    </small>
                                </td>
                                <td><strong>{{ number_format($link->amount, 2) }}</strong></td>
                                <td>
                                    <a href="{{ route('finance.mpesa.link.show', $link) }}" class="btn btn-xs btn-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">No active links</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($activeLinks->count() > 0)
            <div class="card-footer text-center">
                <a href="{{ route('finance.mpesa.links.index') }}" class="btn btn-sm btn-primary">
                    View All Links <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            @endif
        </div>
    </div>
</div>

@stop

@section('css')
<style>
    .small-box {
        border-radius: 5px;
    }
    .small-box h3 {
        font-size: 2.2rem;
        font-weight: 700;
    }
</style>
@stop

