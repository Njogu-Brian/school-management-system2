@extends('layouts.app')

@section('content')
<div class="container">
  <h3 class="mb-3">Invoices</h3>

  @includeIf('finance.invoices.partials.alerts')

  <form method="GET" action="{{ route('finance.invoices.index') }}" class="row g-3 mb-3">
    <div class="col-md-2">
      <label class="form-label">Year</label>
      <input type="number" class="form-control" name="year" value="{{ request('year', now()->year) }}">
    </div>
    <div class="col-md-2">
      <label class="form-label">Term</label>
      <select name="term" class="form-select">
        <option value="">All</option>
        @for($i=1;$i<=3;$i++)
          <option value="{{ $i }}" {{ request('term') == $i ? 'selected':'' }}>Term {{ $i }}</option>
        @endfor
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Votehead</label>
      <select name="votehead_id" class="form-select">
        <option value="">All</option>
        @foreach($voteheads as $vh)
          <option value="{{ $vh->id }}" {{ request('votehead_id')==$vh->id ? 'selected':'' }}>{{ $vh->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label">Class</label>
      <select name="class_id" class="form-select">
        <option value="">All</option>
        @foreach($classrooms as $c)
          <option value="{{ $c->id }}" {{ request('class_id')==$c->id ? 'selected':'' }}>{{ $c->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Stream</label>
      <select name="stream_id" class="form-select">
        <option value="">All</option>
        @foreach($streams as $s)
          <option value="{{ $s->id }}" {{ request('stream_id')==$s->id ? 'selected':'' }}>{{ $s->name }}</option>
        @endforeach
      </select>
    </div>
    <div class="col-12">
      <button class="btn btn-primary">Filter</button>
      <a href="{{ route('finance.invoices.index') }}" class="btn btn-outline-secondary">Reset</a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>Invoice #</th>
          <th>Student</th>
          <th>Class/Stream</th>
          <th>Year/Term</th>
          <th class="text-end">Total</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($invoices as $inv)
          <tr>
            <td>{{ $loop->iteration + ($invoices->currentPage()-1)*$invoices->perPage() }}</td>
            <td>{{ $inv->invoice_number }}</td>
            <td>{{ $inv->student->full_name ?? 'Unknown' }}</td>
            <td>{{ $inv->student->classroom->name ?? '-' }} / {{ $inv->student->stream->name ?? '-' }}</td>
            <td>{{ $inv->year }} / T{{ $inv->term }}</td>
            <td class="text-end">{{ number_format($inv->total,2) }}</td>
            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('finance.invoices.show',$inv) }}">View</a></td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted">No invoices</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $invoices->links() }}
</div>
@endsection
