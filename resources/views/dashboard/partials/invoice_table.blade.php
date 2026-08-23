<div class="dash-card card h-100">
  <div class="card-header d-flex justify-content-between align-items-center">
    <strong>Invoices – Due / Overdue</strong>
    @if(Route::has('finance.invoices.index'))
      <a class="small dash-btn-ghost" href="{{ route('finance.invoices.index') }}">View all</a>
    @endif
  </div>
  <div class="table-responsive erp-invoice-table">
    <table class="table table-sm mb-0 dash-table">
      <thead>
        <tr>
          <th>Invoice #</th>
          <th>Student</th>
          <th>Class</th>
          <th class="text-end">Balance</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse($invoices as $inv)
          <tr>
            <td>{{ $inv->number ?: ('#'.$inv->id) }}</td>
            <td>
              @if(!empty($inv->student_id) && Route::has('students.show'))
                <a href="{{ route('students.show', $inv->student_id) }}">{{ $inv->student_name }}</a>
              @else
                {{ $inv->student_name }}
              @endif
            </td>
            <td>{{ $inv->classroom ?? '—' }}</td>
            <td class="text-end">{{ format_money($inv->balance) }}</td>
            <td>
              <span class="erp-status {{ $inv->status_label ?? (!empty($inv->is_overdue) ? 'overdue' : 'due') }}">
                {{ ucfirst(str_replace('_', ' ', $inv->status_label ?? (!empty($inv->is_overdue) ? 'Overdue' : 'Due'))) }}
              </span>
            </td>
            <td><a href="{{ route('finance.invoices.show',$inv->id) }}" class="btn btn-outline-primary btn-sm">Open</a></td>
          </tr>
        @empty
          <tr><td colspan="6" class="text-muted">No unpaid invoices for this period.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="erp-invoice-cards">
    @forelse($invoices as $inv)
      <div class="erp-invoice-card">
        <div class="d-flex justify-content-between gap-2">
          <div class="fw-semibold">{{ $inv->number ?: ('Invoice #'.$inv->id) }}</div>
          <span class="erp-status {{ $inv->status_label ?? (!empty($inv->is_overdue) ? 'overdue' : 'due') }}">
            {{ ucfirst($inv->status_label ?? (!empty($inv->is_overdue) ? 'Overdue' : 'Due')) }}
          </span>
        </div>
        <div class="small dash-muted mt-1">{{ $inv->student_name }} · {{ $inv->classroom ?? '—' }}</div>
        <div class="fw-semibold mt-2">{{ format_money($inv->balance) }}</div>
        <a href="{{ route('finance.invoices.show',$inv->id) }}" class="btn btn-primary btn-sm mt-2">Open Invoice</a>
      </div>
    @empty
      <div class="erp-empty"><i class="bi bi-receipt"></i>No unpaid invoices for this period.</div>
    @endforelse
  </div>
</div>
