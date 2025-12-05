<?php

namespace Usamamuneerchaudhary\Commentify\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Usamamuneerchaudhary\Commentify\Events\CommentLiked;

class CommentLikedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public CommentLiked $event
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = config('commentify.notification_channels', ['database']);

        return array_filter($channels, function ($channel) {
            return in_array($channel, ['database', 'mail', 'broadcast']);
        });
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('commentify::commentify.notifications.comment_liked_subject'))
            ->line(__('commentify::commentify.notifications.comment_liked_line'))
            ->action(
                __('commentify::commentify.notifications.view_comment'),
                url('/comments/' . $this->event->comment->id)
            );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'comment_id' => $this->event->comment->id,
            'user_id' => $this->event->userId,
            'message' => __('commentify::commentify.notifications.comment_liked_message'),
        ];
    }
}

