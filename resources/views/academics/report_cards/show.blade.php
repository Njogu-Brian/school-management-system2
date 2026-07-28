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
        <p class="text-muted mb-0">Preview and publish the student report card.</p>
      </div>
      <div class="d-flex flex-wrap gap-2 align-items-center">
        <a href="{{ route('academics.report_cards.pdf', $report_card) }}" target="_blank" class="btn btn-ghost-strong"><i class="bi bi-printer"></i> Build & Download PDF</a>
        @php
          $familyPortal = family_report_portal_link_for_student(
            $report_card->student,
            $report_card->academic_year_id,
            $report_card->term_id
          );
        @endphp
        @if($familyPortal)
          <a href="{{ $familyPortal->getUrl() }}" target="_blank" class="btn btn-outline-secondary">
            <i class="bi bi-link-45deg"></i> Family Portal Link
          </a>
        @endif
        @if(!$report_card->locked_at && !$report_card->published_at)
        <form action="{{ route('academics.report_cards.publish',$report_card) }}" method="POST" class="d-inline-block border rounded p-2 bg-white text-dark" style="color: var(--settings-text) !important;">
          @csrf
          <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" name="notify_parents" value="1" id="notifyFamily">
            <label class="form-check-label small text-dark" for="notifyFamily">Publish &amp; send family link</label>
          </div>
          <div class="d-flex flex-wrap gap-2 mb-1" id="showPublishChannels">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="showChSms" checked>
              <label class="form-check-label small text-dark" for="showChSms">SMS</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" id="showChWa" checked>
              <label class="form-check-label small text-dark" for="showChWa">WhatsApp</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="showChEmail" checked>
              <label class="form-check-label small text-dark" for="showChEmail">Email</label>
            </div>
          </div>
          <button class="btn btn-settings-primary btn-sm mt-1"><i class="bi bi-upload"></i> Publish</button>
        </form>
        @endif
      </div>
    </div>

    <div class="settings-card">
      <div class="card-body">
        @include('academics.report_cards.partials.core', ['dto' => $dto, 'isPdf' => false])
      </div>
    </div>
  </div>
</div>
@endsection
