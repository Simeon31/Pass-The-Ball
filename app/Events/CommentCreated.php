<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $postId;
    public $comment;

    /**
     * Create a new event instance.
     */
    public function __construct($postId, $comment)
    {
        $this->postId = $postId;
        $this->comment = $comment;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        return new Channel('posts.' . $this->postId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'comment.created';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'post_id' => $this->postId,
            'comment' => $this->comment,
        ];
    }
}
