@extends('layouts.app')

@section('content')
  @include('finance.partials.header', [
      'title' => 'Optional Fee Import Details',
      'icon' => 'bi bi-file-earmark-spreadsheet',
      'subtitle' => 'Detailed view of import #' . $import->id,
      'actions' => '<a href="' . route('finance.optional-fees.import-history') . '" class="btn btn-finance btn-finance-outline"><i class="bi bi-arrow-left"></i> Back to History</a>'
  ])

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header">
          <i class="bi bi-info-circle"></i>
          <span>Import Information</span>
        </div>
        <div class="finance-card-body p-4">
          <dl class="row mb-0">
            <dt class="col-sm-5">Import Date:</dt>
            <dd class="col-sm-7">{{ $import->imported_at ? $import->imported_at->format('M d, Y H:i:s') : '—' }}</dd>
            
            <dt class="col-sm-5">Year/Term:</dt>
            <dd class="col-sm-7">{{ $import->year }} - Term {{ $import->term }}</dd>
            
            <dt class="col-sm-5">Imported By:</dt>
            <dd class="col-sm-7">{{ $import->importedBy->name ?? '—' }}</dd>
            
            <dt class="col-sm-5">Records:</dt>
            <dd class="col-sm-7">{{ number_format($import->fees_imported_count) }}</dd>
            
            <dt class="col-sm-5">Total Amount:</dt>
            <dd class="col-sm-7"><strong>KES {{ number_format($import->total_amount, 2) }}</strong></dd>
            
            <dt class="col-sm-5">Status:</dt>
            <dd class="col-sm-7">
              @if($import->is_reversed)
                <span class="badge bg-secondary">Reversed</span>
                @if($import->reversed_at)
                  <br><small class="text-muted">{{ $import->reversed_at->format('M d, Y H:i') }}</small>
                  <br><small class="text-muted">By: {{ $import->reversedBy->name ?? '—' }}</small>
                @endif
              @else
                <span class="badge bg-success">Active</span>
              @endif
            </dd>
            
            @if($import->notes)
            <dt class="col-sm-5">Notes:</dt>
            <dd class="col-sm-7">{{ $import->notes }}</dd>
            @endif
          </dl>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
        <div class="finance-card-header d-flex align-items-center gap-2">
          <i class="bi bi-list-ul"></i>
          <span>Imported Optional Fees</span>
          <span class="badge bg-secondary ms-auto">{{ $optionalFees->count() }} records</span>
        </div>
        <div class="finance-card-body p-4">
          <div class="table-responsive">
            <table class="finance-table align-middle">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Class</th>
                  <th>Votehead</th>
                  <th class="text-end">Amount</th>
                </tr>
              </thead>
              <tbody>
                @forelse($optionalFees as $fee)
                <tr>
                  <td>
                    <div class="fw-semibold">{{ $fee->student->full_name ?? '—' }}</div>
                    <div class="text-muted small">Adm: {{ $fee->student->admission_number ?? '—' }}</div>
                  </td>
                  <td>{{ $fee->student->classroom->name ?? '—' }}</td>
                  <td>{{ $fee->votehead->name ?? '—' }}</td>
                  <td class="text-end">KES {{ number_format($fee->amount, 2) }}</td>
                </tr>
                @empty
                <tr>
                  <td colspan="4" class="text-center text-muted py-5">
                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                    <p class="mt-3">No optional fees found for this import</p>
                  </td>
                </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
