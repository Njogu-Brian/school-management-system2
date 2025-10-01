@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Behaviour Category</h1>

    <form action="{{ route('academics.behaviours.update',$behaviour) }}" method="POST">
        @csrf @method('PUT')
        @include('academics.behaviours.partials.form',['behaviour'=>$behaviour])
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
