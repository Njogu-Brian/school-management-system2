@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
      'title' => 'Review Statement',
      'icon' => 'bi bi-list-check',
      'subtitle' => ($expenseStatement->account_name ?? 'M-Pesa') . ' · ' . ($expenseStatement->original_filename ?? ''),
      'actions' => '<a href="' . route('finance.expense-statements.index') . '" class="btn btn-finance btn-finance-secondary"><i class="bi bi-arrow-left"></i> All Imports</a>'
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
          <div class="text-muted small">Ready for Expense Drafts</div>
          <div class="fs-6 fw-semibold">{{ $draftStats['unconverted_count'] }} txn(s)</div>
          <div class="small text-muted">KES {{ number_format((float)$draftStats['unconverted_total'], 2) }}</div>
        </div>
      </div>
    </div>

    @if($draftStats['unconverted_count'] > 0)
      <div class="finance-card mb-3">
        <div class="finance-card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div>
            <strong>{{ $draftStats['unconverted_count'] }}</strong> confirmed business transaction(s) can be converted to expense drafts.
            @if($draftStats['converted_count'] > 0)
              <span class="text-muted">({{ $draftStats['converted_count'] }} already converted)</span>
            @endif
          </div>
          <form method="POST" action="{{ route('finance.expense-statements.generate-expenses', $expenseStatement) }}" onsubmit="return confirm('Create expense drafts from all confirmed business transactions?');">
            @csrf
            <button type="submit" class="btn btn-finance btn-finance-primary">
              <i class="bi bi-journal-plus"></i> Generate Expense Drafts
            </button>
          </form>
        </div>
      </div>
    @endif

    <div class="finance-card mb-3">
      <div class="finance-card-body d-flex flex-wrap gap-2">
        <a href="{{ route('finance.expense-statements.show', $expenseStatement) }}" class="btn btn-sm {{ !$filter ? 'btn-primary' : 'btn-outline-secondary' }}">All Outgoing</a>
        <a href="{{ route('finance.expense-statements.show', [$expenseStatement, 'filter' => 'pending']) }}" class="btn btn-sm {{ $filter === 'pending' ? 'btn-primary' : 'btn-outline-secondary' }}">Pending</a>
        <a href="{{ route('finance.expense-statements.show', [$expenseStatement, 'filter' => 'confirmed']) }}" class="btn btn-sm {{ $filter === 'confirmed' ? 'btn-primary' : 'btn-outline-secondary' }}">Confirmed</a>
        <a href="{{ route('finance.expense-statements.show', [$expenseStatement, 'filter' => 'fees']) }}" class="btn btn-sm {{ $filter === 'fees' ? 'btn-primary' : 'btn-outline-secondary' }}">Fees Only</a>
      </div>
    </div>

    @forelse($groups as $index => $group)
      <div class="finance-card mb-3">
        <div class="finance-card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">{{ $group->display_name }}</h5>
                <span class="badge bg-secondary">{{ $group->transaction_type_label }}</span>
                @if($group->review_status === 'confirmed_expense')
                  <span class="badge bg-success">Business Expense</span>
                @elseif($group->review_status === 'personal')
                  <span class="badge bg-light text-dark">Personal</span>
                @elseif($group->review_status === 'mixed')
                  <span class="badge bg-info text-dark">Mixed — review individually</span>
                @else
                  <span class="badge bg-warning text-dark">Pending</span>
                @endif
              </div>
              <div class="text-muted small mt-1">
                @if($group->recipient_phone)<span class="me-2"><i class="bi bi-phone"></i> {{ $group->recipient_phone }}</span>@endif
                @if($group->paybill_number)<span class="me-2"><i class="bi bi-building"></i> Paybill {{ $group->paybill_number }}</span>@endif
                @if($group->account_reference)<span class="me-2">Acc. {{ $group->account_reference }}</span>@endif
                <span>{{ $group->transaction_count }} transaction(s)</span>
                @if($group->fee_amount > 0)<span class="ms-2">incl. KES {{ number_format($group->fee_amount, 2) }} fees</span>@endif
              </div>
            </div>
            <div class="text-end">
              <div class="fs-5 fw-semibold">KES {{ number_format($group->total_amount, 2) }}</div>
            </div>
          </div>

          <button class="btn btn-sm btn-link px-0 mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#group-{{ $index }}" aria-expanded="{{ $group->review_status === 'mixed' ? 'true' : 'false' }}">
            {{ $group->review_status === 'mixed' ? 'Hide' : 'Show' }} transactions
          </button>

          <div class="collapse {{ $group->review_status === 'mixed' ? 'show' : '' }} mt-2" id="group-{{ $index }}">
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-3">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Receipt</th>
                    <th>Details</th>
                    <th class="text-end">Amount</th>
                    <th>Status</th>
                    <th style="min-width: 420px">Individual classification</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($group->lines as $line)
                    <tr class="{{ $line->is_transaction_fee ? 'table-light' : '' }}">
                      <td class="text-nowrap">{{ optional($line->completed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                      <td><code>{{ $line->receipt_no }}</code></td>
                      <td>
                        {{ $line->narration }}
                        @if($line->is_transaction_fee)<span class="badge bg-light text-dark ms-1">Fee</span>@endif
                        @if($line->expense_id)
                          <div class="small mt-1"><a href="{{ route('finance.expenses.show', $line->expense_id) }}">Expense draft #{{ $line->expense_id }}</a></div>
                        @endif
                      </td>
                      <td class="text-end">{{ number_format((float)$line->withdrawn_amount, 2) }}</td>
                      <td>
                        @if($line->review_status === 'confirmed_expense')
                          <span class="badge bg-success">Business</span>
                        @elseif($line->review_status === 'personal')
                          <span class="badge bg-secondary">Personal</span>
                        @elseif($line->review_status === 'ignored')
                          <span class="badge bg-dark">Ignored</span>
                        @else
                          <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                      </td>
                      <td>
                        <form method="POST" action="{{ route('finance.expense-statements.lines.update', $expenseStatement) }}" class="row g-1">
                          @csrf
                          <input type="hidden" name="line_id" value="{{ $line->id }}">
                          <div class="col-12 col-xl-3">
                            <select name="review_status" class="form-select form-select-sm" required>
                              <option value="pending" @selected($line->review_status === 'pending')>Pending</option>
                              <option value="confirmed_expense" @selected($line->review_status === 'confirmed_expense')>Business</option>
                              <option value="personal" @selected($line->review_status === 'personal')>Personal</option>
                              <option value="ignored" @selected($line->review_status === 'ignored')>Ignore</option>
                            </select>
                          </div>
                          <div class="col-12 col-xl-3">
                            <select name="expense_category_id" class="form-select form-select-sm">
                              <option value="">Category</option>
                              @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((string)$line->expense_category_id === (string)$category->id)>{{ $category->name }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-12 col-xl-4">
                            <input type="text" name="expense_description" value="{{ $line->expense_description }}" class="form-control form-control-sm" placeholder="What was this for?">
                          </div>
                          <div class="col-12 col-xl-2">
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">Save</button>
                          </div>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          <form method="POST" action="{{ route('finance.expense-statements.groups.update', $expenseStatement) }}" class="border-top pt-3 mt-2">
            @csrf
            <input type="hidden" name="group_key" value="{{ $group->group_key }}">
            <div class="small text-muted mb-2 fw-semibold">Apply to entire group ({{ $group->transaction_count }} transactions)</div>
            <div class="row g-2 align-items-end">
              <div class="col-md-3">
                <label class="form-label small mb-1">Classification</label>
                <select name="review_status" class="finance-form-select form-select-sm" required>
                  <option value="pending" @selected($group->review_status === 'pending')>Pending review</option>
                  <option value="confirmed_expense" @selected($group->review_status === 'confirmed_expense')>Business expense</option>
                  <option value="personal" @selected($group->review_status === 'personal')>Personal (not expense)</option>
                  <option value="ignored" @selected($group->review_status === 'ignored')>Ignore</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small mb-1">Expense category</label>
                <select name="expense_category_id" class="finance-form-select form-select-sm">
                  <option value="">— Select —</option>
                  @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string)$group->expense_category_id === (string)$category->id)>{{ $category->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-1">What was this expense for?</label>
                <input type="text" name="expense_description" value="{{ $group->expense_description }}" class="finance-form-control form-control-sm" placeholder="e.g. Motor parts for school van">
              </div>
              <div class="col-md-2">
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" name="remember_choice" value="1" id="remember-{{ $index }}">
                  <label class="form-check-label small" for="remember-{{ $index }}">Remember</label>
                </div>
                <button type="submit" class="btn btn-sm btn-finance btn-finance-primary w-100">Apply to Group</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    @empty
      <div class="finance-card">
        <div class="finance-card-body text-center py-5 text-muted">
          No outgoing transactions match this filter.
        </div>
      </div>
    @endforelse
  </div>
</div>
@endsection
