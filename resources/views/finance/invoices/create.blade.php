@extends('layouts.app')
@section('content')
<div class="container">
    <div class="alert alert-info">
        <h4>Invoice Generation Moved</h4>
        <p>Invoice generation has been merged into the <strong>Post Pending Fees</strong> feature.</p>
        <p>Please use the <a href="{{ route('finance.posting.index') }}" class="alert-link">Post Pending Fees</a> page to generate invoices.</p>
        <a href="{{ route('finance.posting.index') }}" class="btn btn-primary">Go to Post Pending Fees</a>
    </div>
</div>
@endsection
