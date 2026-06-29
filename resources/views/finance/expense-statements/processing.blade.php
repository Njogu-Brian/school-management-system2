@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
      'title' => 'Processing Statement',
      'icon' => 'bi bi-hourglass-split',
      'subtitle' => $import->original_filename,
      'actions' => '<a href="' . route('finance.expense-statements.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="row">
      <div class="col-lg-8 mx-auto">
        <div class="finance-card">
          <div class="finance-card-body p-4">

            <div id="parse-active">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0"><i class="bi bi-file-earmark-pdf text-danger me-1"></i> Parsing your M-Pesa statement</h6>
                <span class="badge bg-primary" id="parse-percent-badge">0%</span>
              </div>
              <div class="progress" style="height: 1.4rem;">
                <div id="parse-bar"
                     class="progress-bar progress-bar-striped progress-bar-animated"
                     role="progressbar" style="width: 0%;"
                     aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
              </div>
              <p class="text-muted small mt-2 mb-0" id="parse-message">Starting…</p>
              <p class="text-muted small mt-3 mb-0">
                <i class="bi bi-info-circle"></i>
                The statement is processed a few pages at a time in the background, so the
                site stays responsive. You can leave this page — the import keeps running and
                appears in the list when done.
              </p>
            </div>

            <div id="parse-failed" class="d-none">
              <div class="alert alert-danger mb-3">
                <h6 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Parsing failed</h6>
                <p class="mb-0" id="parse-error-message">{{ $import->parse_error ?: 'Something went wrong while parsing the statement.' }}</p>
              </div>
              <a href="{{ route('finance.expense-statements.create') }}" class="btn btn-finance btn-finance-primary">
                <i class="bi bi-upload"></i> Upload again
              </a>
            </div>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const progressUrl = @json(route('finance.expense-statements.parse-progress', $import->id));
  const bar = document.getElementById('parse-bar');
  const badge = document.getElementById('parse-percent-badge');
  const message = document.getElementById('parse-message');
  const activeBox = document.getElementById('parse-active');
  const failedBox = document.getElementById('parse-failed');
  const errorMsg = document.getElementById('parse-error-message');
  let stopped = false;

  function setPercent(p) {
    p = Math.max(0, Math.min(100, parseInt(p || 0, 10)));
    bar.style.width = p + '%';
    bar.setAttribute('aria-valuenow', p);
    bar.textContent = p + '%';
    badge.textContent = p + '%';
  }

  async function poll() {
    if (stopped) return;
    try {
      const res = await fetch(progressUrl, { headers: { 'Accept': 'application/json' } });
      const data = await res.json();

      setPercent(data.percent);
      if (data.message) message.textContent = data.message;

      if (data.status === 'completed') {
        stopped = true;
        bar.classList.remove('progress-bar-animated', 'progress-bar-striped');
        bar.classList.add('bg-success');
        setPercent(100);
        message.textContent = data.message || 'Done. Redirecting…';
        setTimeout(function () {
          window.location = data.redirect_url || window.location.href;
        }, 800);
        return;
      }

      if (data.status === 'failed') {
        stopped = true;
        activeBox.classList.add('d-none');
        if (data.message) errorMsg.textContent = data.message;
        failedBox.classList.remove('d-none');
        return;
      }
    } catch (e) {
      // transient network/server hiccup — keep polling
    }
    setTimeout(poll, 2000);
  }

  // If the import already failed before the page loaded, show that immediately.
  const initialStatus = @json($import->status);
  if (initialStatus === 'failed') {
    activeBox.classList.add('d-none');
    failedBox.classList.remove('d-none');
  } else {
    poll();
  }
})();
</script>
@endpush
