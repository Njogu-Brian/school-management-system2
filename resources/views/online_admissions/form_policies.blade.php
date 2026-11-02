@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Online Admissions</div>
        <h1 class="mb-1">Admission form policies</h1>
        <p class="text-muted mb-0">Customize the text printed on the admission form for parents to acknowledge.</p>
      </div>
      <a href="{{ route('online-admissions.index') }}" class="btn btn-ghost-strong">Back</a>
    </div>

    @if (session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('online-admissions.form-policies.update') }}" method="POST" class="settings-card">
      @csrf
      @method('PUT')
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label">Introduction</label>
          <textarea name="intro" class="form-control" rows="3">{{ old('intro', $policies['intro']) }}</textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Fees payment policy</label>
          <textarea name="fees_policy" class="form-control" rows="6">{{ old('fees_policy', $policies['fees_policy']) }}</textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Dress code</label>
          <textarea name="dress_code" class="form-control" rows="5">{{ old('dress_code', $policies['dress_code']) }}</textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Other policies</label>
          <textarea name="other_policies" class="form-control" rows="5">{{ old('other_policies', $policies['other_policies']) }}</textarea>
        </div>
        <button type="submit" class="btn btn-settings-primary">Save policies</button>
      </div>
    </form>
  </div>
</div>
@endsection
