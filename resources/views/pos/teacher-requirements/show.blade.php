@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <div class="crumb">POS / Teacher Requirements / Detail</div>
                <h1>{{ $requirement->posProduct->name ?? 'Requirement' }}</h1>
                <p>Review required items and fulfillment status.</p>
            </div>
            <a href="{{ route('pos.teacher-requirements.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
        </div>

        <div class="row g-3">
            <div class="col-lg-8">
                <div class="settings-card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3 gap-2">
                            <div class="fw-semibold">{{ $requirement->classroom->name ?? '—' }}</div>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="pill-badge">{{ $requirement->is_active ? 'Active' : 'Inactive' }}</span>
                                <span class="input-chip">{{ $requirement->unit }}</span>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6"><strong>Quantity per student:</strong> {{ $requirement->quantity_per_student }}</div>
                            <div class="col-md-6"><strong>Product:</strong> {{ $requirement->posProduct->name ?? '—' }}</div>
                        </div>

                        @if($requirement->notes)
                            <div class="mb-2">
                                <strong>Notes:</strong>
                                <p class="mb-0">{{ $requirement->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="settings-card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Links</h5>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('pos.products.show', $requirement->posProduct) }}" class="btn btn-ghost-strong w-100 mb-2">
                            <i class="bi bi-box"></i> View Product
                        </a>
                        <a href="{{ route('pos.shop.public', $publicLinkToken ?? ($requirement->public_link_token ?? '')) }}" class="btn btn-settings-primary w-100" target="_blank">
                            <i class="bi bi-bag"></i> Open Shop
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

