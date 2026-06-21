@extends('layouts.app')

@push('styles')@include('settings.partials.styles')@endpush

@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Testimonials', 'icon' => 'bi bi-chat-quote', 'subtitle' => 'Parent and community stories'])

<div class="settings-card mb-4"><div class="card-body">
<form action="{{ route('website.testimonials.store') }}" method="POST" enctype="multipart/form-data" class="row g-3">
@csrf
<div class="col-md-4"><label class="form-label">Name</label><input type="text" name="name" class="form-control" required></div>
<div class="col-md-4"><label class="form-label">Relationship</label><input type="text" name="relationship" class="form-control" placeholder="Parent of Grade 3"></div>
<div class="col-md-4"><label class="form-label">Photo</label><input type="file" name="photo" class="form-control"></div>
<div class="col-12"><label class="form-label">Message</label><textarea name="message" class="form-control" rows="3" required></textarea></div>
<div class="col-md-6"><label class="form-label">Video URL</label><input type="url" name="video_url" class="form-control"></div>
<div class="col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="featured" value="1"><label class="form-check-label">Featured</label></div></div>
<div class="col-md-3"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="approved" value="1" checked><label class="form-check-label">Approved</label></div></div>
<div class="col-12"><button type="submit" class="btn btn-settings-primary">Add Testimonial</button></div>
</form></div></div>

<div class="settings-card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-modern mb-0"><thead class="table-light"><tr><th>Name</th><th>Message</th><th>Featured</th><th>Approved</th><th></th></tr></thead>
<tbody>@forelse($testimonials as $t)<tr>
<td>{{ $t->name }}<br><small class="text-muted">{{ $t->relationship }}</small></td>
<td>{{ Str::limit($t->message, 80) }}</td>
<td>{{ $t->featured ? 'Yes' : 'No' }}</td><td>{{ $t->approved ? 'Yes' : 'No' }}</td>
<td><form action="{{ route('website.testimonials.destroy', $t) }}" method="POST" onsubmit="return confirm('Delete?');">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button></form></td>
</tr>@empty<tr><td colspan="5" class="text-center py-4 text-muted">No testimonials.</td></tr>@endforelse</tbody></table></div>
@if($testimonials->hasPages())<div class="p-3">{{ $testimonials->links() }}</div>@endif</div></div>
</div></div>
@endsection
