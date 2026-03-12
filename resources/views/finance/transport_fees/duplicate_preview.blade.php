@extends('layouts.app')

@section('content')
<div class="finance-page transport-fees-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
        'title' => 'Duplicate Transport Fees – Preview',
        'icon' => 'bi bi-copy',
        'subtitle' => 'Review and approve or reject each fee before duplicating',
    ])

    <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
      <div class="finance-card-header d-flex align-items-center gap-2">
        <i class="bi bi-info-circle"></i>
        <span>Summary</span>
      </div>
      <div class="finance-card-body p-4">
        <div class="row g-3">
          <div class="col-md-6">
            <strong>Source:</strong> {{ $sourceYear }} Term {{ $sourceTerm }}
          </div>
          <div class="col-md-6">
            <strong>Target:</strong> {{ $targetYear }} Term {{ $targetTerm }}
          </div>
          <div class="col-12">
            <p class="text-muted small mb-0">
              <i class="bi bi-check2-square"></i> Check items to approve. Uncheck to reject – rejected items will not be duplicated and will not appear in invoices. You can add them later from the transport fee view.
            </p>
          </div>
        </div>
      </div>
    </div>

    <form method="POST" action="{{ route('finance.transport-fees.duplicate.commit') }}">
      @csrf
      <input type="hidden" name="source_year_term" value="{{ $sourceYear }}|{{ $sourceTerm }}">
      <input type="hidden" name="target_year_term" value="{{ $targetYear }}|{{ $targetTerm }}">

      <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
        <div class="finance-card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
          <span><i class="bi bi-list-check me-2"></i>Proposed transport fees ({{ count($items) }})</span>
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="approveAll">Approve all</button>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="rejectAll">Reject all</button>
          </div>
        </div>
        <div class="finance-card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width: 48px;">
                    <input type="checkbox" class="form-check-input" id="toggleAll" checked>
                  </th>
                  <th>Student</th>
                  <th>Drop-off point</th>
                  <th class="text-end">Amount (KES)</th>
                </tr>
              </thead>
              <tbody>
                @foreach($items as $item)
                  @php
                    $rowData = base64_encode(json_encode([
                      'student_id' => $item['student_id'],
                      'amount' => $item['amount'],
                      'drop_off_point_id' => $item['drop_off_point_id'] ?? null,
                      'drop_off_point_name' => $item['drop_off_point_name'] ?? null,
                    ]));
                  @endphp
                  <tr>
                    <td>
                      <input type="checkbox" class="form-check-input row-approve" name="approved[]" value="{{ $rowData }}" checked>
                    </td>
                    <td>{{ $item['student_name'] }}</td>
                    <td>{{ $item['drop_off_point_name'] ?? '—' }}</td>
                    <td class="text-end">{{ number_format($item['amount'], 2) }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
        <div class="finance-card-body border-top d-flex flex-wrap gap-2 align-items-center">
          <button type="submit" class="btn btn-finance btn-finance-primary">
            <i class="bi bi-check-lg me-2"></i>Duplicate approved items
          </button>
          <a href="{{ route('finance.transport-fees.index') }}" class="btn btn-outline-secondary">Cancel</a>
          <span class="text-muted small ms-2" id="approvedCount">{{ count($items) }} approved</span>
        </div>
      </div>
    </form>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const toggleAll = document.getElementById('toggleAll');
  const approveAll = document.getElementById('approveAll');
  const rejectAll = document.getElementById('rejectAll');
  const checkboxes = document.querySelectorAll('.row-approve');
  const countEl = document.getElementById('approvedCount');

  function updateCount() {
    const n = document.querySelectorAll('.row-approve:checked').length;
    if (countEl) countEl.textContent = n + ' approved';
    if (toggleAll) toggleAll.checked = n === checkboxes.length;
    if (toggleAll) toggleAll.indeterminate = n > 0 && n < checkboxes.length;
  }

  toggleAll?.addEventListener('change', function() {
    checkboxes.forEach(cb => { cb.checked = toggleAll.checked; });
    updateCount();
  });

  approveAll?.addEventListener('click', function() {
    checkboxes.forEach(cb => { cb.checked = true; });
    if (toggleAll) { toggleAll.checked = true; toggleAll.indeterminate = false; }
    updateCount();
  });

  rejectAll?.addEventListener('click', function() {
    checkboxes.forEach(cb => { cb.checked = false; });
    if (toggleAll) { toggleAll.checked = false; toggleAll.indeterminate = false; }
    updateCount();
  });

  checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
  updateCount();
});
</script>
@endpush
@endsection
