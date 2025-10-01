@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Diary Entry - {{ $diary->classroom?->name ?? 'N/A' }}</h1>

    <p><strong>Week Start:</strong> {{ $diary->week_start->format('d M Y') }}</p>

    <h4>Activities</h4>
    <p>{{ $diary->entries['activities'] ?? '' }}</p>

    @if(!empty($diary->entries['announcements']))
        <h4>Announcements</h4>
        <p>{{ $diary->entries['announcements'] }}</p>
    @endif

    <a href="{{ route('academics.diaries.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
