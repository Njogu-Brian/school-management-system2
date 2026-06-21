@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Website Analytics', 'icon' => 'bi bi-graph-up', 'subtitle' => 'Last 30 days'])
<div class="row g-3 mb-4">
<div class="col-md-3"><div class="settings-card p-4 text-center"><div class="h2">{{ $summary['total_page_views'] }}</div><small>Page Views</small></div></div>
<div class="col-md-3"><div class="settings-card p-4 text-center"><div class="h2">{{ $summary['abandoned_admissions'] }}</div><small>Abandoned Applications</small></div></div>
</div>
<div class="settings-card mb-4"><div class="card-header">Top Pages</div><div class="card-body">
@foreach($summary['top_pages'] as $row)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $row->page }}</span><strong>{{ $row->views }}</strong></div>@endforeach
</div></div>
<div class="settings-card"><div class="card-header">Conversions</div><div class="card-body">
@forelse($summary['conversion_totals'] as $type => $total)<div class="d-flex justify-content-between py-2"><span>{{ str_replace('_',' ', $type) }}</span><strong>{{ $total }}</strong></div>@empty<p class="text-muted mb-0">No conversion events yet.</p>@endforelse
</div></div></div></div>
@endsection
