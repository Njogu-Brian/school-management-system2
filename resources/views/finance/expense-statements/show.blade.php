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
          @php $totalSubmitted = $expenseGroups->sum('submitted_count'); @endphp
          <div class="d-flex justify-content-end mt-2">
            @if($totalSubmitted > 0)
              <form method="POST" action="{{ route('finance.expense-statements.approve-expenses', $expenseStatement) }}"
                    onsubmit="return confirm('Approve all {{ $totalSubmitted }} submitted expense(s) and post them to the ledger?');">
                @csrf
                <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check2-all"></i> Approve all submitted ({{ $totalSubmitted }})</button>
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
                      @if($eg->submitted_count > 0 && $eg->category_id)
                        <form method="POST" action="{{ route('finance.expense-statements.approve-expenses', $expenseStatement) }}"
                              onsubmit="return confirm('Approve all {{ $eg->submitted_count }} submitted {{ $eg->category_name }} expense(s)?');">
                          @csrf
                          <input type="hidden" name="category_id" value="{{ $eg->category_id }}">
                          <button type="submit" class="btn btn-sm btn-success">Approve all {{ $eg->category_name }}</button>
                        </form>
                      @endif
                    </td>
                  </tr>
                  <tr class="collapse" id="exp-cat-{{ $loop->index }}">
                    <td colspan="5" class="p-0">
                      <table class="table table-sm mb-0">
                        <tbody>
                          @foreach($eg->expenses as $exp)
                            <tr>
                              <td class="ps-4">
                                <a href="{{ route('finance.expenses.show', $exp) }}">{{ $exp->expense_no }}</a>
                                <span class="text-muted small ms-2">{{ optional($exp->expense_date)->format('Y-m-d') }}</span>
                              </td>
                              <td class="text-end">KES {{ number_format((float) $exp->total, 2) }}</td>
                              <td>
                                @php $st = $exp->status; @endphp
                                <span class="badge bg-{{ $st === 'paid' ? 'success' : ($st === 'approved' ? 'info text-dark' : ($st === 'submitted' ? 'warning text-dark' : 'secondary')) }}">{{ ucfirst($st) }}</span>
                              </td>
                              <td class="text-end" style="width: 120px">
                                @if($exp->status === 'submitted')
                                  <form method="POST" action="{{ route('finance.expense-statements.approve-expenses', $expenseStatement) }}">
                                    @csrf
                                    <input type="hidden" name="expense_id" value="{{ $exp->id }}">
                                    <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                  </form>
                                @endif
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

    <div class="finance-card mb-3">
      <div class="finance-card-body">
        <form method="GET" action="{{ route('finance.expense-statements.show', $expenseStatement) }}" class="row g-2 align-items-center mb-3">
          @if($filter)<input type="hidden" name="filter" value="{{ $filter }}">@endif
          <div class="col-md-9 col-lg-10">
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search recipient, phone, paybill, account ref, receipt or narration…">
            </div>
          </div>
          <div class="col-md-3 col-lg-2 d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-grow-1">Search</button>
            @if($search !== '')
              <a href="{{ route('finance.expense-statements.show', array_filter([$expenseStatement->id, 'filter' => $filter])) }}" class="btn btn-outline-secondary">Clear</a>
            @endif
          </div>
        </form>

        @php $filterQuery = array_filter(['search' => $search]); @endphp
        <div class="d-flex flex-wrap gap-2">
          <a href="{{ route('finance.expense-statements.show', array_merge([$expenseStatement], $filterQuery)) }}" class="btn btn-sm {{ !$filter ? 'btn-primary' : 'btn-outline-secondary' }}">All Outgoing</a>
          <a href="{{ route('finance.expense-statements.show', array_merge([$expenseStatement, 'filter' => 'business'], $filterQuery)) }}" class="btn btn-sm {{ in_array($filter, ['business', 'confirmed']) ? 'btn-primary' : 'btn-outline-secondary' }}">Business</a>
          <a href="{{ route('finance.expense-statements.show', array_merge([$expenseStatement, 'filter' => 'personal'], $filterQuery)) }}" class="btn btn-sm {{ $filter === 'personal' ? 'btn-primary' : 'btn-outline-secondary' }}">Personal</a>
          <a href="{{ route('finance.expense-statements.show', array_merge([$expenseStatement, 'filter' => 'uncategorized'], $filterQuery)) }}" class="btn btn-sm {{ $filter === 'uncategorized' ? 'btn-primary' : 'btn-outline-secondary' }}">Uncategorized</a>
          <a href="{{ route('finance.expense-statements.show', array_merge([$expenseStatement, 'filter' => 'pending'], $filterQuery)) }}" class="btn btn-sm {{ $filter === 'pending' ? 'btn-primary' : 'btn-outline-secondary' }}">Pending</a>
          <a href="{{ route('finance.expense-statements.show', array_merge([$expenseStatement, 'filter' => 'fees'], $filterQuery)) }}" class="btn btn-sm {{ $filter === 'fees' ? 'btn-primary' : 'btn-outline-secondary' }}">Fees Only</a>
        </div>
      </div>
    </div>

    <form id="bulkForm" method="POST" action="{{ route('finance.expense-statements.bulk-update-groups', $expenseStatement) }}" class="finance-card mb-3 sticky-top" style="top: 0; z-index: 5;">
      @csrf
      <div class="finance-card-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="bi bi-check2-square"></i>
          <strong class="small">Mass classify</strong>
          <span class="text-muted small">Tick the recipients below, choose a classification, then Apply. Works together with search &amp; filters.</span>
        </div>
        <div class="row g-2 align-items-end">
          <div class="col-auto">
            <div class="form-check mb-1">
              <input class="form-check-input" type="checkbox" id="selectAllGroups">
              <label class="form-check-label small" for="selectAllGroups">Select all on page</label>
            </div>
            <div class="small text-muted"><span id="selectedCount">0</span> selected</div>
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1">Set classification</label>
            <select name="review_status" class="finance-form-select form-select-sm" required>
              <option value="confirmed_expense">Business expense</option>
              <option value="personal">Personal (not expense)</option>
              <option value="ignored">Ignore</option>
              <option value="pending">Pending review</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1">Expense category</label>
            <select name="expense_category_id" class="finance-form-select form-select-sm" data-category-select data-scope="group" data-selected="">
              <option value="">— Select —</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1">Description (optional)</label>
            <input type="text" name="expense_description" class="finance-form-control form-control-sm" placeholder="Applied to all selected">
          </div>
          <div class="col-md-2">
            <div class="form-check mb-1">
              <input class="form-check-input" type="checkbox" name="remember_choice" value="1" id="bulkRemember">
              <label class="form-check-label small" for="bulkRemember">Remember</label>
            </div>
            <button type="submit" class="btn btn-sm btn-finance btn-finance-primary w-100" id="bulkApplyBtn" disabled>Apply to selected</button>
          </div>
        </div>
      </div>
    </form>

    @forelse($groups as $index => $group)
      <div class="finance-card mb-3">
        <div class="finance-card-body">
          <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
            <div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <input type="checkbox" class="form-check-input mt-0 group-select" name="group_keys[]" value="{{ $group->group_key }}" form="bulkForm" title="Select for mass classify">
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
                          @unless($line->is_transaction_fee)
                          <div class="col-12 col-xl-3">
                            <input type="text" name="vendor_name" list="vendor-options" value="{{ $line->vendor_name }}" class="form-control form-control-sm" placeholder="{{ $line->recipient_name ?: 'Vendor / payee' }}" autocomplete="off">
                          </div>
                          @endunless
                          <div class="col-12 col-xl-3">
                            <select name="expense_category_id" class="form-select form-select-sm" data-category-select data-selected="{{ $line->expense_category_id }}">
                              <option value="">Category</option>
                            </select>
                          </div>
                          <div class="col-12 col-xl-3">
                            <input type="text" name="expense_description" value="{{ $line->expense_description }}" class="form-control form-control-sm" placeholder="What was this for?">
                          </div>
                          <div class="col-12">
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
                <label class="form-label small mb-1">Vendor / payee name</label>
                <input type="text" name="vendor_name" list="vendor-options" value="{{ $group->vendor_name }}" class="finance-form-control form-control-sm" placeholder="{{ $group->recipient_name ?: 'Type or pick a vendor' }}" autocomplete="off">
                <div class="form-text small">Overrides the statement payee on created expenses.</div>
              </div>
              <div class="col-md-3">
                <label class="form-label small mb-1">Expense category</label>
                <select name="expense_category_id" class="finance-form-select form-select-sm" data-category-select data-scope="group" data-selected="{{ $group->expense_category_id }}">
                  <option value="">— Select —</option>
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label small mb-1">What was this expense for?</label>
                <input type="text" name="expense_description" value="{{ $group->expense_description }}" class="finance-form-control form-control-sm" placeholder="e.g. Motor parts for school van">
              </div>
            </div>
            <div class="d-flex justify-content-end align-items-center gap-3 mt-2">
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" name="remember_choice" value="1" id="remember-{{ $index }}">
                <label class="form-check-label small" for="remember-{{ $index }}">Remember vendor &amp; category for this recipient</label>
              </div>
              <button type="submit" class="btn btn-sm btn-finance btn-finance-primary">Apply to Group</button>
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

    @if($groups->hasPages())
      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        <div class="text-muted small">
          Showing recipients {{ $groups->firstItem() }}–{{ $groups->lastItem() }} of {{ $groups->total() }}
        </div>
        {{ $groups->withQueryString()->links() }}
      </div>
    @endif
  </div>
