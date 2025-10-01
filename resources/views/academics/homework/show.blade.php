@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $homework->title }}</h1>

    <p><strong>Classroom:</strong> {{ $homework->classroom?->name ?? 'N/A' }}</p>
    <p><strong>Subject:</strong> {{ $homework->subject?->name ?? 'N/A' }}</p>
    <p><strong>Due Date:</strong> {{ $homework->due_date?->format('d M Y') }}</p>

    <h4>Instructions</h4>
    <p>{{ $homework->instructions }}</p>

    @if($homework->file_path)
        <p><a href="{{ asset('storage/'.$homework->file_path) }}" target="_blank">Download Attachment</a></p>
    @endif

    <a href="{{ route('academics.homework.index') }}" class="btn btn-secondary">Back</a>
</div>
@endsection
