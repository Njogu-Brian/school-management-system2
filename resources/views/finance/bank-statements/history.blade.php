@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Transaction History',
        'icon' => 'bi bi-clock-history',
        'subtitle' => 'Audit trail for Transaction #' . $bankStatement->id,
        'actions' => '<a href="' . route('finance.bank-statements.show', $bankStatement) . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back to Transaction</a>'
    ])

    <div class="row">
        <div class="col-md-12">
            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Transaction Information</h5>
                </div>
                <div class="finance-card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Reference Number:</dt>
                                <dd class="col-sm-7"><strong>{{ $bankStatement->reference_number ?? 'N/A' }}</strong></dd>

                                <dt class="col-sm-5">Amount:</dt>
                                <dd class="col-sm-7">Ksh {{ number_format($bankStatement->amount, 2) }}</dd>

                                <dt class="col-sm-5">Status:</dt>
                                <dd class="col-sm-7">
                                    @if($bankStatement->status === 'confirmed')
                                        <span class="badge bg-success">Confirmed</span>
                                    @elseif($bankStatement->status === 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @else
                                        <span class="badge bg-warning">Draft</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl class="row mb-0">
                                <dt class="col-sm-5">Transaction Date:</dt>
                                <dd class="col-sm-7">{{ $bankStatement->transaction_date->format('d M Y') ?? 'N/A' }}</dd>

                                <dt class="col-sm-5">Created:</dt>
                                <dd class="col-sm-7">{{ $bankStatement->created_at->format('d M Y H:i') }}</dd>

                                <dt class="col-sm-5">Last Updated:</dt>
                                <dd class="col-sm-7">{{ $bankStatement->updated_at->format('d M Y H:i') }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="finance-card finance-animate mb-4 shadow-sm rounded-4 border-0">
                <div class="finance-card-header">
                    <h5 class="mb-0">Audit Trail</h5>
                </div>
                <div class="finance-card-body">
                    @if($auditLogs->isEmpty())
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> No audit logs found for this transaction.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Event</th>
                                        <th>User</th>
                                        <th>Changes</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($auditLogs as $log)
                                        <tr>
                                            <td>{{ $log->created_at->format('d M Y H:i:s') }}</td>
                                            <td>
                                                <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $log->event)) }}</span>
                                            </td>
                                            <td>
                                                {{ $log->user->name ?? 'System' }}
                                                @if($log->user)
                                                    <br><small class="text-muted">{{ $log->user->email }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                @if($log->old_values || $log->new_values)
                                                    <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#log-{{ $log->id }}">
                                                        View Details
                                                    </button>
                                                    <div class="collapse mt-2" id="log-{{ $log->id }}">
                                                        <div class="card card-body">
                                                            @if($log->old_values)
                                                                <strong>Before:</strong>
                                                                <pre class="mb-2">{{ json_encode($log->old_values, JSON_PRETTY_PRINT) }}</pre>
                                                            @endif
                                                            @if($log->new_values)
                                                                <strong>After:</strong>
                                                                <pre>{{ json_encode($log->new_values, JSON_PRETTY_PRINT) }}</pre>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">No changes recorded</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">{{ $log->ip_address ?? 'N/A' }}</small>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
@endsection
