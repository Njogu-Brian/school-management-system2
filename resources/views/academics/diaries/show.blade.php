@extends('layouts.app')

@section('content')
<div class="container">
    <h1>Digital Diary - {{ $diary->classroom?->name ?? 'N/A' }}</h1>

    <div id="chat-box" class="chat-box border rounded p-3 mb-3" 
         style="height: 400px; overflow-y: auto; background: #f9f9f9;">
        @forelse($diary->messages as $msg)
            <div class="mb-2 d-flex {{ $msg->user_id == auth()->id() ? 'justify-content-end' : 'justify-content-start' }}">
                <div class="p-2 rounded shadow-sm {{ $msg->user_id == auth()->id() ? 'bg-success text-white' : 'bg-light text-dark' }}" style="max-width: 70%;">
                    <strong>{{ $msg->sender->name }}</strong><br>

                    {{-- Message body --}}
                    {!! nl2br(e($msg->body)) !!}

                    {{-- File Preview --}}
                    @if($msg->attachment_path)
                        @if(Str::endsWith($msg->attachment_path, ['.jpg','.png','.jpeg','.gif']))
                            <div><img src="{{ asset('storage/'.$msg->attachment_path) }}" class="img-fluid rounded mt-1"></div>
                        @elseif(Str::endsWith($msg->attachment_path, ['.pdf']))
                            <a href="{{ asset('storage/'.$msg->attachment_path) }}" target="_blank">📄 View PDF</a>
                        @else
                            <a href="{{ asset('storage/'.$msg->attachment_path) }}" target="_blank">📎 Download File</a>
                        @endif
                    @endif

                    <div class="small mt-1 text-end">
                        {{ $msg->created_at->format('d M H:i') }}
                        @php
                            $receipts = $msg->receipts;
                            $read = $receipts->where('user_id','!=',auth()->id())->isNotEmpty();
                        @endphp
                        @if($msg->user_id == auth()->id())
                            <span class="ms-2">{!! $read ? '✓✓' : '✓' !!}</span>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <p class="text-muted text-center">No messages yet. Start the conversation below.</p>
        @endforelse
    </div>

    {{-- Chat form --}}
    <form id="chat-form" method="POST" 
          action="{{ route('academics.diary.messages.store', $diary) }}" 
          enctype="multipart/form-data" class="d-flex align-items-center">
        @csrf
        <textarea name="body" id="chat-body" class="form-control me-2" placeholder="Type a message..." rows="1"></textarea>
        <input type="file" name="attachment" id="chat-attachment" class="form-control me-2" style="max-width: 200px;">
        <button type="submit" class="btn btn-success"><i class="bi bi-send"></i></button>
    </form>
</div>

{{-- Realtime JS --}}
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script> {{-- ✅ Replaced mix() with asset() --}}
<script>
    const chatBox = document.getElementById('chat-box');
    const chatForm = document.getElementById('chat-form');
    const diaryId = {{ $diary->id }};

    // Auto-scroll to bottom
    chatBox.scrollTop = chatBox.scrollHeight;

    // Listen for events
    if (typeof Echo !== 'undefined') {
        Echo.channel('diary.' + diaryId)
            .listen('.new-message', (e) => {
                const msg = e.message;
                const mine = msg.user_id == {{ auth()->id() }};
                const bubble = `
                    <div class="mb-2 d-flex ${mine ? 'justify-content-end' : 'justify-content-start'}">
                        <div class="p-2 rounded shadow-sm ${mine ? 'bg-success text-white' : 'bg-light text-dark'}" style="max-width: 70%;">
                            <strong>${msg.sender.name}</strong><br>
                            ${msg.body ?? ''}
                            ${msg.attachment_path ? `<a href="/storage/${msg.attachment_path}" target="_blank">📎 File</a>` : ''}
                            <div class="small mt-1 text-end">${msg.created_at}</div>
                        </div>
                    </div>`;
                chatBox.insertAdjacentHTML('beforeend', bubble);
                chatBox.scrollTop = chatBox.scrollHeight;
            });
    }

    // Ajax send
    chatForm.addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(chatForm);
        fetch(chatForm.action, {
            method: 'POST',
            body: formData,
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'}
        }).then(() => {
            chatForm.reset();
        });
    });
</script>
@endsection
