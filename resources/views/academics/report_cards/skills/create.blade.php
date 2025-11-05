@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Add Skill</h1>

    <form action="{{ route('academics.report_cards.skills.store',$reportCard) }}" method="POST">
        @csrf
        @include('academics.report_cards.skills.partials.form',['skill'=>null])
        <button class="btn btn-success">Save</button>
    </form>
</div>
@endsection
