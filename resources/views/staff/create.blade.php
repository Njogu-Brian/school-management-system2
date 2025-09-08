@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Add New Staff</h1>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('staff.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        @include('staff.partials.form')

        <button class="btn btn-primary mt-3">💾 Save Staff</button>
    </form>
</div>
@endsection
