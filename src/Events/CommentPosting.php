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

    public function __construct(public Comment $comment) {}

    public function cancel(?string $reason = null): void
    {
        $this->cancelled = true;
        $this->reason = $reason;
    }

    public function isCancelled(): bool
    {
        return $this->cancelled;
    }
}
