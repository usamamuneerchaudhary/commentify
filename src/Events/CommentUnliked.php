<?php

namespace Usamamuneerchaudhary\Commentify\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class CommentUnliked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Carry the comment that was unliked and, when known, the user who unliked it.
     */
    public function __construct(
        public Comment $comment,
        public ?int $userId = null
    ) {}
}
