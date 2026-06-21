@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Student Showcase', 'icon' => 'bi bi-trophy', 'subtitle' => 'Spotlights & competitions'])
<div class="row g-4">
<div class="col-lg-6"><div class="settings-card"><div class="card-header">Add spotlight</div><div class="card-body">
<form method="POST" action="{{ route('website.showcase.spotlights.store') }}">@csrf
<input name="title" class="form-control mb-2" placeholder="Title" required>
<textarea name="story" class="form-control mb-2" rows="3" placeholder="Story"></textarea>
<input name="achievement" class="form-control mb-2" placeholder="Achievement">
<label class="form-check"><input type="checkbox" name="published" value="1" class="form-check-input"> Published</label>
<button class="btn btn-settings-primary mt-2">Save</button>
</form></div></div></div>
<div class="col-lg-6"><div class="settings-card"><div class="card-header">Add competition</div><div class="card-body">
<form method="POST" action="{{ route('website.showcase.competitions.store') }}">@csrf
<input name="title" class="form-control mb-2" required placeholder="Competition title">
<textarea name="description" class="form-control mb-2" rows="2"></textarea>
<input name="date" type="date" class="form-control mb-2">
<input name="location" class="form-control mb-2" placeholder="Location">
<button class="btn btn-settings-primary mt-2">Save</button>
</form></div></div></div>
</div>
</div></div>
@endsection
