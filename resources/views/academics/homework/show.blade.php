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
   <h3>Diary Conversation</h3>
        <div class="border p-3 mb-3" style="max-height:300px;overflow:auto;">
            @if($homework->diary && $homework->diary->messages->count())
                @foreach($homework->diary->messages as $msg)
                    <div class="mb-2">
                        <strong>{{ $msg->sender->name ?? 'Unknown' }}:</strong>
                        {{ $msg->content }}
                        <small class="text-muted d-block">{{ $msg->created_at->format('d M Y, h:i A') }}</small>
                    </div>
                @endforeach
            @else
                <p class="text-muted mb-0">No messages yet for this homework.</p>
            @endif
        </div>

    <form method="POST" action="{{ route('academics.diary.messages.store',$homework->diary) }}" enctype="multipart/form-data">
        @csrf
        <textarea name="body" class="form-control mb-2" placeholder="Type message..."></textarea>
        <input type="file" name="attachment" class="form-control mb-2">
        <button class="btn btn-success">Send</button>
    </form>
</div>
@endsection
