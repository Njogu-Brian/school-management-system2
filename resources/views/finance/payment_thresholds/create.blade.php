@extends('layouts.app')

@section('content')
<div class="finance-page">
    <div class="finance-shell">
        @include('finance.partials.header', [
            'title' => 'Add payment threshold',
            'icon' => 'bi bi-plus-circle',
            'subtitle' => 'One row per term + student category',
        ])

        <div class="finance-card finance-animate">
            <div class="finance-card-header">
                <i class="bi bi-file-earmark-text me-2"></i> Threshold details
            </div>
            <div class="finance-card-body">
                <form method="POST" action="{{ route('finance.payment-thresholds.store') }}">
                    @csrf
                    @include('finance.payment_thresholds._form', [
                        'threshold' => null,
                        'terms' => $terms,
                        'categories' => $categories,
                        'defaultTermId' => $selectedTermId,
                    ])

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="{{ route('finance.payment-thresholds.index', array_filter(['term_id' => $selectedTermId])) }}" class="btn btn-finance btn-finance-outline">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-finance btn-finance-primary">
                            <i class="bi bi-check-circle"></i> Save threshold
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
