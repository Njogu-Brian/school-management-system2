@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Report Card</div>
        <h1 class="mb-1">Report Card</h1>
        <p class="text-muted mb-0">Summary view with remarks and skills.</p>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <h5 class="mb-1">Summary</h5>
            <p class="text-muted mb-0">{{ $report_card->summary ?? 'No summary provided.' }}</p>
          </div>
          <div class="col-md-6">
            <h5 class="mb-1">Teacher Remark</h5>
            <p class="text-muted mb-0">{{ $report_card->teacher_remark ?? '-' }}</p>
          </div>
          <div class="col-md-6">
            <h5 class="mb-1">Headteacher Remark</h5>
            <p class="text-muted mb-0">{{ $report_card->headteacher_remark ?? '-' }}</p>
          </div>
        </div>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-header"><h5 class="mb-0">Skills & Personal Growth</h5></div>
      <div class="card-body">
        <ul class="mb-0">
          @forelse($report_card->skills as $skill)
            <li>{{ $skill->skill_name }} - <strong>{{ $skill->rating }}</strong></li>
          @empty
            <li class="text-muted">No skills added.</li>
          @endforelse
        </ul>
      </div>
    </div>
  </div>
</div>
@endsection
