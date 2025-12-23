@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Bank Account Details',
        'icon' => 'bi bi-bank',
        'subtitle' => 'View bank account information',
        'actions' => '<a href="' . route('finance.bank-accounts.edit', $bankAccount) . '" class="btn btn-finance btn-finance-warning"><i class="bi bi-pencil"></i> Edit</a><a href="' . route('finance.bank-accounts.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="row">
        <div class="col-md-8">
            <div class="finance-card finance-animate">
                <div class="finance-card-header">
                    <i class="bi bi-info-circle me-2"></i> Bank Account Information
                </div>
                <div class="finance-card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Account Name:</dt>
                        <dd class="col-sm-8"><strong>{{ $bankAccount->name }}</strong></dd>

                        <dt class="col-sm-4">Account Number:</dt>
                        <dd class="col-sm-8"><code>{{ $bankAccount->account_number }}</code></dd>

                        <dt class="col-sm-4">Bank Name:</dt>
                        <dd class="col-sm-8">{{ $bankAccount->bank_name }}</dd>

                        <dt class="col-sm-4">Branch:</dt>
                        <dd class="col-sm-8">{{ $bankAccount->branch ?? '—' }}</dd>

                        <dt class="col-sm-4">Account Type:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-info">{{ ucfirst($bankAccount->account_type) }}</span>
                        </dd>

                        <dt class="col-sm-4">Currency:</dt>
                        <dd class="col-sm-8">{{ $bankAccount->currency ?? 'KES' }}</dd>

                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            @if($bankAccount->is_active)
                                <span class="finance-badge badge-approved">Active</span>
                            @else
                                <span class="finance-badge badge-rejected">Inactive</span>
                            @endif
                        </dd>

                        @if($bankAccount->notes)
                        <dt class="col-sm-4">Notes:</dt>
                        <dd class="col-sm-8">{{ $bankAccount->notes }}</dd>
                        @endif

                        <dt class="col-sm-4">Created:</dt>
                        <dd class="col-sm-8">{{ $bankAccount->created_at->format('d M Y, h:i A') }}</dd>

                        <dt class="col-sm-4">Last Updated:</dt>
                        <dd class="col-sm-8">{{ $bankAccount->updated_at->format('d M Y, h:i A') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="finance-card finance-animate">
                <div class="finance-card-header">
                    <i class="bi bi-graph-up me-2"></i> Statistics
                </div>
                <div class="finance-card-body">
                    <div class="text-center mb-3">
                        <h3 class="mb-0">{{ $bankAccount->payments()->count() }}</h3>
                        <small class="text-muted">Total Payments</small>
                    </div>
                    <div class="text-center mb-3">
                        <h4 class="mb-0 text-success">Ksh {{ number_format($bankAccount->payments()->sum('amount'), 2) }}</h4>
                        <small class="text-muted">Total Amount</small>
                    </div>
                    <hr>
                    <div class="text-center">
                        <h5 class="mb-0">{{ \App\Models\PaymentMethod::where('bank_account_id', $bankAccount->id)->count() }}</h5>
                        <small class="text-muted">Linked Payment Methods</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
@endsection

