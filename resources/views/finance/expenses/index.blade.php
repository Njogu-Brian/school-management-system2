@extends('layouts.app')

@section('content')
<div class="finance-page">
  <div class="finance-shell">
    @include('finance.partials.header', [
      'title' => 'Expenses',
      'icon' => 'bi bi-wallet2',
      'subtitle' => 'Track school operating expenses and approvals',
      'actions' => '<a href="' . route('finance.expense-statements.index') . '" class="btn btn-finance btn-finance-outline me-2"><i class="bi bi-file-earmark-spreadsheet"></i> Statement Analyzer</a><a href="' . route('finance.expenses.create') . '" class="btn btn-finance btn-finance-primary"><i class="bi bi-plus-circle"></i> New Expense</a>'
    ])

    @if(session('success'))
      <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->any())
      <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    {{-- One-click cash-book export (per-expense rows by month + category summary) --}}
    <div class="d-flex justify-content-end mb-3">
      <form method="GET" action="{{ route('finance.expenses.cash-book-export') }}" class="d-flex align-items-end gap-2">
        <div>
          <label class="form-label small mb-1">Cash Book year</label>
          <select name="year" class="finance-form-select">
            @for($y = (int) now()->year; $y >= (int) now()->year - 3; $y--)
              <option value="{{ $y }}">{{ $y }}</option>
            @endfor
          </select>
        </div>
        <button type="submit" class="btn btn-finance btn-finance-outline">
          <i class="bi bi-file-earmark-excel"></i> Export Cash Book (Excel)
        </button>
      </form>
    </div>

    {{-- Existing vendors for type-ahead --}}
    <datalist id="vendor-options">
      @foreach($vendors as $vendor)
        <option value="{{ $vendor->name }}"></option>
      @endforeach
    </datalist>

    <div class="finance-card mb-3">
      <div class="finance-card-body">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label small mb-1">Search</label>
            <input name="search" value="{{ request('search') }}" class="finance-form-control" placeholder="Expense no, notes or vendor…">
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1">Status</label>
            <input name="status" value="{{ request('status') }}" class="finance-form-control" placeholder="e.g. submitted, paid">
          </div>
          <div class="col-md-3">
            <label class="form-label small mb-1">Vendor</label>
            <select name="vendor_id" class="finance-form-select">
              <option value="">All Vendors</option>
              @foreach($vendors as $vendor)
                <option value="{{ $vendor->id }}" @selected((string)request('vendor_id') === (string)$vendor->id)>{{ $vendor->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-finance btn-finance-primary flex-grow-1">Filter</button>
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="no_vendor" value="1" id="noVendor" @checked(request()->boolean('no_vendor'))>
              <label class="form-check-label small" for="noVendor">Only show expenses with no vendor</label>
            </div>
          </div>
        </form>
      </div>
    </div>

    <form id="bulkExpenseForm" method="POST" action="{{ route('finance.expenses.bulk-update') }}" class="finance-card mb-3 sticky-top" style="top: 0; z-index: 5;">
      @csrf
      <div class="finance-card-body">
        <div class="d-flex align-items-center gap-2 mb-2">
          <i class="bi bi-pencil-square"></i>
          <strong class="small">Bulk edit vendor / category</strong>
          <span class="text-muted small">Tick rows, type or pick a vendor, optionally a category, then Apply.</span>
        </div>
        <div class="row g-2 align-items-end">
          <div class="col-auto">
            <div class="form-check mb-1">
              <input class="form-check-input" type="checkbox" id="selectAllExpenses">
              <label class="form-check-label small" for="selectAllExpenses">Select all on page</label>
            </div>
            <div class="small text-muted"><span id="selectedExpenseCount">0</span> selected</div>
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-1">Vendor / payee</label>
            <input type="text" name="vendor_name" list="vendor-options" class="finance-form-control form-control-sm" placeholder="Type to search or add new" autocomplete="off">
          </div>
          <div class="col-md-4">
            <label class="form-label small mb-1">Category (optional)</label>
            <select name="expense_category_id" class="finance-form-select form-select-sm">
              <option value="">— Leave unchanged —</option>
              @foreach($categoryGroups as $groupName => $cats)
                <optgroup label="{{ $groupName }}">
                  @foreach($cats as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                  @endforeach
                </optgroup>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <button type="submit" class="btn btn-sm btn-finance btn-finance-primary w-100" id="bulkExpenseApply" disabled>Apply to selected</button>
          </div>
        </div>
      </div>
    </form>

    <div class="finance-table-wrapper">
      <table class="finance-table">
        <thead>
          <tr>
            <th style="width: 32px"></th>
            <th>No</th>
            <th>Date</th>
            <th>Vendor</th>
            <th>Status</th>
            <th>Total</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        @forelse($expenses as $expense)
          @php $posted = in_array($expense->status, ['approved', 'paid'], true); @endphp
          <tr>
            <td>
              <input type="checkbox" class="form-check-input expense-select" name="expense_ids[]" value="{{ $expense->id }}" form="bulkExpenseForm">
            </td>
            <td>{{ $expense->expense_no }}</td>
            <td>{{ optional($expense->expense_date)->format('Y-m-d') }}</td>
            <td>
              @if($expense->vendor)
                {{ $expense->vendor->name }}
              @else
                <span class="badge bg-warning text-dark">No vendor</span>
              @endif
            </td>
            <td>{{ ucfirst($expense->status) }}</td>
            <td>{{ number_format((float)$expense->total, 2) }}</td>
            <td class="text-nowrap">
              <a href="{{ route('finance.expenses.show', $expense) }}" class="btn btn-sm btn-info">View</a>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#edit-{{ $expense->id }}">Edit vendor</button>
            </td>
          </tr>
          <tr class="collapse" id="edit-{{ $expense->id }}">
            <td colspan="7" class="bg-light">
              <form method="POST" action="{{ route('finance.expenses.quick-update', $expense) }}" class="row g-2 align-items-end py-2">
                @csrf
                <div class="col-md-4">
                  <label class="form-label small mb-1">Vendor / payee</label>
                  <input type="text" name="vendor_name" list="vendor-options" value="{{ optional($expense->vendor)->name }}" class="form-control form-control-sm" placeholder="Type to search or add new" autocomplete="off">
                </div>
                <div class="col-md-4">
                  <label class="form-label small mb-1">Category {{ $posted ? '(locked — already posted)' : '' }}</label>
                  <select name="expense_category_id" class="form-select form-select-sm" @disabled($posted)>
                    <option value="">— Leave unchanged —</option>
                    @foreach($categoryGroups as $groupName => $cats)
                      <optgroup label="{{ $groupName }}">
                        @foreach($cats as $cat)
                          <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                      </optgroup>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-2">
                  <button type="submit" class="btn btn-sm btn-finance btn-finance-primary w-100">Save</button>
                </div>
              </form>
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center">No expenses found.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    <div class="mt-3">{{ $expenses->withQueryString()->links() }}</div>
  </div>
</div>

<script>
(function () {
  var checks = function () { return Array.prototype.slice.call(document.querySelectorAll('input.expense-select')); };
  var countEl = document.getElementById('selectedExpenseCount');
  var applyBtn = document.getElementById('bulkExpenseApply');
  var selectAll = document.getElementById('selectAllExpenses');
  var form = document.getElementById('bulkExpenseForm');

  function refresh() {
    var all = checks();
    var sel = all.filter(function (c) { return c.checked; }).length;
    if (countEl) countEl.textContent = sel;
    if (applyBtn) applyBtn.disabled = sel === 0;
    if (selectAll) {
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
  if (form) {
    form.addEventListener('submit', function (e) {
      var sel = checks().filter(function (c) { return c.checked; }).length;
      if (sel === 0) { e.preventDefault(); return; }
      if (!confirm('Apply to ' + sel + ' selected expense(s)?')) e.preventDefault();
    });
  }
  refresh();
})();
</script>
@endsection
