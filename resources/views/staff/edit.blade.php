@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Edit Staff</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('staff.update', $staff->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        @include('staff.partials.form')

        <button type="submit" class="btn btn-primary mt-3">🔄 Update Staff</button>
    </form>
</div>
@endsection