</div>

{{-- Existing vendors for type-ahead on the vendor override fields. --}}
<datalist id="vendor-options">
  @foreach($vendorNames as $vName)
    <option value="{{ $vName }}"></option>
  @endforeach
</datalist>

{{-- Category options rendered once and cloned into each dropdown on demand (keeps the page fast for large statements). --}}
<template id="category-options-template">
  @foreach($categoryGroups as $groupName => $cats)
    <optgroup label="{{ $groupName }}">
      @foreach($cats as $category)
        <option value="{{ $category->id }}">{{ $category->name }}</option>
      @endforeach
    </optgroup>
  @endforeach
</template>

<style>
  .cat-combo { position: relative; }
  .cat-combo .cat-combo-input { background-image: none; cursor: text; }
  .cat-combo-panel {
    position: fixed; z-index: 1080; background: #fff; border: 1px solid #d0d5dd;
    border-radius: .375rem; max-height: 260px; overflow-y: auto;
    box-shadow: 0 8px 24px rgba(16,24,40,.16); display: none;
  }
  .cat-combo-panel.show { display: block; }
  .cat-combo-group {
    padding: .3rem .6rem; font-size: .7rem; text-transform: uppercase; letter-spacing: .03em;
    color: #667085; background: #f8f9fb; position: sticky; top: 0;
  }
  .cat-combo-item { padding: .4rem .6rem; font-size: .85rem; cursor: pointer; }
  .cat-combo-item:hover, .cat-combo-item.highlight { background: #eef2ff; }
  .cat-combo-item.active { font-weight: 600; color: #1d4ed8; }
  .cat-combo-empty { padding: .5rem .6rem; color: #98a2b3; font-size: .85rem; }
</style>

<script>
(function () {
  var tmpl = document.getElementById('category-options-template');
  if (!tmpl) return;

  function closeAll(except) {
    document.querySelectorAll('.cat-combo-panel.show').forEach(function (p) {
      if (p !== except) p.classList.remove('show');
    });
  }
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.cat-combo')) closeAll(null);
  });

  // Turn a (filled) native <select> into a searchable, click-or-type combobox.
  function enhance(select) {
    if (select.dataset.combo) return;
    select.dataset.combo = '1';

    var placeholderOpt = select.querySelector('option[value=""]');
    var placeholder = placeholderOpt ? placeholderOpt.textContent.trim() : 'Search category…';

    var items = [];
    Array.prototype.forEach.call(select.querySelectorAll('option'), function (opt) {
      if (opt.value === '') return;
      var grp = (opt.parentElement && opt.parentElement.tagName === 'OPTGROUP') ? opt.parentElement.label : '';
      items.push({ value: opt.value, label: opt.textContent.trim(), group: grp });
    });

    function labelFor(val) {
      for (var i = 0; i < items.length; i++) if (items[i].value === val) return items[i].label;
      return '';
    }

    var wrap = document.createElement('div');
    wrap.className = 'cat-combo';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.style.display = 'none';

    var input = document.createElement('input');
    input.type = 'text';
    input.setAttribute('autocomplete', 'off');
    input.className = 'cat-combo-input form-control ' + (select.className.indexOf('form-select-sm') !== -1 ? 'form-control-sm' : '');
    input.placeholder = placeholder;
    input.value = labelFor(select.value);
    wrap.appendChild(input);

    var panel = document.createElement('div');
    panel.className = 'cat-combo-panel';
    document.body.appendChild(panel);

    var highlight = -1;

    function visibleItemEls() { return panel.querySelectorAll('.cat-combo-item'); }

    function render(filter) {
      panel.innerHTML = '';
      highlight = -1;
      var f = (filter || '').toLowerCase().trim();
      var lastGroup = null, count = 0;
      items.forEach(function (it) {
        if (f && it.label.toLowerCase().indexOf(f) === -1 && it.group.toLowerCase().indexOf(f) === -1) return;
        if (it.group && it.group !== lastGroup) {
          var h = document.createElement('div');
          h.className = 'cat-combo-group';
          h.textContent = it.group;
          panel.appendChild(h);
          lastGroup = it.group;
        }
        var d = document.createElement('div');
        d.className = 'cat-combo-item';
        d.textContent = it.label;
        d.dataset.value = it.value;
        if (select.value === it.value) d.classList.add('active');
        panel.appendChild(d);
        count++;
      });
      if (count === 0) {
        var e = document.createElement('div');
        e.className = 'cat-combo-empty';
        e.textContent = 'No matching category';
        panel.appendChild(e);
      }
    }

    function position() {
      var r = input.getBoundingClientRect();
      panel.style.left = r.left + 'px';
      panel.style.width = r.width + 'px';
      var below = window.innerHeight - r.bottom;
      if (below < 220 && r.top > below) {
        panel.style.top = 'auto';
        panel.style.bottom = (window.innerHeight - r.top) + 'px';
      } else {
        panel.style.bottom = 'auto';
        panel.style.top = r.bottom + 'px';
      }
    }

    function open() {
      closeAll(panel);
      render(input.dataset.dirty ? input.value : '');
      position();
      panel.classList.add('show');
    }
    function close() {
      panel.classList.remove('show');
      input.dataset.dirty = '';
      input.value = labelFor(select.value);
    }
    function choose(val, lbl) {
      select.value = val;
      input.value = lbl;
      input.dataset.dirty = '';
      select.dispatchEvent(new Event('change', { bubbles: true }));
      panel.classList.remove('show');
    }
    function moveHighlight(dir) {
      var els = visibleItemEls();
      if (!els.length) return;
      els.forEach(function (el) { el.classList.remove('highlight'); });
      highlight += dir;
      if (highlight < 0) highlight = els.length - 1;
      if (highlight >= els.length) highlight = 0;
      els[highlight].classList.add('highlight');
      els[highlight].scrollIntoView({ block: 'nearest' });
    }

    input.addEventListener('focus', function () { input.select(); open(); });
    input.addEventListener('click', function () { if (!panel.classList.contains('show')) open(); });
    input.addEventListener('input', function () {
      input.dataset.dirty = '1';
      render(input.value);
      position();
      panel.classList.add('show');
    });
    input.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowDown') { e.preventDefault(); if (!panel.classList.contains('show')) open(); moveHighlight(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); moveHighlight(-1); }
      else if (e.key === 'Enter') {
        var els = visibleItemEls();
        if (panel.classList.contains('show') && els.length) {
          e.preventDefault();
          var target = highlight >= 0 ? els[highlight] : els[0];
          if (target && target.dataset.value) choose(target.dataset.value, target.textContent);
        }
      } else if (e.key === 'Escape') { close(); }
    });

    panel.addEventListener('mousedown', function (e) {
      var item = e.target.closest('.cat-combo-item');
      if (item && item.dataset.value) { e.preventDefault(); choose(item.dataset.value, item.textContent); }
    });

    window.addEventListener('scroll', function () { if (panel.classList.contains('show')) position(); }, true);
    window.addEventListener('resize', function () { if (panel.classList.contains('show')) position(); });
  }

  function fill(select) {
    if (!select.dataset.filled) {
      select.appendChild(tmpl.content.cloneNode(true));
      var val = select.getAttribute('data-selected');
      if (val) select.value = val;
      select.dataset.filled = '1';
    }
    enhance(select);
  }

  // Group-level / bulk dropdowns and any already-expanded groups: fill immediately.
  document.querySelectorAll('select[data-category-select][data-scope="group"]').forEach(fill);
  document.querySelectorAll('.collapse.show select[data-category-select]').forEach(fill);

  // Per-line dropdowns: fill lazily the first time their group is expanded.
  document.querySelectorAll('.collapse').forEach(function (el) {
    el.addEventListener('show.bs.collapse', function () {
      el.querySelectorAll('select[data-category-select]').forEach(fill);
    });
  });
})();

