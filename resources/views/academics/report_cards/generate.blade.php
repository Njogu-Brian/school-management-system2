@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
      <div>
        <div class="crumb">Academics · Report Cards</div>
        <h1 class="mb-1">Generate Report Cards</h1>
        <p class="text-muted mb-0">Create/update report cards for a class & term.</p>
      </div>
    </div>

    <div class="alert alert-soft alert-info border-0">
      This creates/updates report cards for the selected class & term by averaging all exams in the term.
    </div>

    <form method="post" action="{{ route('academics.report_cards.generate') }}" class="settings-card">
      @csrf
      <div class="card-body">
        <div class="row g-3">
          @include('partials.academic_year_term_selects', [
            'years' => $years,
            'terms' => $terms,
            'selectedYearId' => $selectedYearId ?? null,
            'selectedTermId' => $selectedTermId ?? null,
            'yearRequired' => true,
            'termRequired' => true,
          ])
          <div class="col-md-3">
            <label class="form-label">Classroom</label>
            <select name="classroom_id" class="form-select" required>
              <option value="">-- choose --</option>
              @foreach($classrooms as $c)
                <option value="{{ $c->id }}">{{ $c->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Stream (optional)</label>
            <select name="stream_id" class="form-select">
              <option value="">All streams</option>
              @foreach($streams as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="col-12">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="publish_and_notify" value="1" id="publishAndNotify">
            <label class="form-check-label fw-semibold" for="publishAndNotify">Publish and notify parents after generation</label>
          </div>
          <div id="publishChannelsWrap" class="mt-2 ps-4" style="display:none;">
            <label class="form-label small text-muted mb-1">Notification channels</label>
            <div class="d-flex flex-wrap gap-3">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="channels[]" value="sms" id="genChannelSms" checked>
                <label class="form-check-label" for="genChannelSms">SMS</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="channels[]" value="whatsapp" id="genChannelWa" checked>
                <label class="form-check-label" for="genChannelWa">WhatsApp</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="channels[]" value="email" id="genChannelEmail" checked>
                <label class="form-check-label" for="genChannelEmail">Email</label>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer d-flex justify-content-between flex-wrap gap-2">
        <a href="{{ route('academics.assessments.term') }}" class="btn btn-ghost-strong">View Term Assessment</a>
        <button class="btn btn-settings-primary"><i class="bi bi-gear"></i> Generate Now</button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const cb = document.getElementById('publishAndNotify');
  const wrap = document.getElementById('publishChannelsWrap');
  cb?.addEventListener('change', () => {
    if (wrap) wrap.style.display = cb.checked ? 'block' : 'none';
  });
});
</script>
@endpush
