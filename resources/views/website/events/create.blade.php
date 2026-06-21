@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => isset($event) ? 'Edit Event' : 'New Event', 'icon' => 'bi bi-calendar-plus'])
<div class="settings-card"><div class="card-body">
<form action="{{ isset($event) ? route('website.events.update', $event) : route('website.events.store') }}" method="POST" enctype="multipart/form-data">
@csrf @if(isset($event)) @method('PUT') @endif
@include('website.events._form', ['event' => $event ?? null])
<button type="submit" class="btn btn-settings-primary mt-3">Save</button></form></div></div></div></div>
@endsection
