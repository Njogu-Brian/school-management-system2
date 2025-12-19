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
        <h1 class="mb-1">Edit Report Card</h1>
        <p class="text-muted mb-0">Update summary, interests, and remarks.</p>
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body">
        <form action="{{ route('academics.report_cards.update',$report_card) }}" method="POST" class="row g-3">
          @csrf @method('PUT')

          <div class="col-12">
            <label class="form-label">Summary</label>
            <textarea name="summary" class="form-control" rows="3">{{ old('summary',$report_card->summary) }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Career Interest</label>
            <input type="text" name="career_interest" class="form-control" value="{{ old('career_interest',$report_card->career_interest) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Talent Noticed</label>
            <input type="text" name="talent_noticed" class="form-control" value="{{ old('talent_noticed',$report_card->talent_noticed) }}">
          </div>
          <div class="col-md-6">
            <label class="form-label">Teacher Remark</label>
            <textarea name="teacher_remark" class="form-control" rows="2">{{ old('teacher_remark',$report_card->teacher_remark) }}</textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Headteacher Remark</label>
            <textarea name="headteacher_remark" class="form-control" rows="2">{{ old('headteacher_remark',$report_card->headteacher_remark) }}</textarea>
          </div>

          <div class="col-12 d-flex justify-content-end gap-2">
            <a href="{{ route('academics.report_cards.show',$report_card) }}" class="btn btn-ghost-strong">Cancel</a>
            <button class="btn btn-settings-primary">Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
