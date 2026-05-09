@extends('layouts.app')

@section('content')
<div class="finance-page"><div class="finance-shell">
  @include('finance.partials.header', ['title' => 'Create Vendor', 'icon' => 'bi bi-plus-circle', 'subtitle' => 'Add supplier/vendor'])
  <form method="POST" action="{{ route('finance.vendors.store') }}" class="finance-card">@csrf
    <div class="finance-card-body">@include('finance.vendors.partials.form', ['vendor' => null])<button class="btn btn-primary">Create</button></div>
  </form>
</div></div>
@endsection
