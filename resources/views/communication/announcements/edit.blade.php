@extends('layouts.app')

@section('content')
<div class="container">
    <h4>Edit Announcement</h4>
    <form action="{{ route('announcements.update', $announcement) }}" method="POST">
        @method('PUT')
        @include('communication.announcements.partials._form', ['announcement' => $announcement])
    </form>
</div>
@endsection
