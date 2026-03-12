<div class="finance-card transport-sidebar-card shadow-sm rounded-4 border-0">
  <ul class="nav nav-tabs transport-tabs px-3 pt-3" role="tablist">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#transport-import-tab" type="button"><i class="bi bi-file-earmark-arrow-up me-1"></i>Import</button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#transport-history-tab" type="button"><i class="bi bi-clock-history me-1"></i>History</button>
    </li>
  </ul>
  <div class="tab-content finance-card-body p-4">
    <div class="tab-pane fade show active" id="transport-import-tab">
      <p class="text-muted small">Upload Excel with: Admission Number, Student Name, Transport Fee, Drop-off Point.</p>
      <form method="POST" action="{{ route('finance.transport-fees.import.preview') }}" enctype="multipart/form-data">
        @csrf
        <div class="mb-3">
          <label class="finance-form-label">File (.xlsx/.csv)</label>
          <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="finance-form-label">Year</label>
            <input type="number" name="year" class="finance-form-control" value="{{ $year }}" required>
          </div>
          <div class="col-6">
            <label class="finance-form-label">Term</label>
            <select name="term" class="finance-form-select" required>
              @foreach([1,2,3] as $t)
                <option value="{{ $t }}" @selected(($term ?? 1) == $t)>Term {{ $t }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <button class="btn btn-finance btn-finance-primary w-100 mt-3">
          <i class="bi bi-eye"></i> Preview &amp; apply
        </button>
      </form>
      <div class="mt-3">
        <a class="btn btn-finance btn-finance-outline btn-sm w-100" href="{{ route('finance.transport-fees.template') }}">
          <i class="bi bi-download me-2"></i>Download template
        </a>
      </div>
      <div class="transport-info-pill mt-3">
        <i class="bi bi-info-circle me-2"></i>Charges sync to invoices (one per student per term).
      </div>
    </div>
    <div class="tab-pane fade" id="transport-history-tab">
      @php
        $recentImports = \App\Models\TransportFeeImport::with('importedBy', 'reversedBy')
          ->when($year ?? null, fn($q) => $q->where('year', $year))
          ->when($term ?? null, fn($q) => $q->where('term', $term))
          ->orderBy('imported_at', 'desc')
          ->limit(10)
          ->get();
      @endphp
      @if($recentImports->count() > 0)
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
              @foreach($recentImports as $import)
              <tr>
                <td>{{ $import->imported_at?->format('M d, Y') }}</td>
                <td>{{ $import->importedBy->name ?? '—' }}</td>
                <td class="text-end">{{ number_format($import->fees_imported_count ?? 0) }}</td>
                <td>
                  @if($import->is_reversed)
                    <span class="badge bg-secondary">Reversed</span>
                  @else
                    <span class="badge bg-success">Active</span>
                  @endif
                </td>
                <td>
                  <a href="{{ route('finance.transport-fees.import-details', $import) }}" class="btn btn-sm btn-outline-primary">View</a>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <a href="{{ route('finance.transport-fees.import-history') }}" class="btn btn-finance btn-finance-outline btn-sm w-100 mt-3">Full history</a>
      @else
        <p class="text-muted mb-0 small">No imports yet.</p>
      @endif
    </div>
  </div>
</div>
