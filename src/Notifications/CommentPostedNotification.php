<?php

namespace Usamamuneerchaudhary\Commentify\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Usamamuneerchaudhary\Commentify\Commentify;
use Usamamuneerchaudhary\Commentify\Events\CommentPosted;

class CommentPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public CommentPosted $event
    ) {}

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
        $mail = (new MailMessage)
            ->subject(__('commentify::commentify.notifications.comment_posted_subject'))
            ->line(__('commentify::commentify.notifications.comment_posted_line', [
                'user' => $this->event->comment->authorName(),
            ]));

        $url = $this->applicationCommentUrl();

        if ($url !== null) {
            $mail->action(__('commentify::commentify.notifications.view_comment'), $url);
        }

        return $mail;
    }

    /**
     * Permalink for the mail button. Off-site fallbacks, including the request referer, are left out.
     */
    protected function applicationCommentUrl(): ?string
    {
        $url = Commentify::commentUrl($this->event->comment);
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $linkHost = parse_url($url, PHP_URL_HOST);

        if (! is_string($appHost) || ! is_string($linkHost) || strcasecmp($appHost, $linkHost) !== 0) {
            return null;
        }

        return $url;
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
            'user_id' => $this->event->comment->user_id,
            'user_name' => $this->event->comment->authorName(),
            'message' => __('commentify::commentify.notifications.comment_posted_message', [
                'user' => $this->event->comment->authorName(),
            ]),
        ];
    }
}
