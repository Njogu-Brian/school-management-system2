<?php

namespace App\Http\Controllers\Academics;

use App\Http\Controllers\Controller;
use App\Models\Academics\Diary;
use App\Models\Academics\DiaryMessage;
use App\Models\Academics\DiaryReadReceipt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Events\NewDiaryMessage;

class DiaryMessageController extends Controller
{
    public function store(Request $request, Diary $diary)
    {
        $request->validate([
            'body' => 'nullable|string',
            'attachment' => 'nullable|file|max:10240'
        ]);

        $path = null;
        if ($request->hasFile('attachment')) {
            $path = $request->file('attachment')->store('diary', 'public');
        }

        $message = DiaryMessage::create([
            'diary_id'       => $diary->id,
            'user_id'        => Auth::id(),
            'message_type'   => $path ? 'file' : 'text',
            'body'           => $request->body,
            'attachment_path'=> $path,
        ]);

        // Fire event for realtime update
        broadcast(new NewDiaryMessage($message->load('sender')))->toOthers();

        return response()->json(['status' => 'ok', 'message' => $message]);
    }

    public function markRead(DiaryMessage $message)
    {
        DiaryReadReceipt::updateOrCreate(
            ['message_id' => $message->id, 'user_id' => Auth::id()],
            ['read_at' => now()]
        );

        return response()->json(['status' => 'ok']);
    }
}
