@extends('layouts.app')
@push('styles')@include('settings.partials.styles')@endpush
@section('content')
<div class="settings-page"><div class="settings-shell">
@include('website.partials.header', ['title' => 'Review Application', 'icon' => 'bi bi-clipboard-check', 'subtitle' => $application->application_no, 'actions' => '<a href="'.route('website.admissions.index').'" class="btn btn-outline-secondary">Back</a>'])

<div class="row g-4">
<div class="col-lg-7">
<div class="settings-card mb-4"><div class="card-body">
<h5>Applicant Details</h5>
<p><strong>Parent:</strong> {{ $application->parent_name }} · {{ $application->phone }} · {{ $application->email }}</p>
<p><strong>Child:</strong> {{ $application->child_name }} · Age {{ $application->age ?? '—' }} · {{ $application->gender }}</p>
<p><strong>DOB:</strong> {{ $application->dob?->format('M d, Y') ?? '—' }}</p>
<p><strong>Desired class:</strong> {{ $application->desired_class }}</p>
<p><strong>Previous school:</strong> {{ $application->previous_school ?? '—' }}</p>
<p><strong>Medical:</strong> {{ $application->medical_notes ?? '—' }}</p>
<p><strong>Special needs:</strong> {{ $application->special_needs ?? '—' }}</p>
</div></div>

<div class="settings-card"><div class="card-header">Documents</div><div class="card-body">
@forelse($application->documents as $doc)
<div class="d-flex justify-content-between align-items-center border-bottom py-2">
<div><strong>{{ str_replace('_',' ', ucfirst($doc->document_type)) }}</strong>
@if($doc->verified)<span class="badge bg-success ms-2">Verified</span>@endif</div>
<div class="d-flex gap-2">
<a href="{{ $doc->url() }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
<form action="{{ route('website.admissions.documents.verify', [$application, $doc->id]) }}" method="POST">@csrf @method('PATCH')
<input type="hidden" name="verified" value="1"><button class="btn btn-sm btn-settings-primary">Verify</button></form>
</div></div>
@empty<p class="text-muted mb-0">No documents uploaded.</p>@endforelse
</div></div>
</div>

<div class="col-lg-5">
<div class="settings-card mb-4"><div class="card-body">
<form action="{{ route('website.admissions.status', $application) }}" method="POST">@csrf @method('PATCH')
<label class="form-label">Status</label>
<select name="status" class="form-select mb-3">@foreach(\App\Models\Admissions\AdmissionApplication::statuses() as $s)
<option value="{{ $s }}" @selected($application->status === $s)>{{ str_replace('_',' ', ucfirst($s)) }}</option>@endforeach</select>
<label class="form-label">Assessment date</label>
<input type="datetime-local" name="assessment_date" class="form-control mb-3" value="{{ $application->assessment_date?->format('Y-m-d\TH:i') }}">
<label class="form-label">Notes</label>
<textarea name="admission_notes" class="form-control mb-3" rows="3">{{ $application->admission_notes }}</textarea>
<button type="submit" class="btn btn-settings-primary w-100">Update Status</button>
</form></div></div>

@if($application->status === 'approved' && !$application->student_id)
<div class="settings-card"><div class="card-header">Enroll into ERP</div><div class="card-body">
<form action="{{ route('website.admissions.enroll', $application) }}" method="POST">@csrf
<div class="mb-3"><label class="form-label">Classroom</label>
<select name="classroom_id" class="form-select" required>@foreach($classrooms as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Category</label>
<select name="category_id" class="form-select" required>@foreach($categories as $cat)<option value="{{ $cat->id }}">{{ $cat->name }}</option>@endforeach</select></div>
<div class="mb-3"><label class="form-label">Residential area</label><input type="text" name="residential_area" class="form-control" required></div>
<button type="submit" class="btn btn-success w-100">Create Student Record</button>
</form></div></div>
@elseif($application->student_id)
<div class="alert alert-success">Enrolled — <a href="{{ route('students.show', $application->student_id) }}">View student</a></div>
@endif
</div></div></div></div>
@endsection
