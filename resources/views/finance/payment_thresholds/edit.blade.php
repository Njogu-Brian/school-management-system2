@extends('layouts.app')

@section('content')
<div class="finance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Edit payment threshold',
            'icon' => 'bi bi-pencil',
            'subtitle' => 'Term + category: ' . ($paymentThreshold->term?->name ?? '') . ' — ' . ($paymentThreshold->studentCategory?->name ?? ''),
        ])

        <div class="finance-card finance-animate">
            <div class="finance-card-header">
                <i class="bi bi-file-earmark-text me-2"></i> Threshold details
            </div>
            <div class="finance-card-body">
                <form method="POST" action="{{ route('finance.payment-thresholds.update', $paymentThreshold) }}">
                    @csrf
                    @method('PUT')
                    @include('finance.payment_thresholds._form', [
                        'threshold' => $paymentThreshold,
                        'terms' => $terms,
                        'categories' => $categories,
                    ])

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('finance.payment-thresholds.index', ['term_id' => $paymentThreshold->term_id]) }}" class="btn btn-finance btn-finance-outline">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-check-circle"></i> Update threshold
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
