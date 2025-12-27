@extends('layouts.app')

@section('content')
  @include('finance.partials.header', [
      'title' => 'Post Pending Fees',
      'icon' => 'bi bi-arrow-right-circle',
      'subtitle' => 'Move pending fee structures to active invoices'
  ])

  @includeIf('finance.invoices.partials.alerts')

  <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
    <div class="finance-card-header d-flex align-items-center gap-2">
      <i class="bi bi-funnel"></i> <span>Select Criteria</span>
    </div>
    <div class="finance-card-body p-4">
      <form method="POST" action="{{ route('finance.posting.preview') }}" class="row g-3">
        @csrf

        <div class="col-md-6 col-lg-2">
          <label class="finance-form-label">Academic Year</label>
          <input type="number" name="year" class="finance-form-control" value="{{ request('year', $currentYear ?? $defaultYear ?? now()->year) }}" required>
        </div>

        <div class="col-md-6 col-lg-2">
          <label class="finance-form-label">Term</label>
          <select name="term" class="finance-form-select" required>
            @for($i=1;$i<=3;$i++)
              <option value="{{ $i }}" {{ (request('term', $currentTermNumber ?? $defaultTerm ?? 1) == $i) ? 'selected' : '' }}>Term {{ $i }}</option>
            @endfor
          </select>
        </div>

        <div class="col-md-6 col-lg-3">
          <label class="finance-form-label">Votehead (optional)</label>
          <select name="votehead_id" class="finance-form-select">
            <option value="">All</option>
            @foreach($voteheads as $vh)
              <option value="{{ $vh->id }}">{{ $vh->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-6 col-lg-3">
          <label class="finance-form-label">Class (optional)</label>
          <select name="class_id" class="finance-form-select">
            <option value="">All</option>
            @foreach($classrooms as $c)
              <option value="{{ $c->id }}">{{ $c->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-6 col-lg-3">
          <label class="finance-form-label">Student Category (optional)</label>
          <select name="student_category_id" class="finance-form-select">
            <option value="">All</option>
            @foreach($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-6 col-lg-2">
          <label class="finance-form-label">Stream (optional)</label>
          <select name="stream_id" class="finance-form-select">
            <option value="">All</option>
            @foreach($streams as $s)
              <option value="{{ $s->id }}">{{ $s->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="col-12 d-flex gap-2 flex-wrap">
          <button type="submit" class="btn btn-finance btn-finance-primary">
            <i class="bi bi-search"></i> Preview
          </button>
        </div>
      </form>
    </div>
  </div>

  @if(isset($runs) && $runs->count() > 0)
  <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
    <div class="finance-card-header secondary d-flex align-items-center gap-2">
      <i class="bi bi-clock-history"></i> <span>Previous Posting Runs</span>
    </div>
    <div class="finance-table-wrapper">
      <div class="table-responsive px-3 pb-3">
        <table class="finance-table align-middle">
          <thead>
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
            <span class="finance-badge badge-{{ $run->status === 'completed' ? 'approved' : ($run->status === 'reversed' ? 'rejected' : 'pending') }}">
              {{ ucfirst($run->status) }}
            </span>
          </td>
          <td>{{ $run->items_posted_count ?? 0 }}</td>
          <td>{{ $run->postedBy->name ?? 'System' }}</td>
          <td>{{ $run->posted_at ? $run->posted_at->format('d M Y, H:i') : 'N/A' }}</td>
          <td>
            <div class="finance-action-buttons">
              <a href="{{ route('finance.posting.show', $run) }}" class="btn btn-sm btn-info">
                <i class="bi bi-eye"></i> View
              </a>
              @if($run->canBeReversed())
              <form action="{{ route('finance.posting.reverse', $run) }}" method="POST" class="d-inline" 
                    onsubmit="return confirm('Are you sure you want to reverse this posting run? This will remove all invoice items created by this run. If items have payment allocations, reversal will be blocked.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-danger">
                  <i class="bi bi-arrow-counterclockwise"></i> Reverse
                </button>
              </form>
              @endif
            </div>
          </td>
        </tr>
        @endforeach
          </tbody>
        </table>
      </div>
      <div class="finance-card-body" style="padding-top: 1rem; border-top: 1px solid #e5e7eb;">
        {{ $runs->links() }}
      </div>
    </div>
  </div>
  @endif
@endsection
