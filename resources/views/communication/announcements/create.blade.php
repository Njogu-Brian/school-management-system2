@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Create Announcement</h4>
    <form action="{{ route('announcements.store') }}" method="POST">
        @include('communication.announcements.partials._form')
    </form>
</div>
@endsection
