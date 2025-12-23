@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    <div class="finance-card finance-animate mb-3 d-flex justify-content-between align-items-center p-3">
        <h3 class="mb-0">
            <i class="bi bi-percent"></i> Discount Details
        </h3>
        <a href="{{ route('finance.discounts.index') }}" class="btn btn-finance btn-finance-outline">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="finance-card finance-animate mb-4">
                <div class="finance-card-header">
                    <h5 class="mb-0">Discount Information</h5>
                </div>
                <div class="finance-card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Discount Type:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-info">
                                {{ ucfirst(str_replace('_', ' ', $discount->discount_type)) }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Amount Type:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-secondary">
                                {{ $discount->type === 'percentage' ? 'Percentage' : 'Fixed Amount' }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Discount Value:</dt>
                        <dd class="col-sm-8">
                            <strong>
                                @if($discount->type === 'percentage')
                                    {{ number_format($discount->value, 1) }}%
                                @else
                                    Ksh {{ number_format($discount->value, 2) }}
                                @endif
                            </strong>
                        </dd>

                        <dt class="col-sm-4">Scope:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-primary">{{ ucfirst($discount->scope) }}</span>
                        </dd>

                        <dt class="col-sm-4">Frequency:</dt>
                        <dd class="col-sm-8">{{ ucfirst($discount->frequency) }}</dd>

                        <dt class="col-sm-4">Status:</dt>
                        <dd class="col-sm-8">
                            <span class="badge bg-{{ $discount->is_active ? 'success' : 'danger' }}">
                                {{ $discount->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </dd>

                        <dt class="col-sm-4">Start Date:</dt>
                        <dd class="col-sm-8">{{ $discount->start_date ? \Carbon\Carbon::parse($discount->start_date)->format('d M Y') : 'N/A' }}</dd>

                        <dt class="col-sm-4">End Date:</dt>
                        <dd class="col-sm-8">{{ $discount->end_date ? \Carbon\Carbon::parse($discount->end_date)->format('d M Y') : 'No expiry' }}</dd>

                        <dt class="col-sm-4">Reason:</dt>
                        <dd class="col-sm-8">{{ $discount->reason }}</dd>

                        @if($discount->description)
                        <dt class="col-sm-4">Description:</dt>
                        <dd class="col-sm-8">{{ $discount->description }}</dd>
                        @endif

                        <dt class="col-sm-4">Created By:</dt>
                        <dd class="col-sm-8">{{ $discount->creator->name ?? 'System' }}</dd>

                        <dt class="col-sm-4">Created At:</dt>
                        <dd class="col-sm-8">{{ $discount->created_at->format('d M Y, H:i') }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="finance-card finance-animate mb-4">
                <div class="finance-card-header">
                    <h5 class="mb-0">Applied To</h5>
                </div>
                <div class="finance-card-body">
                    @if($discount->student)
                        <p><strong>Student:</strong><br>
                            {{ $discount->student->first_name }} {{ $discount->student->last_name }}<br>
                            <small class="text-muted">{{ $discount->student->admission_number }}</small>
                        </p>
                    @endif

                    @if($discount->family)
                        <p><strong>Family:</strong><br>
                            {{ $discount->family->surname ?? 'N/A' }}
                        </p>
                    @endif

                    @if($discount->votehead)
                        <p><strong>Votehead:</strong><br>
                            {{ $discount->votehead->name }}
                        </p>
                    @endif

                    @if($discount->invoice)
                        <p><strong>Invoice:</strong><br>
                            {{ $discount->invoice->invoice_number }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
  </div>
</div>
@endsection

