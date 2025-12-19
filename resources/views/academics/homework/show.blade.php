@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics</div>
        <h1 class="mb-1">{{ $homework->title }}</h1>
        <p class="text-muted mb-0">Homework details and download.</p>
      </div>
      <a href="{{ route('academics.homework.index') }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <div class="row mb-3">
          <div class="col-md-4"><strong>Classroom:</strong> {{ $homework->classroom?->name ?? 'All' }}</div>
          <div class="col-md-4"><strong>Subject:</strong> {{ $homework->subject?->name ?? 'N/A' }}</div>
          <div class="col-md-4"><strong>Due Date:</strong> <span class="pill-badge pill-info">{{ $homework->due_date->format('d M Y') }}</span></div>
        </div>
        <div class="mb-3"><strong>Instructions:</strong><br>{{ $homework->instructions }}</div>
        @if($homework->file_path)
          <a href="{{ asset('storage/'.$homework->file_path) }}" class="btn btn-settings-primary" target="_blank"><i class="bi bi-download"></i> Download File</a>
        @endif
      </div>
    </div>

    <div class="alert alert-soft alert-info border-0">
      <i class="bi bi-info-circle"></i> Diary conversations for homework have moved to the Digital Diaries module. <a href="{{ route('academics.diaries.index') }}" class="text-reset text-decoration-underline">Open the diary</a> to continue discussions.
    </div>
  </div>
</div>
@endsection
