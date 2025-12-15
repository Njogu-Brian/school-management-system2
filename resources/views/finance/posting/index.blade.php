@extends('layouts.app')

@section('content')
<div class="container">
  <h3 class="mb-3">Post Pending Fees</h3>

  @includeIf('finance.invoices.partials.alerts')

  <form method="POST" action="{{ route('finance.posting.preview') }}" class="row g-3">
    @csrf

    <div class="col-md-2">
      <label class="form-label">Academic Year</label>
      <input type="number" name="year" class="form-control" value="{{ request('year', $currentYear ?? $defaultYear ?? now()->year) }}" required>
    </div>

    <div class="col-md-2">
      <label class="form-label">Term</label>
      <select name="term" class="form-select" required>
        @for($i=1;$i<=3;$i++)
          <option value="{{ $i }}" {{ (request('term', $currentTermNumber ?? $defaultTerm ?? 1) == $i) ? 'selected' : '' }}>Term {{ $i }}</option>
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

  @if(isset($runs) && $runs->count() > 0)
  <hr class="my-4">
  <h5 class="mb-3">Previous Posting Runs</h5>
  <div class="table-responsive">
    <table class="table table-bordered table-hover">
      <thead class="table-light">
        <tr>
          <th>Run ID</th>
          <th>Academic Year</th>
          <th>Term</th>
          <th>Status</th>
          <th>Items Posted</th>
          <th>Posted By</th>
          <th>Posted At</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($runs as $run)
        <tr>
          <td>#{{ $run->id }}</td>
          <td>{{ $run->academicYear->year ?? 'N/A' }}</td>
          <td>{{ $run->term->name ?? 'N/A' }}</td>
          <td>
            <span class="badge bg-{{ $run->status === 'completed' ? 'success' : ($run->status === 'reversed' ? 'danger' : 'warning') }}">
              {{ ucfirst($run->status) }}
            </span>
          </td>
          <td>{{ $run->items_posted_count ?? 0 }}</td>
          <td>{{ $run->postedBy->name ?? 'System' }}</td>
          <td>{{ $run->posted_at ? $run->posted_at->format('d M Y, H:i') : 'N/A' }}</td>
          <td>
            <a href="{{ route('finance.posting.show', $run) }}" class="btn btn-sm btn-info">
              <i class="bi bi-eye"></i> View
            </a>
            @if($run->is_active && $run->canBeReversed())
            <form action="{{ route('finance.posting.reverse', $run) }}" method="POST" class="d-inline" 
                  onsubmit="return confirm('Are you sure you want to reverse this posting run? This will remove all invoice items created by this run.');">
              @csrf
              <button type="submit" class="btn btn-sm btn-danger">
                <i class="bi bi-arrow-counterclockwise"></i> Reverse
              </button>
            </form>
            @elseif($run->is_active)
            <span class="badge bg-success">Active</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    {{ $runs->links() }}
  </div>
  @endif
</div>
@endsection
