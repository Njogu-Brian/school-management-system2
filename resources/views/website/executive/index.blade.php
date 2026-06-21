@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Executive Intelligence', 'icon' => 'bi bi-speedometer2', 'subtitle' => 'Institutional KPIs & predictive alerts'])
<form method="POST" action="{{ route('website.executive.refresh-alerts') }}" class="mb-3">@csrf<button class="btn btn-sm btn-outline-primary">Refresh predictive alerts</button></form>
<div class="row g-3 mb-4">
<div class="col-md-3"><div class="settings-card p-4 text-center"><div class="h4">{{ $kpis['admissions']['total_leads'] ?? 0 }}</div><small>Admission leads</small></div></div>
<div class="col-md-3"><div class="settings-card p-4 text-center"><div class="h4">{{ $kpis['admissions']['conversion_rate'] ?? 0 }}%</div><small>Conversion rate</small></div></div>
<div class="col-md-3"><div class="settings-card p-4 text-center"><div class="h4">KES {{ number_format($kpis['finance']['outstanding_balances'] ?? 0) }}</div><small>Outstanding</small></div></div>
<div class="col-md-3"><div class="settings-card p-4 text-center"><div class="h4">{{ $kpis['attendance']['absentee_rate'] ?? 0 }}%</div><small>Absentee rate (14d)</small></div></div>
</div>
<div class="settings-card"><div class="card-header">Active alerts</div><div class="card-body">
@forelse($alerts as $a)<div class="border-bottom py-2"><span class="badge bg-warning text-dark">{{ $a->severity }}</span> <strong>{{ $a->title }}</strong><br><small>{{ $a->message }}</small></div>@empty<p class="text-muted mb-0">No unacknowledged alerts.</p>@endforelse
</div></div>
</div></div>
@endsection
