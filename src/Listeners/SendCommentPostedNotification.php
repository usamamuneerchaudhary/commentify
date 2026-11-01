<?php

namespace Usamamuneerchaudhary\Commentify\Listeners;

use Illuminate\Database\Eloquent\Model;
use Usamamuneerchaudhary\Commentify\Events\CommentPosted;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Notifications\CommentPostedNotification;
use Usamamuneerchaudhary\CommentifyPro\Listeners\SendCommentNotifications;

class SendCommentPostedNotification
{
    /**
     * Notify the parent author, or the commentable owner, when notifications are on.
     * Pro registers its own listener, so this one stays quiet when that class is installed.
     */
    public function handle(CommentPosted $event): void
    {
        if (! config('commentify.enable_notifications', false)) {
            return;
        }

        if ($this->proHandlesNotifications()) {
            return;
        }

        $comment = $event->comment;

        if (! $comment->isApproved()) {
            return;
        }

        $recipient = $this->recipient($comment);

        if (! $recipient instanceof Model || ! method_exists($recipient, 'notify')) {
            return;
        }

        if ($comment->user_id !== null && (int) $recipient->getKey() === (int) $comment->user_id) {
            return;
        }

        $recipient->notify(new CommentPostedNotification($event));
    }

    /**
     * Pro ships its own comment mail, so this listener stays quiet when that class is installed.
     */
    protected function proHandlesNotifications(): bool
    {
        return class_exists(SendCommentNotifications::class);
    }

    /**
     * Parent author for a reply, otherwise the commentable's user when that relation exists.
     */
    protected function recipient(Comment $comment): ?Model
    {
        $comment->loadMissing(['parent.user', 'commentable']);

        $parentUser = $comment->parent?->user;

        if ($parentUser instanceof Model) {
            return $parentUser;
        }

        $commentable = $comment->commentable;

        if ($commentable instanceof Model && method_exists($commentable, 'user')) {
            $owner = $commentable->user;

            if ($owner instanceof Model) {
                return $owner;
            }
        }

        return null;
    }
}
