@extends('layouts.app')

@section('content')
  @include('finance.partials.header', [
      'title' => 'Transport Fee Import Preview',
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
        <strong>{{ number_format($total, 2) }}</strong>. Review the rows below and resolve any drop-off point mappings.
      </p>

      <form method="POST" action="{{ route('finance.transport-fees.import.commit') }}">
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
                  <th class="text-end">Amount</th>
                  <th>Drop-off point</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                @foreach($preview as $row)
                  @php $isOk = $row['status'] === 'ok'; @endphp
                  <tr class="{{ $isOk ? '' : 'table-warning' }}">
                    <td>{{ $row['student_name'] ?? '—' }}</td>
                    <td>{{ $row['admission_number'] ?? '—' }}</td>
                    <td class="text-end">{{ $row['amount'] !== null ? number_format($row['amount'], 2) : '—' }}</td>
                    <td>{{ $row['drop_off_point_name'] ?? ($row['drop_off_point_id'] ? $dropOffPoints->firstWhere('id', $row['drop_off_point_id'])->name : '—') }}</td>
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

        @if($missingDropOffs->count())
          <div class="alert alert-warning">
            <div class="fw-semibold mb-2">New drop-off points detected</div>
            <p class="small mb-2">Choose whether to create a new drop-off point or map to an existing one.</p>
            @foreach($missingDropOffs as $name)
              <div class="mb-2">
                <label class="form-label small mb-1">{{ $name }}</label>
                <select name="dropoff_map[{{ \Illuminate\Support\Str::lower($name) }}]" class="form-select">
                  <option value="create">Create &amp; use "{{ $name }}"</option>
                  @foreach($dropOffPoints as $point)
                    <option value="{{ $point->id }}">{{ $point->name }}</option>
                  @endforeach
                </select>
              </div>
            @endforeach
          </div>
        @endif

        <div class="d-flex gap-3">
          <a href="{{ route('finance.transport-fees.index') }}" class="btn btn-outline-secondary">
            Cancel
          </a>
          <button type="submit" class="btn btn-finance btn-finance-primary">
            <i class="bi bi-check2-circle"></i> Apply to invoices
          </button>
        </div>
      </form>
    </div>
  </div>
@endsection

