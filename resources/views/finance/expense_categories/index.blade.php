@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Expense Categories', 'icon' => 'bi bi-tags', 'subtitle' => 'Configure expense category taxonomy'])
  <div class="finance-card mb-3"><div class="finance-card-body">
    <form method="POST" action="{{ route('finance.expense-categories.store') }}" class="row g-2">@csrf
      <div class="col-md-2"><input class="finance-form-control" name="code" placeholder="Code" required></div>
      <div class="col-md-4"><input class="finance-form-control" name="name" placeholder="Name" required></div>
      <div class="col-md-3"><select class="finance-form-select" name="parent_id"><option value="">No Parent</option>@foreach($parents as $parent)<option value="{{ $parent->id }}">{{ $parent->name }}</option>@endforeach</select></div>
      <div class="col-md-2"><select class="finance-form-select" name="is_active"><option value="1">Active</option><option value="0">Inactive</option></select></div>
      <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
    </form>
  </div></div>

  <div class="finance-table-wrapper">
    <table class="finance-table"><thead><tr><th>Code</th><th>Name</th><th>Parent</th><th>Status</th></tr></thead><tbody>
      @foreach($categories as $category)
      <tr><td>{{ $category->code }}</td><td>{{ $category->name }}</td><td>{{ optional($category->parent)->name }}</td><td>{{ $category->is_active ? 'Active' : 'Inactive' }}</td></tr>
      @endforeach
    </tbody></table>
  </div>
  {{ $categories->links() }}
</div></div>
@endsection
