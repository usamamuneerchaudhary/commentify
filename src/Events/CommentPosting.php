<?php

namespace Usamamuneerchaudhary\Commentify\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class CommentPosting
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public bool $cancelled = false;

    public ?string $reason = null;

    /**
     * Carry the comment that is about to be saved.
     */
    public function __construct(public Comment $comment) {}

    /**
     * Stop the save and record an optional reason for the flash message.
     */
    public function cancel(?string $reason = null): void
    {
        $this->cancelled = true;
        $this->reason = $reason;
    }

    /**
     * Whether a listener has cancelled this save.
     */
    public function isCancelled(): bool
    {
        return $this->cancelled;
    }
}
