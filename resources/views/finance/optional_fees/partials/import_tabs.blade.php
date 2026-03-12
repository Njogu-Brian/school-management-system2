<div class="finance-card shadow-sm rounded-4 border-0">
  <ul class="nav nav-tabs px-3 pt-3" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#optional-import-tab" type="button">Import</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#optional-history-tab" type="button">Import History</button>
    </li>
  </ul>
  <div class="tab-content finance-card-body p-4">
    <div class="tab-pane fade show active" id="optional-import-tab">
      <p class="text-muted small">Upload Excel: Name, Admission Number, then votehead columns (Yorghut, Skating, etc.).</p>
      <form method="POST" action="{{ route('finance.optional-fees.import.preview') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
          <label class="finance-form-label">File (.xlsx/.csv)</label>
          <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="finance-form-label">Year</label>
            <input type="number" name="year" class="finance-form-control" value="{{ $currentYear ?? $defaultYear ?? now()->year }}" required>
          </div>
          <div class="col-6">
            <label class="finance-form-label">Term</label>
            <select name="term" class="finance-form-select" required>
              @foreach([1,2,3] as $t)
                <option value="{{ $t }}" @selected(($currentTermNumber ?? $defaultTerm ?? 1) == $t)>Term {{ $t }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="d-flex gap-2 mt-3">
          <button class="btn btn-finance btn-finance-primary">
            <i class="bi bi-eye"></i> Preview &amp; apply
          </button>
          <a class="btn btn-outline-secondary" href="{{ route('finance.optional-fees.import.template') }}">
            <i class="bi bi-download"></i> Template
          </a>
        </div>
      </form>
    </div>
    <div class="tab-pane fade" id="optional-history-tab">
      @php
        $imports = \App\Models\OptionalFeeImport::with('importedBy', 'reversedBy')
          ->orderBy('imported_at', 'desc')
          ->limit(10)
          ->get();
      @endphp
      @if($imports->count() > 0)
        <div class="table-responsive">
          <table class="finance-table align-middle">
            <thead>
              <tr>
                <th>Date</th>
                <th>By</th>
                <th class="text-end">Records</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($imports as $imp)
              <tr>
                <td>{{ $imp->imported_at?->format('M d, Y') }}</td>
                <td>{{ $imp->importedBy->name ?? '—' }}</td>
                <td class="text-end">{{ number_format($imp->fees_imported_count ?? 0) }}</td>
                <td>
                  @if($imp->is_reversed ?? false)
                    <span class="badge bg-secondary">Reversed</span>
                  @else
                    <span class="badge bg-success">Active</span>
                  @endif
                </td>
                <td>
                  <a href="{{ route('finance.optional-fees.import-details', $imp) }}" class="btn btn-sm btn-outline-primary">View</a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <a href="{{ route('finance.optional-fees.import-history') }}" class="btn btn-sm btn-outline-secondary mt-2">Full history</a>
      @else
        <p class="text-muted mb-0">No imports yet.</p>
      @endif
    </div>
  </div>
</div>
