@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Admission Applications', 'icon' => 'bi bi-file-earmark-person', 'subtitle' => 'Website admissions pipeline'])
<div class="settings-card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-modern mb-0"><thead class="table-light"><tr><th>App #</th><th>Parent</th><th>Child</th><th>Class</th><th>Status</th><th></th></tr></thead>
<tbody>@forelse($applications as $app)<tr>
<td><code>{{ $app->application_no }}</code></td>
<td>{{ $app->parent_name }}<br><small>{{ $app->phone }}</small></td>
<td>{{ $app->child_name }}</td><td>{{ $app->desired_class }}</td>
<td><span class="pill-badge">{{ str_replace('_',' ', ucfirst($app->status)) }}</span></td>
<td><a href="{{ route('website.admissions.show', $app) }}" class="btn btn-sm btn-ghost-strong">Review</a></td>
</tr>@empty<tr><td colspan="6" class="text-center py-4 text-muted">No applications yet.</td></tr>@endforelse</tbody></table></div>
@if($applications->hasPages())<div class="p-3">{{ $applications->links() }}</div>@endif</div></div></div></div>
@endsection
