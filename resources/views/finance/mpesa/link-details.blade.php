@extends('layouts.app')

@section('content')
    @include('finance.partials.header', [
        'title' => 'Payment Link Details',
        'icon' => 'bi bi-link-45deg',
        'subtitle' => 'View and share this payment link',
        'actions' => '<a href="' . route('finance.mpesa.links.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-list"></i> All Links</a><a href="' . route('finance.mpesa.dashboard') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Dashboard</a>'
    ])

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show border-0 mb-4" role="alert">
        <div class="d-flex align-items-start">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div class="flex-grow-1">{{ session('success') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show border-0 mb-4" role="alert">
        <div class="d-flex align-items-start">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div class="flex-grow-1">{{ session('error') }}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="finance-card finance-animate">
                <div class="finance-card-header d-flex justify-content-between align-items-center">
                    <h5 class="finance-card-title mb-0">
                        <i class="bi bi-link-45deg me-2"></i>
                        {{ $paymentLink->payment_reference }}
                    </h5>
                    @php
                        $statusClass = match($paymentLink->status) {
                            'active' => 'success',
                            'used' => 'info',
                            'expired' => 'warning',
                            'cancelled' => 'secondary',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="badge bg-{{ $statusClass }}">{{ ucfirst($paymentLink->status) }}</span>
                </div>
                <div class="finance-card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4 text-muted">Student</dt>
                        <dd class="col-sm-8">{{ $paymentLink->student?->full_name ?? '—' }} <small class="text-muted">({{ $paymentLink->student?->admission_number ?? '—' }})</small></dd>

                        <dt class="col-sm-4 text-muted">Amount</dt>
                        <dd class="col-sm-8">{{ $paymentLink->currency }} {{ number_format($paymentLink->amount, 2) }}</dd>

                        <dt class="col-sm-4 text-muted">Description</dt>
                        <dd class="col-sm-8">{{ $paymentLink->description ?? '—' }}</dd>

                        <dt class="col-sm-4 text-muted">Expires</dt>
                        <dd class="col-sm-8">{{ $paymentLink->expires_at ? $paymentLink->expires_at->format('d M Y H:i') : 'Never' }}</dd>

                        <dt class="col-sm-4 text-muted">Uses</dt>
                        <dd class="col-sm-8">{{ $paymentLink->use_count ?? 0 }} / {{ $paymentLink->max_uses >= 999 ? 'Unlimited' : $paymentLink->max_uses }}</dd>

                        <dt class="col-sm-4 text-muted">Created</dt>
                        <dd class="col-sm-8">{{ $paymentLink->created_at->format('d M Y H:i') }} @if($paymentLink->creator) by {{ $paymentLink->creator->name }} @endif</dd>
                    </dl>

                    @if($paymentLink->status === 'active')
                    <div class="mt-4 pt-3 border-top">
                        <label class="form-label small text-muted">Payment URL (share with parent)</label>
                        <div class="input-group">
                            <input type="text" class="form-control font-monospace" id="paymentUrl" readonly
                                   value="{{ $paymentLink->getPaymentUrl() }}">
                            <button type="button" class="btn btn-finance btn-finance-primary" onclick="copyLink()">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                        <a href="{{ $paymentLink->getPaymentUrl() }}" target="_blank" class="btn btn-finance btn-finance-outline btn-sm mt-2">
                            <i class="bi bi-box-arrow-up-right"></i> Open payment page
                        </a>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
    <script>
        function copyLink() {
            var el = document.getElementById('paymentUrl');
            el.select();
            el.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(el.value).then(function() {
                var btn = document.querySelector('#paymentUrl').nextElementSibling;
                if (btn) { btn.innerHTML = '<i class="bi bi-check"></i> Copied'; setTimeout(function(){ btn.innerHTML = '<i class="bi bi-clipboard"></i> Copy'; }, 2000); }
            });
        }
    </script>
    @endpush
@endsection
