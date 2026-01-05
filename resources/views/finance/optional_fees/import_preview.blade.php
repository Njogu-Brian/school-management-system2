@extends('layouts.app')

@section('content')
  @include('finance.partials.header', [
      'title' => 'Optional Fee Import Preview',
      'icon' => 'bi bi-upload',
      'subtitle' => 'Validate rows before applying to invoices'
  ])

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show finance-animate" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  <div class="finance-card finance-animate shadow-sm rounded-4 border-0">
    <div class="finance-card-header d-flex align-items-center gap-2">
      <i class="bi bi-list-check"></i>
      <span>Preview</span>
    </div>
    <div class="finance-card-body p-4">
      <p class="text-muted mb-3">
        Year {{ $year }}, Term {{ $term }} — Total upload amount:
        <strong>{{ number_format($totalAmount, 2) }}</strong>. Review the rows below.
      </p>

      @if($missingVoteheads->count())
        <div class="alert alert-danger mb-3">
          <div class="fw-semibold mb-2">Invalid voteheads detected</div>
          <p class="small mb-2">The following voteheads were not found or are not optional:</p>
          <ul class="mb-0">
            @foreach($missingVoteheads as $name)
              <li>{{ $name }}</li>
            @endforeach
          </ul>
        </div>
      @endif

      <form method="POST" action="{{ route('finance.optional-fees.import.commit') }}">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="term" value="{{ $term }}">

        <div class="finance-table-wrapper mb-3">
          <div class="table-responsive">
            <table class="finance-table align-middle">
              <thead>
                <tr>
                  <th>Student</th>
                  <th>Admission #</th>
                  <th>Votehead Name</th>
                  <th class="text-end">Amount</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($preview as $row)
                  @php $isOk = $row['status'] === 'ok'; @endphp
                  <tr class="{{ $isOk ? '' : 'table-warning' }}">
                    <td>{{ $row['student_name'] ?? '—' }}</td>
                    <td>{{ $row['admission_number'] ?? '—' }}</td>
                    <td>{{ $row['votehead_name'] ?? '—' }}</td>
                    <td class="text-end">{{ $row['amount'] !== null ? number_format($row['amount'], 2) : '—' }}</td>
                    <td>
                      @if($isOk)
                        <span class="badge bg-success">Ready</span>
                      @else
                        <span class="badge bg-warning text-dark">{{ $row['message'] ?? 'Needs attention' }}</span>
                      @endif
                    </td>
                  </tr>
                  <input type="hidden" name="rows[]" value="{{ base64_encode(json_encode($row)) }}">
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        <div class="d-flex gap-3">
          <a href="{{ route('finance.optional_fees.index') }}" class="btn btn-outline-secondary">
            Cancel
          </a>
          <button type="submit" class="btn btn-finance btn-finance-primary" @if($missingVoteheads->count()) disabled @endif>
            <i class="bi bi-check2-circle"></i> Apply to invoices
          </button>
        </div>
      </form>
    </div>
  </div>
@endsection

