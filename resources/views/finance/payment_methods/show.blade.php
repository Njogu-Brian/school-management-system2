@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Payment Method Details',
        'icon' => 'bi bi-credit-card',
        'subtitle' => 'View payment method information',
        'actions' => '<a href="' . route('finance.payment-methods.edit', $paymentMethod) . '" class="btn btn-finance btn-finance-warning"><i class="bi bi-pencil"></i> Edit</a><a href="' . route('finance.payment-methods.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="row">
        <div class="col-md-8">
            <div class="finance-card finance-animate">
                <div class="finance-card-header">
                    <i class="bi bi-info-circle me-2"></i> Payment Method Information
                </div>
                <div class="finance-card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Name:</dt>
                        <dd class="col-sm-8"><strong>{{ $paymentMethod->name }}</strong></dd>

                        <dt class="col-sm-4">Code:</dt>
                        <dd class="col-sm-8"><code>{{ $paymentMethod->code }}</code></dd>

                        <dt class="col-sm-4">Bank Account:</dt>
                        <dd class="col-sm-8">
                            @if($paymentMethod->bankAccount)
                                <span class="badge bg-info">{{ $paymentMethod->bankAccount->name }}</span>
                                <small class="text-muted d-block">{{ $paymentMethod->bankAccount->account_number }}</small>
                            @else
                                <span class="text-muted">Not linked</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Requires Reference:</dt>
                        <dd class="col-sm-8">
                            @if($paymentMethod->requires_reference)
                                <span class="finance-badge badge-approved">Yes</span>
                            @else
                                <span class="finance-badge badge-pending">No</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Online Payment:</dt>
                        <dd class="col-sm-8">
                            @if($paymentMethod->is_online)
                                <span class="badge bg-primary">Yes</span>
                            @else
                                <span class="badge bg-secondary">No</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            @if($paymentMethod->is_active)
                                <span class="finance-badge badge-approved">Active</span>
                            @else
                                <span class="finance-badge badge-rejected">Inactive</span>
                            @endif
                        </dd>

                        <dt class="col-sm-4">Display Order:</dt>
                        <dd class="col-sm-8">{{ $paymentMethod->display_order ?? 0 }}</dd>

                        @if($paymentMethod->description)
                        <dt class="col-sm-4">Description:</dt>
                        <dd class="col-sm-8">{{ $paymentMethod->description }}</dd>
                        @endif

                        <dt class="col-sm-4">Created:</dt>
                        <dd class="col-sm-8">{{ $paymentMethod->created_at->format('d M Y, h:i A') }}</dd>

                        <dt class="col-sm-4">Last Updated:</dt>
                        <dd class="col-sm-8">{{ $paymentMethod->updated_at->format('d M Y, h:i A') }}</dd>
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
                        <h3 class="mb-0">{{ $paymentMethod->payments()->count() }}</h3>
                        <small class="text-muted">Total Payments</small>
                    </div>
                    <div class="text-center">
                        <h4 class="mb-0 text-success">Ksh {{ number_format($paymentMethod->payments()->sum('amount'), 2) }}</h4>
                        <small class="text-muted">Total Amount</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
@endsection

