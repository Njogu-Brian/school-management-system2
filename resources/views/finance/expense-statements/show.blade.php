@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
      'title' => 'Review Statement',
      'icon' => 'bi bi-list-check',
      'subtitle' => ($expenseStatement->account_name ?? 'M-Pesa') . ' · ' . ($expenseStatement->original_filename ?? ''),
      'actions' => '<a href="' . route('finance.statement-transactions.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-collection"></i> All Transactions</a> <a href="' . route('finance.expense-statements.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> All Imports</a>'
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
        <div class="finance-card p-3">
          <div class="text-muted small">Recorded as Expenses</div>
          <div class="fs-6 fw-semibold">{{ $draftStats['converted_count'] }} txn(s)</div>
          <a href="{{ route('finance.expenses.index') }}" class="small">View in Expenses →</a>
        </div>
      </div>
    </div>

    <div class="finance-card mb-3">
      <div class="finance-card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <h6 class="mb-1"><i class="bi bi-journal-check"></i> Expenses from this statement</h6>
            <div class="small text-muted">
              Mark transactions as <strong>Business expense</strong> with a category, then <strong>Submit</strong> to create the expenses.
              Approve them (individually or by category) to post them to the general ledger. Charges are booked to Bank &amp; Transaction Charges automatically.
            </div>
          </div>
          @if($pendingExpenseCreation > 0)
            <form method="POST" action="{{ route('finance.expense-statements.submit-expenses', $expenseStatement) }}">
              @csrf
              <button type="submit" class="btn btn-finance btn-finance-primary">
                <i class="bi bi-journal-arrow-up"></i> Submit {{ $pendingExpenseCreation }} confirmed
              </button>
            </form>
          @endif
        </div>

        @if($expenseGroups->isNotEmpty())
          @php
            $totalSubmitted = $expenseGroups->sum('submitted_count');
            $totalPosted = $expenseGroups->sum('posted_count');
          @endphp
          <div class="d-flex justify-content-end gap-2 mt-2">
            @if($totalSubmitted > 0)
              <form method="POST" action="{{ route('finance.expense-statements.approve-expenses', $expenseStatement) }}"
                    onsubmit="return confirm('Approve all {{ $totalSubmitted }} submitted expense(s) and post them to the ledger?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-all"></i> Approve all submitted ({{ $totalSubmitted }})</button>
              </form>
            @endif
            @if($totalPosted > 0)
              <form method="POST" action="{{ route('finance.expense-statements.reverse-expense', $expenseStatement) }}"
                    onsubmit="return confirm('Reverse ALL {{ $totalPosted }} posted expense(s)? Contra journal entries will be posted and every transaction returned to Uncategorized.');">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-arrow-counterclockwise"></i> Reverse all posted ({{ $totalPosted }})</button>
              </form>
            @endif
          </div>

          <div class="table-responsive mt-2">
            <table class="table table-sm align-middle mb-0">
              <thead>
                <tr>
                  <th>Category</th>
                  <th class="text-end">Expenses</th>
                  <th class="text-end">Total</th>
                  <th>Status</th>
                  <th class="text-end">Action</th>
                </tr>
              </thead>
              <tbody>
                @foreach($expenseGroups as $eg)
                  <tr>
                    <td>
                      <button class="btn btn-sm btn-link px-0" type="button" data-bs-toggle="collapse" data-bs-target="#exp-cat-{{ $loop->index }}">
                        <strong>{{ $eg->category_name }}</strong>
                      </button>
                    </td>
                    <td class="text-end">{{ $eg->count }}</td>
                    <td class="text-end">KES {{ number_format($eg->total, 2) }}</td>
                    <td>
                      @if($eg->submitted_count > 0)
                        <span class="badge bg-warning text-dark">{{ $eg->submitted_count }} awaiting approval</span>
                      @else
                        <span class="badge bg-success">All approved</span>
                      @endif
                    </td>
                    <td class="text-end">
                      <div class="d-flex gap-1 justify-content-end flex-wrap">
                        @if($eg->submitted_count > 0 && $eg->category_id)
                          <form method="POST" action="{{ route('finance.expense-statements.approve-expenses', $expenseStatement) }}"
                                onsubmit="return confirm('Approve all {{ $eg->submitted_count }} submitted {{ $eg->category_name }} expense(s)?');">
                            @csrf
                            <input type="hidden" name="category_id" value="{{ $eg->category_id }}">
                            <button type="submit" class="btn btn-sm btn-success">Approve all {{ $eg->category_name }}</button>
                          </form>
                        @endif
                        @if($eg->posted_count > 0 && $eg->category_id)
                          <form method="POST" action="{{ route('finance.expense-statements.reverse-expense', $expenseStatement) }}"
                                onsubmit="return confirm('Reverse all {{ $eg->posted_count }} posted {{ $eg->category_name }} expense(s)? Contra journal entries will be posted and the transactions returned to Uncategorized.');">
                            @csrf
                            <input type="hidden" name="category_id" value="{{ $eg->category_id }}">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Reverse all {{ $eg->category_name }}</button>
                          </form>
                        @endif
                      </div>
                    </td>
                  </tr>
                  <tr class="collapse" id="exp-cat-{{ $loop->index }}">
                    <td colspan="5" class="p-0">
                      <table class="table table-sm mb-0">
                        <tbody>
                          @foreach($eg->expenses as $exp)
                            @php
                              $st = $exp->status;
                              $posted = in_array($st, ['approved', 'paid'], true);
                              $primaryLine = $exp->lines->first(fn ($l) => optional($l->category)->code !== 'TXN_COST') ?? $exp->lines->first();
                            @endphp
                            <tr>
                              <td class="ps-4">
                                <a href="{{ route('finance.expenses.show', $exp) }}">{{ $exp->expense_no }}</a>
                                <span class="text-muted small ms-2">{{ optional($exp->expense_date)->format('Y-m-d') }}</span>
                                <div class="small">
                                  <i class="bi bi-shop"></i>
                                  @if($exp->vendor)
                                    {{ $exp->vendor->name }}
                                  @else
                                    <span class="text-warning">No vendor</span>
                                  @endif
                                </div>
                              </td>
                              <td class="text-end">KES {{ number_format((float) $exp->total, 2) }}</td>
                              <td>
                                <span class="badge bg-{{ $st === 'paid' ? 'success' : ($st === 'approved' ? 'info text-dark' : ($st === 'submitted' ? 'warning text-dark' : 'secondary')) }}">{{ ucfirst($st) }}</span>
                              </td>
                              <td class="text-end" style="width: 220px">
                                <div class="d-flex gap-1 justify-content-end">
                                  <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#exp-edit-{{ $exp->id }}">Edit</button>
                                  @if($st === 'submitted')
                                    <form method="POST" action="{{ route('finance.expense-statements.approve-expenses', $expenseStatement) }}">
                                      @csrf
                                      <input type="hidden" name="expense_id" value="{{ $exp->id }}">
                                      <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('finance.expense-statements.reject-expense', $expenseStatement) }}"
                                          onsubmit="return confirm('Reject this expense? Its transaction(s) will go back to Uncategorized.');">
                                      @csrf
                                      <input type="hidden" name="expense_id" value="{{ $exp->id }}">
                                      <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                    </form>
                                  @elseif($posted)
                                    <form method="POST" action="{{ route('finance.expense-statements.reverse-expense', $expenseStatement) }}"
                                          onsubmit="return confirm('Reverse this posted expense? A contra journal entry will be posted and the transaction(s) returned to Uncategorized to be re-done.');">
                                      @csrf
                                      <input type="hidden" name="expense_id" value="{{ $exp->id }}">
                                      <button type="submit" class="btn btn-sm btn-outline-danger">Reverse</button>
                                    </form>
                                  @endif
                                </div>
                              </td>
                            </tr>
                            <tr class="collapse" id="exp-edit-{{ $exp->id }}">
                              <td colspan="4" class="bg-white">
                                <form method="POST" action="{{ route('finance.expense-statements.edit-expense', $expenseStatement) }}" class="row g-2 align-items-end py-2 px-2">
                                  @csrf
                                  <input type="hidden" name="expense_id" value="{{ $exp->id }}">
                                  <div class="col-md-3">
                                    <label class="form-label small mb-1">Vendor / payee</label>
                                    <input type="text" name="vendor_name" list="vendor-options" value="{{ optional($exp->vendor)->name }}" class="form-control form-control-sm" placeholder="Type or pick a vendor" autocomplete="off">
                                  </div>
                                  <div class="col-md-3">
                                    <label class="form-label small mb-1">Category{{ $posted ? ' (locked — posted)' : '' }}</label>
                                    @if($posted)
                                      <input type="text" class="form-control form-control-sm" value="{{ $eg->category_name }}" disabled>
                                    @else
                                      <select name="expense_category_id" class="form-select form-select-sm" data-category-select data-selected="{{ $eg->category_id }}">
                                        <option value="">— Keep current —</option>
                                      </select>
                                    @endif
                                  </div>
                                  <div class="col-md-4">
                                    <label class="form-label small mb-1">Description</label>
                                    <input type="text" name="expense_description" value="{{ optional($primaryLine)->description }}" class="form-control form-control-sm" placeholder="What was this for?">
                                  </div>
                                  <div class="col-md-2">
                                    <button type="submit" class="btn btn-sm btn-finance btn-finance-primary w-100">Save</button>
                                  </div>
                                </form>
                              </td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>

    @include('finance.expense-statements._grouping', [
      'groups' => $groups,
      'categoryGroups' => $categoryGroups,
      'vendorNames' => $vendorNames,
      'filter' => $filter,
      'search' => $search,
      'showStatement' => false,
      'routes' => [
        'group' => route('finance.expense-statements.groups.update', $expenseStatement),
        'line' => route('finance.expense-statements.lines.update', $expenseStatement),
        'bulk' => route('finance.expense-statements.bulk-update-groups', $expenseStatement),
      ],
      'searchAction' => route('finance.expense-statements.show', $expenseStatement),
      'clearUrl' => route('finance.expense-statements.show', array_filter([$expenseStatement->id, 'filter' => $filter])),
      'filterUrl' => function ($f) use ($expenseStatement, $search) {
          $params = [$expenseStatement->id];
          if ($f) { $params['filter'] = $f; }
          if ($search !== '') { $params['search'] = $search; }
          return route('finance.expense-statements.show', $params);
      },
    ])
  </div>
</div>
@endsection
