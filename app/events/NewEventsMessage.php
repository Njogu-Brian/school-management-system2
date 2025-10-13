<?php

namespace App\Events;

use App\Models\Academics\DiaryMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewDiaryMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(DiaryMessage $message)
    {
        $this->message = $message;
    }

    public function broadcastOn()
    {
        return new Channel('diary.' . $this->message->diary_id);
    }

    public function broadcastAs()
    {
        return 'new-message';
    }
}
