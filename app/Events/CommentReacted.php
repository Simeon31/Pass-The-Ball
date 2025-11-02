<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentReacted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $commentId;
    public $reactions;

    /**
     * Create a new event instance.
     */
    public function __construct($commentId, $reactions)
    {
        $this->commentId = $commentId;
        $this->reactions = $reactions;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('comments.' . $this->commentId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'comment.reacted';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'commentId' => $this->commentId,
            'reactions' => $this->reactions,
        ];
    }
}
