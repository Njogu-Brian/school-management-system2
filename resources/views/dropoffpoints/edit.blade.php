@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
    <div class="settings-shell">
        <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                <p class="eyebrow text-muted mb-1">Transport / Drop-off points</p>
                <h1 class="mb-1">Edit Drop-off Point</h1>
                <p class="text-muted mb-0">{{ $dropOffPoint->name }}</p>
            </div>
            <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-ghost-strong">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>

        @if(($usageCount ?? 0) > 0)
            <div class="alert alert-info">
                Used by about {{ $usageCount }} student(s) on morning/evening legs. After changing rates, open
                Finance → Transport Fees, run <strong>Recalculate from routes</strong>, then <strong>Post Pending Fees</strong>.
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <div class="settings-card">
            <div class="card-body">
                <form action="{{ route('transport.dropoffpoints.update', $dropOffPoint->id) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold">Name</label>
                        <input type="text" name="name" id="name" class="form-control"
                               value="{{ old('name', $dropOffPoint->name) }}" required
                               @if($dropOffPoint->isOwnMeans()) readonly @endif>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="two_way_amount" class="form-label fw-semibold">Two-way fare (KES / term)</label>
                            <input type="number" step="0.01" min="0" name="two_way_amount" id="two_way_amount"
                                   class="form-control"
                                   value="{{ old('two_way_amount', $dropOffPoint->two_way_amount) }}"
                                   @if($dropOffPoint->isOwnMeans()) readonly @endif>
                        </div>
                        <div class="col-md-6">
                            <label for="one_way_amount" class="form-label fw-semibold">One-way fare (KES / term)</label>
                            <input type="number" step="0.01" min="0" name="one_way_amount" id="one_way_amount"
                                   class="form-control"
                                   value="{{ old('one_way_amount', $dropOffPoint->one_way_amount) }}"
                                   @if($dropOffPoint->isOwnMeans()) readonly @endif>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-settings-primary">Save rates</button>
                        <a href="{{ route('transport.dropoffpoints.index') }}" class="btn btn-ghost-strong">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
