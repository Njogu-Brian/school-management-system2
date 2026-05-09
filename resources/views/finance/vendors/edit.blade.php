@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Edit Vendor', 'icon' => 'bi bi-pencil', 'subtitle' => 'Update supplier/vendor'])
  <form method="POST" action="{{ route('finance.vendors.update', $vendor) }}" class="finance-card">@csrf @method('PUT')
    <div class="finance-card-body">@include('finance.vendors.partials.form', ['vendor' => $vendor])<button class="btn btn-primary">Update</button></div>
  </form>
</div></div>
@endsection
