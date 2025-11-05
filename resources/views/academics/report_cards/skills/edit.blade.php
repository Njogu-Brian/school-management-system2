@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Edit Skill</h1>

    <form action="{{ route('academics.report_cards.skills.update',[$reportCard,$skill]) }}" method="POST">
        @csrf @method('PUT')
        @include('academics.report_cards.skills.partials.form',['skill'=>$skill])
        <button class="btn btn-primary">Update</button>
    </form>
</div>
@endsection
