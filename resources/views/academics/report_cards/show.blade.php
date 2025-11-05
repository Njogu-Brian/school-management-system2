@extends('layouts.app')
@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="mb-0">Report Card</h1>
    <div>
      <a href="{{ route('academics.report_cards.pdf', $report_card) }}" target="_blank" class="btn btn-outline-primary">
        <i class="bi bi-printer"></i> Build & Download PDF
      </a>
      @if(!$report_card->locked_at)
        <form action="{{ route('academics.report_cards.publish',$report_card) }}" method="POST" class="d-inline">
          @csrf
          <button class="btn btn-success"><i class="bi bi-upload"></i> Publish</button>
        </form>
      @endif
    </div>
  </div>

  {{-- Make the HTML page look like the PDF --}}
  <div class="card">
    <div class="card-body">
      @php($isPdf = false)
      @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => $isPdf])
    </div>
  </div>
</div>
@endsection
