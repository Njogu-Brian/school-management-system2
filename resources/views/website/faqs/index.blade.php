@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'FAQs', 'icon' => 'bi bi-question-circle'])
<div class="settings-card mb-4"><div class="card-body">
<form action="{{ route('website.faqs.store') }}" method="POST" class="row g-3">@csrf
<div class="col-md-5"><label class="form-label">Question</label><input type="text" name="question" class="form-control" required></div>
<div class="col-md-3"><label class="form-label">Category</label><input type="text" name="category" class="form-control" placeholder="Admissions"></div>
<div class="col-md-1"><label class="form-label">Order</label><input type="number" name="order" class="form-control" value="0"></div>
<div class="col-md-3"><label class="form-label">Answer</label><textarea name="answer" class="form-control" rows="2" required></textarea></div>
<div class="col-12"><button type="submit" class="btn btn-settings-primary">Add FAQ</button></div>
</form></div></div>
<div class="settings-card"><div class="card-body p-0"><div class="table-responsive">
<table class="table table-modern mb-0"><thead class="table-light"><tr><th>Question</th><th>Category</th><th>Order</th><th></th></tr></thead>
<tbody>@forelse($faqs as $faq)<tr><td>{{ $faq->question }}</td><td>{{ $faq->category }}</td><td>{{ $faq->order }}</td>
<td><form action="{{ route('website.faqs.destroy', $faq) }}" method="POST" onsubmit="return confirm('Delete?');">@csrf @method('DELETE')<button class="btn btn-sm btn-ghost-strong text-danger"><i class="bi bi-trash"></i></button></form></td></tr>
@empty<tr><td colspan="4" class="text-center py-4 text-muted">No FAQs.</td></tr>@endforelse</tbody></table></div></div></div></div></div>
@endsection
