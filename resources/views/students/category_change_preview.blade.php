@extends('layouts.app')

@push('styles')
    @include('settings.partials.styles')
@endpush

@section('content')
<div class="settings-page">
  <div class="settings-shell">
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
      <div>
        <div class="crumb">Students</div>
        <h1 class="mb-1">Confirm Category Change</h1>
        <p class="text-muted mb-0">Changing the category mid-term will update the current term invoice and remove discounts.</p>
      </div>
      <a href="{{ url()->previous() ?: route('students.index') }}" class="btn btn-ghost-strong">
        <i class="bi bi-arrow-left"></i> Back
      </a>
    </div>

    <div class="alert alert-warning">
      <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle"></i> Important</div>
      <ul class="mb-0">
        <li>Current term invoice (Term {{ optional($existingInvoice)->term ?? get_current_term_number() }}, Year {{ optional($existingInvoice)->year ?? get_current_academic_year() }}) will be updated to the new category fee structure.</li>
        <li>Previous terms are not affected. Payments stay intact.</li>
        <li>All discounts for this student will be removed; invoice totals will reflect the new category.</li>
      </ul>
    </div>

    <div class="settings-card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="mb-0">Category</h5>
        <span class="pill-badge pill-primary">{{ $oldCategory->name ?? '—' }} → {{ $newCategory->name ?? '—' }}</span>
      </div>
      <div class="card-body row g-3">
        <div class="col-md-6">
          <div class="shadow-sm p-3 rounded border">
            <div class="text-muted small">Before (current invoice)</div>
            <div class="fs-4 fw-semibold">KES {{ number_format($beforeTotal, 2) }}</div>
          </div>
        </div>
        <div class="col-md-6">
          <div class="shadow-sm p-3 rounded border">
            <div class="text-muted small">After (new category)</div>
            <div class="fs-4 fw-semibold text-primary">KES {{ number_format($afterTotal, 2) }}</div>
          </div>
        </div>
      </div>
    </div>

    <div class="settings-card mb-3">
      <div class="card-header">
        <h5 class="mb-0">Invoice Diff (current term)</h5>
      </div>
      <div class="card-body table-responsive">
        <table class="table table-modern align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Votehead</th>
              <th class="text-end">Before (KES)</th>
              <th class="text-end">After (KES)</th>
              <th class="text-end">Change</th>
            </tr>
          </thead>
          <tbody>
            @forelse($diffs as $diff)
              @php
                $old = $diff['old_amount'] ?? 0;
                $new = $diff['new_amount'] ?? 0;
                $delta = $new - $old;
              @endphp
              <tr>
                <td>{{ \App\Models\Votehead::find($diff['votehead_id'])->name ?? 'Votehead' }}</td>
                <td class="text-end">{{ number_format($old, 2) }}</td>
                <td class="text-end">{{ number_format($new, 2) }}</td>
                <td class="text-end @if($delta>0) text-danger @elseif($delta<0) text-success @endif">
                  {{ $delta === 0 ? 'No change' : number_format($delta, 2) }}
                </td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-muted text-center py-3">No changes detected.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if($discountWarning)
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Discounts applied to this student will be cleared when you confirm. Payments remain untouched.
      </div>
    @endif

    <div class="d-flex justify-content-end gap-2">
      <a href="{{ url()->previous() ?: route('students.index') }}" class="btn btn-outline-secondary">Cancel</a>
      <form action="{{ route('students.update', $student->id) }}" method="POST">
        @csrf
        @method('PUT')
        @foreach($payload as $key => $value)
          @if(is_array($value))
            @foreach($value as $subKey => $subVal)
              <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subVal }}">
            @endforeach
          @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
          @endif
        @endforeach
        <button type="submit" class="btn btn-settings-primary">
          <i class="bi bi-check-circle"></i> Confirm & Update Invoice
        </button>
      </form>
    </div>
  </div>
</div>
@endsection

