@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Conversion Engine', 'icon' => 'bi bi-bullseye', 'subtitle' => 'CTAs, exit intent & lead magnets'])
<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="settings-card p-4 text-center"><div class="h2">{{ $stats['cta_clicks'] }}</div><small>CTA Clicks (30d)</small></div></div>
    <div class="col-md-4"><div class="settings-card p-4 text-center"><div class="h2">{{ $stats['enquiries'] }}</div><small>Enquiries (30d)</small></div></div>
    <div class="col-md-4"><div class="settings-card p-4 text-center"><div class="h2">{{ $stats['admission_starts'] }}</div><small>Admission Starts (30d)</small></div></div>
</div>
<div class="settings-card mb-4"><div class="card-header">Smart CTAs</div><div class="card-body">
    <form action="{{ route('website.conversion.ctas.store') }}" method="POST" class="row g-2 mb-4">@csrf
        <div class="col-md-2"><input name="name" class="form-control" placeholder="Internal name" required></div>
        <div class="col-md-2"><select name="cta_type" class="form-select" required>
            @foreach(['apply_now','book_visit','call_now','whatsapp','custom'] as $t)<option value="{{ $t }}">{{ str_replace('_',' ',ucfirst($t)) }}</option>@endforeach
        </select></div>
        <div class="col-md-2"><input name="label" class="form-control" placeholder="Button label" required></div>
        <div class="col-md-2"><input name="url" class="form-control" placeholder="URL"></div>
        <div class="col-md-2"><select name="placement" class="form-select"><option value="global">Global</option><option value="page">Specific pages</option></select></div>
        <div class="col-md-2"><button class="btn btn-settings-primary w-100">Add CTA</button></div>
    </form>
    @foreach($ctas as $cta)<div class="d-flex justify-content-between border-bottom py-2"><span>{{ $cta->label }} <small class="text-muted">({{ $cta->cta_type }})</small></span><strong>{{ $cta->click_count }} clicks</strong></div>@endforeach
</div></div>
<div class="settings-card mb-4"><div class="card-header">Exit Intent</div><div class="card-body">
    <form action="{{ route('website.conversion.exit-intent.store') }}" method="POST" class="row g-2 mb-3">@csrf
        <div class="col-md-3"><input name="title" class="form-control" placeholder="Title" required></div>
        <div class="col-md-4"><input name="message" class="form-control" placeholder="Message"></div>
        <div class="col-md-2"><input name="button_label" class="form-control" value="Book a School Tour" required></div>
        <div class="col-md-2"><input name="button_url" class="form-control" placeholder="/contact"></div>
        <div class="col-md-1"><button class="btn btn-settings-primary w-100">Add</button></div>
    </form>
    @foreach($campaigns as $c)<div class="d-flex justify-content-between py-2 border-bottom"><span>{{ $c->title }}</span><small>{{ $c->impressions }} views · {{ $c->conversions }} conversions</small></div>@endforeach
</div></div>
<div class="settings-card"><div class="card-header">Lead Magnets</div><div class="card-body">
    <form action="{{ route('website.conversion.magnets.store') }}" method="POST" class="row g-2 mb-3">@csrf
        <div class="col-md-3"><input name="title" class="form-control" placeholder="Title" required></div>
        <div class="col-md-2"><input name="slug" class="form-control" placeholder="slug" required></div>
        <div class="col-md-4"><input name="description" class="form-control" placeholder="Description"></div>
        <div class="col-md-2"><input name="file_path" class="form-control" placeholder="file path"></div>
        <div class="col-md-1"><button class="btn btn-settings-primary w-100">Add</button></div>
    </form>
    @foreach($magnets as $m)<div class="d-flex justify-content-between py-2 border-bottom"><span>{{ $m->title }}</span><small>{{ $m->download_count }} downloads</small></div>@endforeach
</div></div>
</div></div>
@endsection
