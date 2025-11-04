@extends('layouts.app')

@section('content')
<div class="container">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Invoice: {{ $invoice->invoice_number }}</h3>

    {{-- 🔹 Print PDF Button --}}
    <a href="{{ route('finance.invoices.print_single', $invoice) }}" 
       target="_blank" 
       class="btn btn-outline-secondary">
       <i class="bi bi-printer"></i> Print PDF
    </a>
  </div>

  @includeIf('finance.invoices.partials.alerts')

  <div class="card mb-3 shadow-sm">
    <div class="card-body">
      <div class="fw-bold">{{ $invoice->student->full_name ?? 'Unknown' }}</div>
      <div class="text-muted">
        Adm: {{ $invoice->student->admission_number ?? '-' }} |
        Class: {{ $invoice->student->classroom->name ?? '-' }} / {{ $invoice->student->stream->name ?? '-' }}
      </div>
      <div class="mt-2">
        Period: <span class="badge bg-secondary">{{ $invoice->year }} / Term {{ $invoice->term }}</span>
        <span class="ms-3">Total: <span class="badge bg-primary">{{ number_format($invoice->total,2) }}</span></span>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header fw-bold">Items</div>
    <div class="card-body table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th>#</th>
            <th>Votehead</th>
            <th class="text-end">Amount</th>
            <th>Status</th>
            <th>Effective</th>
            <th>Edit</th>
          </tr>
        </thead>
        <tbody>
          @forelse($invoice->items as $item)
            <tr>
              <td>{{ $loop->iteration }}</td>
              <td>{{ $item->votehead->name ?? 'Unknown' }}</td>
              <td class="text-end">{{ number_format($item->amount,2) }}</td>
              <td>
                @if($item->status === 'active')
                  <span class="badge bg-success">Active</span>
                @else
                  <span class="badge bg-warning text-dark">Pending</span>
                @endif
              </td>
              <td>
                @php
                  $ed = $item->effective_date;
                @endphp
                {{ $ed ? (method_exists($ed, 'format') ? $ed->format('Y-m-d') : $ed) : '-' }}
              </td>
              <td>
                <form method="POST" action="{{ route('finance.invoices.items.update', [$invoice->id, $item->id]) }}" class="d-flex gap-2">
                  @csrf
                  <input type="number" step="0.01" name="new_amount" class="form-control form-control-sm" 
                         value="{{ $item->amount }}" style="max-width:120px;">
                  <input type="text" name="reason" class="form-control form-control-sm" 
                         placeholder="Reason" style="max-width:200px;" required>
                  <button class="btn btn-sm btn-outline-primary">Update</button>
                </form>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="text-center text-muted">No items</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
