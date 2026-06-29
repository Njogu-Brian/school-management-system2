@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
      'title' => 'All Transactions',
      'icon' => 'bi bi-collection',
      'subtitle' => 'Every parsed statement combined — group and classify each recipient once across all statements',
      'actions' => '<a href="' . route('finance.expense-statements.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-folder2-open"></i> Uploaded Statements</a> <a href="' . route('finance.expense-statements.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-upload"></i> Upload Statement</a>'
    ])

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="finance-card p-3">
          <div class="text-muted small">Outgoing Total</div>
          <div class="fs-5 fw-semibold">KES {{ number_format((float)$stats['outgoing_total'], 2) }}</div>
          <div class="small text-muted">{{ $stats['import_count'] }} statement(s)</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="finance-card p-3">
          <div class="text-muted small">Confirmed Business</div>
          <div class="fs-5 fw-semibold text-success">KES {{ number_format((float)$stats['confirmed_total'], 2) }}</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="finance-card p-3">
          <div class="text-muted small">Pending Review</div>
          <div class="fs-5 fw-semibold text-warning">KES {{ number_format((float)$stats['pending_outgoing'], 2) }}</div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="finance-card p-3 d-flex flex-column justify-content-between h-100">
          <div class="text-muted small">Ready to submit</div>
          <div class="fs-6 fw-semibold">{{ $pendingExpenseCreation }} txn(s)</div>
          @if($pendingExpenseCreation > 0)
            <form method="POST" action="{{ route('finance.statement-transactions.submit-expenses') }}" class="mt-1"
                  onsubmit="return confirm('Create {{ $pendingExpenseCreation }} expense(s) from all confirmed transactions? You can then approve them in each statement or in Expenses.');">
              @csrf
              <button type="submit" class="btn btn-sm btn-finance btn-finance-primary w-100"><i class="bi bi-journal-arrow-up"></i> Submit all confirmed</button>
            </form>
          @endif
        </div>
      </div>
    </div>

    @include('finance.expense-statements._grouping', [
      'groups' => $groups,
      'categoryGroups' => $categoryGroups,
      'vendorNames' => $vendorNames,
      'filter' => $filter,
      'search' => $search,
      'showStatement' => true,
      'highlightGroup' => $highlightGroup ?? null,
      'perPage' => $perPage,
      'routes' => [
        'group' => route('finance.statement-transactions.groups.update'),
        'line' => route('finance.statement-transactions.lines.update'),
        'bulk' => route('finance.statement-transactions.bulk-update-groups'),
      ],
      'searchAction' => route('finance.statement-transactions.index'),
      'clearUrl' => route('finance.statement-transactions.index', array_filter(['filter' => $filter, 'per_page' => $perPage != 20 ? $perPage : null])),
      'filterUrl' => function ($f) use ($search, $perPage) {
          $params = [];
          if ($f) { $params['filter'] = $f; }
          if ($search !== '') { $params['search'] = $search; }
          if ($perPage != 20) { $params['per_page'] = $perPage; }
          return route('finance.statement-transactions.index', $params);
      },
    ])
  </div>
</div>
@endsection
