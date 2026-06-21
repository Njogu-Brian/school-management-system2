@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Campaigns', 'icon' => 'bi bi-megaphone'])
<div class="settings-card mb-4"><div class="card-body">
<form action="{{ route('website.campaigns.store') }}" method="POST" class="row g-3">@csrf
<div class="col-md-4"><input type="text" name="campaign_name" class="form-control" placeholder="Campaign name" required></div>
<div class="col-md-3"><select name="type" class="form-select"><option value="newsletter">Newsletter</option><option value="abandoned_admissions">Abandoned Admissions</option></select></div>
<div class="col-md-3"><input type="text" name="subject" class="form-control" placeholder="Email subject"></div>
<div class="col-12"><textarea name="body" class="form-control" rows="3" placeholder="Email body"></textarea></div>
<div class="col-12"><button class="btn btn-settings-primary">Run Campaign</button></div>
</form></div></div>
<div class="settings-card"><div class="card-body p-0"><table class="table table-modern mb-0"><thead class="table-light"><tr><th>Name</th><th>Type</th><th>Sent</th><th>Status</th></tr></thead>
<tbody>@forelse($campaigns as $c)<tr><td>{{ $c->campaign_name }}</td><td>{{ $c->type }}</td><td>{{ $c->sent_count }}</td><td>{{ $c->status }}</td></tr>@empty<tr><td colspan="4" class="text-center py-4 text-muted">No campaigns yet.</td></tr>@endforelse</tbody></table></div></div></div></div>
@endsection
