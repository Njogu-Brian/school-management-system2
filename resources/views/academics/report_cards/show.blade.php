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
        <p class="text-muted mb-0">Preview and publish the student report card.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('academics.report_cards.pdf', $report_card) }}" target="_blank" class="btn btn-ghost-strong text-danger"><i class="bi bi-printer"></i> Build & Download PDF</a>
        @if(!$report_card->locked_at)
        <form action="{{ route('academics.report_cards.publish',$report_card) }}" method="POST" class="d-inline">
          @csrf
          <button class="btn btn-settings-primary"><i class="bi bi-upload"></i> Publish</button>
        </form>
        @endif
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body">
        @php($isPdf = false)
        @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => $isPdf])
      </div>
    </div>
  </div>
</div>
@endsection
