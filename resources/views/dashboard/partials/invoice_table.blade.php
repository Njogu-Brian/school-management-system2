<div class="card shadow-sm h-100">
  <div class="card-header bg-white d-flex justify-content-between">
    <strong>Invoices – Due / Overdue</strong>
    <a class="small" href="{{ route('finance.invoices.index') }}">View all</a>
  </div>
  <div class="table-responsive">
    <table class="table table-sm mb-0">
      <thead><tr><th>Invoice #</th><th>Student</th><th>Due</th><th>Status</th><th></th></tr></thead>
      <tbody>
        @forelse($invoices as $inv)
          <tr>
            <td>{{ $inv->number }}</td>
            <td>{{ $inv->student_name }}</td>
            <td>{{ number_format($inv->balance, 2) }}</td>
            <td>
              <span class="badge bg-{{ !empty($inv->is_overdue) ? 'danger' : 'warning' }}">
                {{ !empty($inv->is_overdue) ? 'Overdue' : 'Due' }}
              </span>
            </td>
            <td><a href="{{ route('finance.invoices.show',$inv->id) }}" class="btn btn-outline-primary btn-sm">Open</a></td>
          </tr>
        @empty
          <tr><td colspan="5" class="text-muted">No due invoices.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
