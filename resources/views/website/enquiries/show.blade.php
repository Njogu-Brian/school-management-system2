@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Enquiry Details', 'icon' => 'bi bi-envelope-open', 'actions' => '<a href="'.route('website.enquiries.index').'" class="btn btn-outline-secondary">Back</a>'])
<div class="settings-card mb-4"><div class="card-body">
<p><strong>Parent:</strong> {{ $enquiry->parent_name }}</p>
<p><strong>Phone:</strong> {{ $enquiry->phone }} · <strong>Email:</strong> {{ $enquiry->email }}</p>
<p><strong>Child Age:</strong> {{ $enquiry->child_age ?? '—' }} · <strong>Grade Interest:</strong> {{ $enquiry->grade_interest ?? '—' }}</p>
<p><strong>Source:</strong> {{ $enquiry->source }} · <strong>Received:</strong> {{ $enquiry->created_at->format('M d, Y H:i') }}</p>
<p><strong>Message:</strong><br>{{ $enquiry->message ?: '—' }}</p>
</div></div>
<div class="settings-card"><div class="card-body">
<form action="{{ route('website.enquiries.status', $enquiry) }}" method="POST" class="row g-3">@csrf @method('PATCH')
<div class="col-md-4"><label class="form-label">Status</label>
<select name="status" class="form-select">@foreach(\App\Models\Website\Enquiry::statuses() as $status)<option value="{{ $status }}" @selected($enquiry->status === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
<div class="col-12"><button type="submit" class="btn btn-settings-primary">Update Status</button></div>
</form></div></div></div></div>
@endsection
