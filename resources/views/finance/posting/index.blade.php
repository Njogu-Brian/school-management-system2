@extends('layouts.app')

@section('content')
<div class="container">
  <h3 class="mb-3">Post Pending Fees</h3>

  @includeIf('finance.invoices.partials.alerts')

  <form method="POST" action="{{ route('finance.posting.preview') }}" class="row g-3">
    @csrf

    <div class="col-md-2">
      <label class="form-label">Academic Year</label>
      <input type="number" name="year" class="form-control" value="{{ request('year', now()->year) }}" required>
    </div>

    <div class="col-md-2">
      <label class="form-label">Term</label>
      <select name="term" class="form-select" required>
        @for($i=1;$i<=3;$i++)
          <option value="{{ $i }}">Term {{ $i }}</option>
        @endfor
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Votehead (optional)</label>
      <select name="votehead_id" class="form-select">
        <option value="">All</option>
        @foreach($voteheads as $vh)
          <option value="{{ $vh->id }}">{{ $vh->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-3">
      <label class="form-label">Class (optional)</label>
      <select name="class_id" class="form-select">
        <option value="">All</option>
        @foreach($classrooms as $c)
          <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-md-2">
      <label class="form-label">Stream (optional)</label>
      <select name="stream_id" class="form-select">
        <option value="">All</option>
        @foreach($streams as $s)
          <option value="{{ $s->id }}">{{ $s->name }}</option>
        @endforeach
      </select>
    </div>

    <div class="col-12">
      <hr>
      <button class="btn btn-primary">
        <i class="bi bi-search"></i> Preview
      </button>
    </div>
  </form>
</div>
@endsection
