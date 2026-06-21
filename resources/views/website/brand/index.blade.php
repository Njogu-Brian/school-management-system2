@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Brand Intelligence', 'icon' => 'bi bi-lightning', 'subtitle' => 'Website performance & recommendations'])
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="settings-card p-4 text-center"><div class="h2">{{ $dashboard['chat_sessions'] }}</div><small>Chat Sessions</small></div></div>
    <div class="col-md-3"><div class="settings-card p-4 text-center"><div class="h2">{{ $dashboard['event_registrations'] }}</div><small>Event RSVPs</small></div></div>
    <div class="col-md-3"><div class="settings-card p-4 text-center"><div class="h2">{{ $dashboard['referrals'] }}</div><small>Referrals</small></div></div>
    <div class="col-md-3"><div class="settings-card p-4 text-center"><div class="h2">{{ $dashboard['best_ctas']->sum('click_count') }}</div><small>CTA Clicks</small></div></div>
</div>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="settings-card mb-4"><div class="card-header">Top Pages</div><div class="card-body">
            @forelse($dashboard['top_pages'] as $row)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $row->page }}</span><strong>{{ $row->views }}</strong></div>@empty<p class="text-muted mb-0">No data yet.</p>@endforelse
        </div></div>
        <div class="settings-card"><div class="card-header">Best CTAs</div><div class="card-body">
            @forelse($dashboard['best_ctas'] as $cta)<div class="d-flex justify-content-between py-2"><span>{{ $cta->label }}</span><strong>{{ $cta->click_count }}</strong></div>@empty<p class="text-muted mb-0">No CTAs tracked.</p>@endforelse
        </div></div>
    </div>
    <div class="col-lg-6">
        <div class="settings-card mb-4"><div class="card-header">Best Blog Posts</div><div class="card-body">
            @forelse($dashboard['best_blogs'] as $blog)<div class="d-flex justify-content-between py-2"><span>{{ $blog->title }}</span><strong>{{ $blog->views_count }}</strong></div>@empty<p class="text-muted mb-0">No blog views yet.</p>@endforelse
        </div></div>
        <div class="settings-card"><div class="card-header">Recommendations</div><div class="card-body">
            @forelse($recommendations as $rec)<div class="alert alert-light border mb-2 py-2">{{ $rec['message'] }}</div>@empty<p class="text-muted mb-0">All looking good.</p>@endforelse
        </div></div>
    </div>
</div>
</div></div>
@endsection
