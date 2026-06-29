@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
      'title' => 'Uploaded Statements',
      'icon' => 'bi bi-file-earmark-spreadsheet',
      'subtitle' => 'Each uploaded statement and its own transactions. For cross-statement grouping use All Transactions.',
      'actions' => '<a href="' . route('finance.statement-transactions.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-collection"></i> All Transactions</a> <a href="' . route('finance.expense-statements.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-upload"></i> Upload Statement</a>'
    ])

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="finance-table-wrapper">
      <table class="finance-table">
        <thead>
          <tr>
            <th>Uploaded</th>
            <th>Source</th>
            <th>Account</th>
            <th>Period</th>
            <th>Outgoing</th>
            <th>Confirmed Expenses</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        @forelse($imports as $import)
          <tr>
            <td>{{ $import->created_at->format('Y-m-d H:i') }}</td>
            <td>{{ strtoupper($import->source) }}</td>
            <td>
              <div>{{ $import->account_name ?? '—' }}</div>
              <small class="text-muted">{{ $import->account_number }}</small>
            </td>
            <td>
              @if($import->period_start && $import->period_end)
                {{ $import->period_start->format('Y-m-d') }} → {{ $import->period_end->format('Y-m-d') }}
              @else
                —
              @endif
            </td>
            <td>KES {{ number_format((float)$import->outgoing_total, 2) }} <small class="text-muted">({{ $import->outgoing_count }})</small></td>
            <td>KES {{ number_format((float)($confirmedTotals[$import->id] ?? 0), 2) }}</td>
            <td>{{ ucfirst($import->status) }}</td>
            <td class="text-nowrap">
              <a href="{{ route('finance.expense-statements.show', $import) }}" class="btn btn-sm btn-info">Review</a>
              <form method="POST" action="{{ route('finance.expense-statements.destroy', $import) }}" class="d-inline"
                    onsubmit="return confirm('Delete this statement and all its transactions? This cannot be undone. (Blocked if any transactions are already confirmed/recorded.)');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="8" class="text-center py-4">No statements uploaded yet.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-3">{{ $imports->links() }}</div>
  </div>
</div>
@endsection
