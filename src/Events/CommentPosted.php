<?php

namespace Usamamuneerchaudhary\Commentify\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class CommentPosted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Comment $comment
    ) {
    }
}

