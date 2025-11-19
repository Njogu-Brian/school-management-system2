@extends('layouts.app')
@section('content')
<div class="container">
    <h1>{{ $homework->title }}</h1>
    <p><strong>Classroom:</strong> {{ $homework->classroom?->name ?? 'All' }}</p>
    <p><strong>Subject:</strong> {{ $homework->subject?->name ?? 'N/A' }}</p>
    <p><strong>Due Date:</strong> {{ $homework->due_date->format('d M Y') }}</p>
    <p>{{ $homework->instructions }}</p>
    @if($homework->file_path)
        <a href="{{ asset('storage/'.$homework->file_path) }}" target="_blank">Download File</a>
    @endif

    <hr>
    <div class="alert alert-info">
        Diary conversations for homework have been moved to the Digital Diaries module.
        <a href="{{ route('academics.diaries.index') }}">Open the diary</a> to continue the discussion for each student.
    </div>
</div>
@endsection
