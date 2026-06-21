@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Community Hub', 'icon' => 'bi bi-people', 'subtitle' => 'Referrals, prayers, alumni stories'])
<div class="row g-4">
<div class="col-lg-6"><div class="settings-card"><div class="card-header">Referrals</div><div class="card-body small">
@forelse($referrals as $r)<div class="border-bottom py-2"><strong>{{ $r->referrer_name }}</strong> → {{ $r->referred_name }} <span class="badge bg-secondary">{{ $r->status }}</span></div>@empty<p class="text-muted mb-0">No referrals yet.</p>@endforelse
</div></div></div>
<div class="col-lg-6"><div class="settings-card"><div class="card-header">Prayer requests</div><div class="card-body small">
@forelse($prayers as $p)<div class="border-bottom py-2 d-flex justify-content-between"><span>{{ $p->is_anonymous ? 'Anonymous' : $p->name }}: {{ Str::limit($p->request, 80) }}</span>@if($p->status==='pending')<form method="POST" action="{{ route('website.community.prayers.approve', $p) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-success">Approve</button></form>@endif</div>@empty<p class="text-muted mb-0">None pending.</p>@endforelse
</div></div></div>
</div>
</div></div>
@endsection
