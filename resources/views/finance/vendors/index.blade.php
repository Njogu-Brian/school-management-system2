@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Vendors', 'icon' => 'bi bi-building', 'subtitle' => 'Manage billed suppliers', 'actions' => '<a href="' . route('finance.vendors.create') . '" class="btn btn-finance btn-finance-primary">Add Vendor</a>'])
  <div class="finance-table-wrapper">
    <table class="finance-table">
      <thead><tr><th>Name</th><th>Type</th><th>Phone</th><th>Status</th><th></th></tr></thead>
      <tbody>
      @foreach($vendors as $vendor)
      <tr>
        <td>{{ $vendor->name }}</td><td>{{ $vendor->type }}</td><td>{{ $vendor->phone }}</td><td>{{ $vendor->is_active ? 'Active' : 'Inactive' }}</td>
        <td><a href="{{ route('finance.vendors.edit', $vendor) }}" class="btn btn-sm btn-warning">Edit</a></td>
      </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  {{ $vendors->links() }}
</div></div>
@endsection