// Mass classify: select-all, live count, enable Apply.
(function () {
  var checks = function () { return Array.prototype.slice.call(document.querySelectorAll('input.group-select')); };
  var countEl = document.getElementById('selectedCount');
  var applyBtn = document.getElementById('bulkApplyBtn');
  var selectAll = document.getElementById('selectAllGroups');
  var statusSel = document.querySelector('#bulkForm select[name="review_status"]');
  var catSel = document.querySelector('#bulkForm select[name="expense_category_id"]');

  function refresh() {
    var sel = checks().filter(function (c) { return c.checked; }).length;
    if (countEl) countEl.textContent = sel;
    if (applyBtn) applyBtn.disabled = sel === 0;
    if (selectAll) {
      var all = checks();
      selectAll.checked = all.length > 0 && sel === all.length;
      selectAll.indeterminate = sel > 0 && sel < all.length;
    }
  }

  checks().forEach(function (c) { c.addEventListener('change', refresh); });
  if (selectAll) {
    selectAll.addEventListener('change', function () {
      checks().forEach(function (c) { c.checked = selectAll.checked; });
      refresh();
    });
  }

  var bulkForm = document.getElementById('bulkForm');
  if (bulkForm) {
    bulkForm.addEventListener('submit', function (e) {
      var sel = checks().filter(function (c) { return c.checked; }).length;
      if (sel === 0) { e.preventDefault(); return; }
      if (statusSel && statusSel.value === 'confirmed_expense' && catSel && !catSel.value) {
        e.preventDefault();
        alert('Select an expense category when marking as Business expense.');
        return;
      }
      if (!confirm('Apply this classification to ' + sel + ' selected recipient group(s)?')) {
        e.preventDefault();
      }
    });
  }

  refresh();
})();
</script>
@endsection
