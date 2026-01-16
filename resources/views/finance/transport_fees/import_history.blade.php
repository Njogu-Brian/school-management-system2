@extends('layouts.app')

@section('content')
  @include('finance.partials.header', [
      'title' => 'Transport Fee Import History',
      'icon' => 'bi bi-clock-history',
      'subtitle' => 'View all transport fee imports and their details',
      'actions' => '<a href="' . route('finance.transport-fees.index') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to Transport Fees</a>'
  ])

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show finance-animate" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="finance-card finance-animate shadow-sm rounded-4 border-0 mb-4">
    <div class="finance-card-body p-4">
      <form method="GET" class="row g-3">
        <div class="col-md-3">
          <label class="finance-form-label">Year</label>
          <input type="number" name="year" class="finance-form-control" value="{{ $year }}" placeholder="Filter by year">
        </div>
        <div class="col-md-3">
          <label class="finance-form-label">Term</label>
          <select name="term" class="finance-form-select">
            <option value="">All Terms</option>
            @foreach([1,2,3] as $t)
              <option value="{{ $t }}" @selected($term == $t)>Term {{ $t }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <button type="submit" class="btn btn-finance btn-finance-primary">
            <i class="bi bi-funnel"></i> Filter
          </button>
          @if($year || $term)
            <a href="{{ route('finance.transport-fees.import-history') }}" class="btn btn-outline-secondary ms-2">
              <i class="bi bi-x-circle"></i> Clear
            </a>
          @endif
        </div>
      </form>
    </div>
  </div>

  <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
    <div class="finance-card-header d-flex align-items-center gap-2">
      <i class="bi bi-list-ul"></i>
      <span>Import History</span>
      <span class="badge bg-secondary ms-auto">{{ $imports->total() }} total</span>
    </div>
    <div class="finance-card-body p-4">
      <div class="table-responsive">
        <table class="finance-table align-middle">
          <thead>
            <tr>
              <th>Import Date</th>
              <th>Year/Term</th>
              <th>Imported By</th>
              <th class="text-end">Records</th>
              <th class="text-end">Drop-off Points</th>
              <th class="text-end">Total Amount</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($imports as $import)
            <tr>
              <td>
                <div class="fw-semibold">{{ $import->imported_at ? $import->imported_at->format('M d, Y') : '—' }}</div>
                <div class="text-muted small">{{ $import->imported_at ? $import->imported_at->format('H:i:s') : '—' }}</div>
              </td>
              <td>
                <div>{{ $import->year }}</div>
                <div class="text-muted small">Term {{ $import->term }}</div>
              </td>
              <td>{{ $import->importedBy->name ?? '—' }}</td>
              <td class="text-end">{{ number_format($import->fees_imported_count) }}</td>
              <td class="text-end">{{ number_format($import->drop_off_points_created_count) }}</td>
              <td class="text-end">KES {{ number_format($import->total_amount, 2) }}</td>
              <td>
                @if($import->is_reversed)
                  <span class="badge bg-secondary">Reversed</span>
                  @if($import->reversed_at)
                    <br><small class="text-muted">{{ $import->reversed_at->format('M d, Y') }}</small>
                  @endif
                @else
                  <span class="badge bg-success">Active</span>
                @endif
              </td>
              <td>
                <div class="d-flex gap-2">
                  <a href="{{ route('finance.transport-fees.import-details', $import) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View
                  </a>
                  @if(!$import->is_reversed)
                    <form method="POST" action="{{ route('finance.transport-fees.import.reverse', $import) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to reverse this import? This will delete all invoice line items and drop-off point assignments created by this import.');">
                      @csrf
                      @method('POST')
                      <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-arrow-counterclockwise"></i> Reverse
                      </button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="8" class="text-center text-muted py-5">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-3">No import history found</p>
              </td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($imports->hasPages())
        <div class="mt-4">
          {{ $imports->links() }}
        </div>
      @endif
    </div>
  </div>
@endsection
