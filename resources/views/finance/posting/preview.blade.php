@extends('layouts.app')

@section('content')
<div class="container">
  <h3 class="mb-3">Preview Posting</h3>

  @includeIf('finance.invoices.partials.alerts')

  @if($rows->isEmpty())
    <div class="alert alert-info">No items found for the selected filters.</div>
    <a href="{{ route('finance.posting.index') }}" class="btn btn-secondary">Back</a>
  @else
    <form method="POST" action="{{ route('finance.posting.commit') }}">
      @csrf
      <input type="hidden" name="year" value="{{ $filters['year'] }}">
      <input type="hidden" name="term" value="{{ $filters['term'] }}">

      <div class="row g-3 mb-3">
        <div class="col-md-3">
          <label class="form-label">Activate now?</label>
          <select name="activate_now" class="form-select">
            <option value="1" selected>Yes (Active)</option>
            <option value="0">No (Pending)</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Effective date (for pending)</label>
          <input type="date" name="effective_date" class="form-control" value="{{ $filters['effective_date'] ?? '' }}">
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Student ID</th>
              <th>Votehead ID</th>
              <th>Amount</th>
              <th>Origin</th>
            </tr>
          </thead>
          <tbody>
            @foreach($rows as $i => $r)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $r['student_id'] }}</td>
                <td>{{ $r['votehead_id'] }}</td>
                <td>{{ number_format($r['amount'], 2) }}</td>
                <td><span class="badge bg-secondary">{{ $r['origin'] }}</span></td>
              </tr>

              {{-- payload --}}
              <input type="hidden" name="payload[{{ $i }}][student_id]" value="{{ $r['student_id'] }}">
              <input type="hidden" name="payload[{{ $i }}][votehead_id]" value="{{ $r['votehead_id'] }}">
              <input type="hidden" name="payload[{{ $i }}][amount]" value="{{ $r['amount'] }}">
              <input type="hidden" name="payload[{{ $i }}][origin]" value="{{ $r['origin'] }}">
            @endforeach
          </tbody>
        </table>
      </div>

      <button class="btn btn-success">
        <i class="bi bi-cloud-upload"></i> Post {{ $rows->count() }} item(s)
      </button>
      <a href="{{ route('finance.posting.index') }}" class="btn btn-secondary">Back</a>
    </form>
  @endif
</div>
@endsection
