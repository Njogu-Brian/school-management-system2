@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Newsletter Subscribers', 'icon' => 'bi bi-mailbox'])
<div class="settings-card"><div class="card-body p-0"><table class="table table-modern mb-0"><thead class="table-light"><tr><th>Email</th><th>Status</th><th>Source</th></tr></thead>
<tbody>@forelse($subscribers as $s)<tr><td>{{ $s->email }}</td><td>{{ $s->status }}</td><td>{{ $s->source }}</td></tr>@empty<tr><td colspan="3" class="text-center py-4 text-muted">No subscribers.</td></tr>@endforelse</tbody></table></div>
@if($subscribers->hasPages())<div class="p-3">{{ $subscribers->links() }}</div>@endif</div></div></div>
@endsection
