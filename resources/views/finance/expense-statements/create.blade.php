@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
      'title' => 'Upload M-Pesa Statement',
      'icon' => 'bi bi-upload',
      'subtitle' => 'Parse outgoing transactions for expense review',
      'actions' => '<a href="' . route('finance.expense-statements.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back</a>'
    ])

    <div class="row">
      <div class="col-lg-8 mx-auto">
        <div class="finance-card">
          <div class="finance-card-body p-4">
            <form method="POST" action="{{ route('finance.expense-statements.store') }}" enctype="multipart/form-data">
              @csrf

              <div class="mb-4">
                <label class="finance-form-label">M-Pesa Statement PDF</label>
                <input type="file" name="statement_file" class="finance-form-control @error('statement_file') is-invalid @enderror" accept=".pdf" required>
                <small class="text-muted d-block">Safaricom M-PESA detailed statement (max 20MB). Bank statements coming later.</small>
                <small class="text-muted d-block mt-1">Large statements are processed in the background a few pages at a time, so the site stays responsive. You'll see a live progress bar after uploading.</small>
                @error('statement_file')<div class="invalid-feedback">{{ $message }}</div>@enderror
              </div>

              <div class="mb-4">
                <label class="finance-form-label">PDF Password <span class="text-muted">(if protected)</span></label>
                <input type="password" name="pdf_password" value="{{ old('pdf_password') }}" class="finance-form-control @error('pdf_password') is-invalid @enderror" placeholder="Enter statement password" autocomplete="off">
                @error('pdf_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @if($errors->has('password_required'))
                  <small class="text-warning">This file appears to be password protected. Enter the password and upload again.</small>
                @endif
              </div>

              <div class="d-flex justify-content-end gap-2 align-items-center">
                <a href="{{ route('finance.expense-statements.index') }}" class="btn btn-finance btn-finance-secondary" id="upload-cancel-btn">Cancel</a>
                <button type="submit" class="btn btn-finance btn-finance-primary" id="upload-submit-btn">
                  <span class="upload-btn-label"><i class="bi bi-upload"></i> Upload &amp; Analyze</span>
                  <span class="upload-btn-busy d-none"><span class="spinner-border spinner-border-sm me-1"></span> Uploading…</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <div id="upload-progress-note" class="alert alert-info d-none mt-3 mb-0">
          <strong>Uploading your statement…</strong> You'll be taken to a live progress screen while it's parsed in the background.
        </div>

        <div class="finance-card mt-4">
          <div class="finance-card-body p-4">
            <h6 class="mb-3">What this analyzer does</h6>
            <ul class="mb-0">
              <li>Extracts transactions from Receipt No, Completion Time, Details, and Withdrawn columns</li>
              <li>Classifies Send Money, Pochi la Biashara, Buy Goods, Pay Bill, and transaction fees</li>
              <li>Groups similar payments to the same recipient so you can confirm business vs personal spend</li>
              <li>Includes M-Pesa charges (e.g. Customer Transfer of Funds Charge) when you mark a group as expense</li>
              <li>Remembers your choices for recurring recipients on future imports</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.querySelector('form[action="{{ route('finance.expense-statements.store') }}"]')?.addEventListener('submit', function () {
  const btn = document.getElementById('upload-submit-btn');
  const cancel = document.getElementById('upload-cancel-btn');
  const note = document.getElementById('upload-progress-note');
  if (btn) {
    btn.disabled = true;
    btn.querySelector('.upload-btn-label')?.classList.add('d-none');
    btn.querySelector('.upload-btn-busy')?.classList.remove('d-none');
  }
  if (cancel) cancel.classList.add('pe-none', 'opacity-50');
  note?.classList.remove('d-none');
});
</script>
@endpush
