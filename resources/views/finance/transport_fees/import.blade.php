@extends('layouts.app')

@section('content')
  @include('finance.partials.header', [
      'title' => 'Import Transport Fees',
      'icon' => 'bi bi-upload',
      'subtitle' => 'Upload and process transport fee imports',
      'actions' => '<a href="' . route('finance.transport-fees.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Transport Fees</a>
                    <a href="' . route('finance.transport-fees.import-history') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-clock-history"></i> Import History</a>'
  ])

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  @if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show finance-animate" role="alert">
      {{ session('warning') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
          <i class="bi bi-file-earmark-spreadsheet"></i>
          <span>Upload Transport Fee File</span>
        </div>
        <div class="finance-card-body p-4">
          <p class="text-muted mb-4">Upload an Excel file with columns: <strong>Admission Number</strong>, <strong>Student Name</strong>, <strong>Transport Fee</strong>, <strong>Drop-off Point</strong>.</p>
          
          <form method="POST" action="{{ route('finance.transport-fees.import.preview') }}" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
              <label class="finance-form-label">File (.xlsx/.csv)</label>
              <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
              <small class="text-muted">Supported formats: .xlsx, .xls, .csv</small>
            </div>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="finance-form-label">Year</label>
                <input type="number" name="year" class="finance-form-control" value="{{ request('year', date('Y')) }}" required>
              </div>
              <div class="col-md-6">
                <label class="finance-form-label">Term</label>
                <select name="term" class="finance-form-select" required>
                  @foreach([1,2,3] as $t)
                    <option value="{{ $t }}" @selected(request('term') == $t)>Term {{ $t }}</option>
                  @endforeach
                </select>
              </div>
            </div>
            <button class="btn btn-finance btn-finance-primary w-100 mt-3">
              <i class="bi bi-eye"></i> Preview &amp; Apply
            </button>
          </form>
          
          <div class="d-flex justify-content-between align-items-center mt-3">
            <a class="btn btn-link p-0" href="{{ route('finance.transport-fees.template') }}">
              <i class="bi bi-download"></i> Download template
            </a>
          </div>
        </div>
      </div>

      <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mt-4">
        <div class="finance-card-header">
          <i class="bi bi-info-circle"></i>
          <span>Import Guidelines</span>
        </div>
        <div class="finance-card-body p-4">
          <ul class="mb-0">
            <li>Transport charges are written directly to invoices (one invoice per student per term)</li>
            <li>Drop-off points will be created automatically if they don't exist</li>
            <li>You can preview and confirm changes before applying them</li>
            <li>All imports are tracked in the import history</li>
            <li>Imports can be reversed if needed</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header">
          <i class="bi bi-clock-history"></i>
          <span>Recent Imports</span>
        </div>
        <div class="finance-card-body p-4">
          @php
            $recentImports = \App\Models\TransportFeeImport::with('importedBy')
              ->orderBy('imported_at', 'desc')
              ->limit(5)
              ->get();
          @endphp
          
          @forelse($recentImports as $import)
            <div class="d-flex justify-content-between align-items-start mb-3 pb-3 border-bottom">
              <div>
                <div class="fw-semibold small">{{ $import->imported_at->format('M d, Y') }}</div>
                <div class="text-muted small">{{ $import->importedBy->name ?? '—' }}</div>
                <div class="text-muted small">Term {{ $import->term }}, {{ $import->year }}</div>
              </div>
              <div class="text-end">
                <span class="badge bg-{{ $import->is_reversed ? 'secondary' : 'success' }}">
                  {{ $import->is_reversed ? 'Reversed' : 'Active' }}
                </span>
                <div class="text-muted small mt-1">{{ number_format($import->fees_imported_count) }} records</div>
              </div>
            </div>
          @empty
            <p class="text-muted text-center mb-0">No recent imports</p>
          @endforelse
          
          <a href="{{ route('finance.transport-fees.import-history') }}" class="btn btn-outline-primary w-100 mt-3">
            <i class="bi bi-clock-history"></i> View Full History
          </a>
        </div>
      </div>
    </div>
  </div>
@endsection
