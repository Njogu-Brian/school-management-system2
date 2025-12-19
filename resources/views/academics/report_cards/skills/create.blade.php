@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Report Card Skills</div>
        <h1 class="mb-1">Add Skill</h1>
        <p class="text-muted mb-0">Attach a new skill rating to this report card.</p>
      </div>
      <a href="{{ route('academics.report_cards.skills.index',$reportCard) }}" class="btn btn-ghost-strong"><i class="bi bi-arrow-left"></i> Back</a>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.report_cards.skills.store',$reportCard) }}" method="POST" class="row g-3">
          @csrf
          @include('academics.report_cards.skills.partials.form',['skill'=>null])
          <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('academics.report_cards.skills.index',$reportCard) }}" class="btn btn-ghost-strong">Cancel</a>
            <button class="btn btn-settings-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
