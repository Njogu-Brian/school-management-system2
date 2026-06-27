@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Expense Categories', 'icon' => 'bi bi-tags', 'subtitle' => 'Main groups (e.g. Fuel) and sub-categories linked to chart of accounts'])

  @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
  @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

  <div class="finance-card mb-3"><div class="finance-card-body">
    <h6 class="mb-3">Add Category</h6>
    <form method="POST" action="{{ route('finance.expense-categories.store') }}" class="row g-2">@csrf
      <div class="col-md-2"><input class="finance-form-control" name="code" placeholder="Code (auto)"></div>
      <div class="col-md-3"><input class="finance-form-control" name="name" placeholder="Name" required></div>
      <div class="col-md-2">
        <select class="finance-form-select" name="parent_id">
          <option value="">Top-level group</option>
          @foreach($headerParents as $parent)<option value="{{ $parent->id }}">{{ $parent->name }}</option>@endforeach
        </select>
      </div>
      <div class="col-md-2">
        <select class="finance-form-select" name="account_id">
          <option value="">GL account</option>
          @foreach($accountGroups as $groupLabel => $groupAccounts)
            <optgroup label="{{ $groupLabel }}">
              @foreach($groupAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach
            </optgroup>
          @endforeach
        </select>
      </div>
      <div class="col-md-1">
        <select class="finance-form-select" name="is_header"><option value="0">Line item</option><option value="1">Group header</option></select>
      </div>
      <div class="col-md-1"><select class="finance-form-select" name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div>
      <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
    </form>
    <p class="small text-muted mt-2 mb-0">Codes are generated automatically when left blank (e.g. FUEL-PETROL under Fuel group).</p>
  </div></div>

  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Code</th><th>Name</th><th>GL Account</th><th>Type</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        @foreach($tree as $category)
          @include('finance.expense_categories._row', ['category' => $category, 'depth' => 0, 'headerParents' => $headerParents, 'accountGroups' => $accountGroups, 'selectableCategories' => $selectableCategories])
        @endforeach
      </tbody>
    </table>
  </div>
</div></div>
@endsection
