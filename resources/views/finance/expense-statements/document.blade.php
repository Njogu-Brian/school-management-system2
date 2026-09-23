@extends('layouts.app')

@section('content')
@php
  $securityCode = trim((string) ($import->pdf_password ?? ''));
  $verificationCode = trim((string) ($import->verification_code ?? ''));
  $needsCode = $fileExists && $encrypted && $securityCode === '';
  $canOpen = $fileExists && ! $needsCode;
@endphp
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
      'title' => 'Statement PDF',
      'icon' => 'bi bi-file-earmark-pdf',
      'subtitle' => ($import->account_name ?? 'Statement') . ' · ' . ($import->original_filename ?? ''),
      'actions' => '<a href="' . route('finance.expense-statements.show', $import) . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> Back to review</a>'
    ])

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    @if(! $fileExists)
      <div class="finance-card mb-3">
        <div class="finance-card-body p-4">
          <h6 class="mb-2">Original PDF is not stored</h6>
          <p class="text-muted mb-3">
            The transactions from this statement are already in the review screen.
            The original PDF itself was not kept, so it cannot be viewed or downloaded until you attach it.
          </p>
          <form method="POST" action="{{ route('finance.expense-statements.document.attach', $import) }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
              <label class="finance-form-label">Statement PDF</label>
              <input type="file" name="statement_file" class="finance-form-control" accept=".pdf" required>
            </div>
            <div class="mb-3">
              <label class="finance-form-label">Security code <span class="text-muted">(if the PDF is locked, as with M-Pesa)</span></label>
              <input type="password" name="pdf_password" class="finance-form-control" autocomplete="off" placeholder="Leave blank if the PDF opens without a code">
            </div>
            <button type="submit" class="btn btn-finance btn-finance-primary"><i class="bi bi-upload"></i> Attach PDF</button>
          </form>
        </div>
      </div>
    @else
      @if($needsCode)
        <div class="finance-card mb-3">
          <div class="finance-card-body p-4">
            <h6 class="mb-2">Security code required</h6>
            <p class="text-muted mb-3">
              This statement is locked. Enter the code that opens the PDF. It will be shown here before you view or download it.
              For an M-Pesa statement that code is the password Safaricom used, usually the national ID number.
            </p>
            <form method="POST" action="{{ route('finance.expense-statements.security-code', $import) }}" class="row g-2 align-items-end">
              @csrf
              <div class="col-md-6">
                <label class="finance-form-label">Security code</label>
                <input type="password" name="pdf_password" class="finance-form-control" autocomplete="off" required>
              </div>
              <div class="col-md-auto">
                <button type="submit" class="btn btn-finance btn-finance-primary">Save code</button>
              </div>
            </form>
          </div>
        </div>
      @endif

      @if($securityCode !== '' || $verificationCode !== '')
        <div class="finance-card mb-3">
          <div class="finance-card-body p-4">
            <h6 class="mb-2">Open this statement with the code below</h6>
            <p class="text-muted mb-3">Use this code if the PDF reader asks for a password. It is shown before the file opens.</p>

            @if($securityCode !== '')
              <div class="mb-3">
                <div class="text-muted small">Security code</div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  <code id="statement-security-code" class="fs-5 user-select-all">{{ $securityCode }}</code>
                  <button type="button" class="btn btn-sm btn-outline-secondary" id="copy-security-code">Copy</button>
                </div>
              </div>
            @endif

            @if($verificationCode !== '' && $verificationCode !== $securityCode)
              <div>
                <div class="text-muted small">Verification code printed on the statement</div>
                <code class="fs-5 user-select-all">{{ $verificationCode }}</code>
              </div>
            @endif
          </div>
        </div>
      @endif

      @if($canOpen)
        <div class="d-flex flex-wrap gap-2 mb-3">
          <a href="{{ route('finance.expense-statements.document.download', $import) }}" class="btn btn-finance btn-finance-primary">
            <i class="bi bi-download"></i> Download PDF
          </a>
          <a href="{{ route('finance.expense-statements.show', $import) }}" class="btn btn-finance btn-finance-secondary">Back to transactions</a>
        </div>

        <div class="finance-card">
          <div class="card-body p-0">
            <iframe
              src="{{ route('finance.expense-statements.document.view', $import) }}"
              style="width: 100%; height: 80vh; border: none;"
              title="Statement PDF">
            </iframe>
          </div>
        </div>
      @endif
    @endif
  </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('copy-security-code')?.addEventListener('click', function () {
  const code = document.getElementById('statement-security-code')?.textContent || '';
  if (!code || !navigator.clipboard) return;
  navigator.clipboard.writeText(code.trim());
  this.textContent = 'Copied';
});
</script>
@endpush
