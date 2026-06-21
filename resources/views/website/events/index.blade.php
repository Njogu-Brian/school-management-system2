@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Website Events', 'icon' => 'bi bi-calendar-event', 'actions' => '<a href="'.route('website.events.create').'" class="btn btn-settings-primary">New Event</a>'])
<div class="settings-card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-modern mb-0"><thead class="table-light"><tr><th>Title</th><th>Date</th><th>Location</th><th></th></tr></thead>
<tbody>@forelse($events as $event)<tr>
<td class="fw-semibold">{{ $event->title }}</td><td>{{ $event->start_date->format('M d, Y') }}</td><td>{{ $event->location }}</td>
<td class="text-end"><a href="{{ route('website.events.edit', $event) }}" class="btn btn-sm btn-ghost-strong"><i class="bi bi-pencil"></i></a></td>
</tr>@empty<tr><td colspan="4" class="text-center py-4 text-muted">No events.</td></tr>@endforelse</tbody></table></div>
@if($events->hasPages())<div class="p-3">{{ $events->links() }}</div>@endif</div></div></div></div>
@endsection
