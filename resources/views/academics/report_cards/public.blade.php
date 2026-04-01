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
        <h1 class="mb-1">Report Card</h1>
        <p class="text-muted mb-0">Summary view with remarks and skills.</p>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body">
        @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => $isPdf ?? false])
      </div>
    </div>
  </div>
</div>
@endsection
