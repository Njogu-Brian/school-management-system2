@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Diary Entry - {{ $diary->classroom->name }}</h1>

    <p><strong>Week:</strong> {{ $diary->week }}</p>

    <h4>Activities</h4>
    <p>{{ $diary->activities }}</p>

    @if($diary->announcements)
        <h4>Announcements</h4>
        <p>{{ $diary->announcements }}</p>
    @endif

    <a href="{{ route('diaries.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
