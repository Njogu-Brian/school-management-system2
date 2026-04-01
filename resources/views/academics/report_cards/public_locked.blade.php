@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Report Card</div>
        <h1 class="mb-1">Report Card Locked</h1>
        <p class="text-muted mb-0">Please clear the fee balance to view this term’s results.</p>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <div class="alert alert-warning mb-3">
          <div class="fw-semibold mb-1">Fee balance pending</div>
          <div>Outstanding balance: <strong>{{ number_format((float) $balance, 2) }}</strong></div>
        </div>

        <div class="text-muted">
          If you have already paid, please wait for the payment to be posted, then try again.
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

