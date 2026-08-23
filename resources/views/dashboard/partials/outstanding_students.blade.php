<div class="dash-card card h-100">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Outstanding fees</strong>
    @if(Route::has('finance.fee-balances.index'))
      <a class="small dash-btn-ghost" href="{{ route('finance.fee-balances.index') }}">View all</a>
    @endif
  </div>
  <div class="table-responsive erp-invoice-table">
    <table class="table table-sm mb-0 dash-table">
      <thead>
        <tr>
          <th>Student</th>
          <th>Admission No</th>
          <th>Class</th>
          <th class="text-end">Invoice Total</th>
          <th class="text-end">Paid</th>
          <th class="text-end">Balance</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($outstandingStudents ?? [] as $row)
          <tr>
            <td>{{ $row->student_name }}</td>
            <td>{{ $row->admission_number ?: '—' }}</td>
            <td>{{ trim(($row->classroom ?? '').' '.($row->stream ?? '')) ?: '—' }}</td>
            <td class="text-end">{{ format_money($row->invoiced) }}</td>
            <td class="text-end">{{ format_money($row->paid) }}</td>
            <td class="text-end">{{ format_money($row->balance) }}</td>
            <td><span class="erp-status {{ $row->status }}">{{ ucfirst($row->status) }}</span></td>
            <td>
              @if($row->statement_url)
                <a href="{{ $row->statement_url }}" class="btn btn-outline-primary btn-sm">Statement</a>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-muted">No outstanding student balances for this period.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="erp-invoice-cards">
    @forelse($outstandingStudents ?? [] as $row)
      <div class="erp-invoice-card">
        <div class="fw-semibold">{{ $row->student_name }}</div>
        <div class="small dash-muted">{{ $row->admission_number ?: '—' }} · {{ trim(($row->classroom ?? '').' '.($row->stream ?? '')) ?: '—' }}</div>
        <div class="erp-kv mt-2"><span>Invoice</span><strong>{{ format_money($row->invoiced) }}</strong></div>
        <div class="erp-kv"><span>Paid</span><strong>{{ format_money($row->paid) }}</strong></div>
        <div class="erp-kv"><span>Balance</span><strong>{{ format_money($row->balance) }}</strong></div>
        <div class="mt-2"><span class="erp-status {{ $row->status }}">{{ ucfirst($row->status) }}</span></div>
        @if($row->statement_url)
          <a href="{{ $row->statement_url }}" class="btn btn-primary btn-sm mt-2 w-100">View Statement</a>
        @endif
      </div>
    @empty
      <div class="erp-empty"><i class="bi bi-wallet2"></i>No outstanding student balances for this period.</div>
    @endforelse
  </div>
</div>
