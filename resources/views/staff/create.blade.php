@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Add New Staff</h1>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('staff.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @include('staff.partials.form')
        <div class="mt-3">
            <button type="submit" class="btn btn-primary">💾 Save</button>
            <a href="{{ route('staff.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
