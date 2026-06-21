@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Admissions Leads', 'icon' => 'bi bi-envelope-paper'])
<div class="settings-card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-modern mb-0"><thead class="table-light"><tr><th>Parent</th><th>Contact</th><th>Child</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($enquiries as $enquiry)<tr>
<td>{{ $enquiry->parent_name }}</td><td>{{ $enquiry->phone }}<br><small>{{ $enquiry->email }}</small></td>
<td>Age {{ $enquiry->child_age ?? '—' }} · {{ $enquiry->grade_interest ?? '—' }}</td><td><span class="pill-badge">{{ ucfirst($enquiry->status) }}</span></td>
<td><a href="{{ route('website.enquiries.show', $enquiry) }}" class="btn btn-sm btn-ghost-strong">View</a></td>
</tr>@empty<tr><td colspan="5" class="text-center py-4 text-muted">No enquiries yet.</td></tr>@endforelse</tbody></table></div>
@if($enquiries->hasPages())<div class="p-3">{{ $enquiries->links() }}</div>@endif</div></div></div></div>
@endsection
