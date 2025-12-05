# Commentify Notifications Setup Guide

This guide explains how to set up notifications in your main Laravel application to receive notifications when comments are posted, liked, or reported.

## Prerequisites

1. **Database Notifications Table** (usually already exists in Laravel)
   ```bash
   php artisan notifications:table
   php artisan migrate
   ```

2. **Queue Configuration** (if using queues - recommended)
   - Set up your queue driver in `.env`:
     ```env
     QUEUE_CONNECTION=database
     ```
   - Run queue worker:
     ```bash
     php artisan queue:work
     ```

## Step 1: Enable Notifications in Config

In your `config/commentify.php` file:

```php
'enable_notifications' => true,
'notification_channels' => ['database'], // or ['database', 'mail', 'broadcast']
```

## Step 2: Ensure User Model Uses Notifiable Trait

Your `App\Models\User` model should use the `Notifiable` trait (usually already included):

```php
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    // ...
}
```

## Step 3: Display Notifications in Your UI

### Option A: Using Laravel's Default Notification Component

Laravel provides a default notification component. You can customize it or use your own.

### Option B: Create Custom Notification Display

Create a Livewire component or Blade view to display notifications:

```blade
@foreach(auth()->user()->unreadNotifications as $notification)
    <div class="notification">
        {{ $notification->data['message'] }}
        <a href="{{ route('comments.show', $notification->data['comment_id']) }}">
            View Comment
        </a>
    </div>
@endforeach
```

### Option C: Mark Notifications as Read

```php
// In a controller or Livewire component
auth()->user()->unreadNotifications->markAsRead();

// Or mark specific notification
$notification->markAsRead();
```

## Step 5: Configure Mail Notifications (Optional)

If using `mail` channel, configure your mail settings in `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## Step 6: Configure Broadcast Notifications (Optional)

If using `broadcast` channel:

1. Install Laravel Echo and Pusher/Redis
2. Configure broadcasting in `config/broadcasting.php`
3. Set up frontend to listen for notifications

## Notification Channels Available

- **database**: Stores notifications in database (requires `notifications` table)
- **mail**: Sends email notifications (requires mail configuration)
- **broadcast**: Real-time notifications via WebSockets (requires broadcasting setup)

## Customization

### Customize Notification Content

You can extend the notification classes or create your own:

```php
use Usamamuneerchaudhary\Commentify\Notifications\CommentPostedNotification;

class CustomCommentNotification extends CommentPostedNotification
{
    public function toArray($notifiable): array
    {
        return [
            // Your custom data
        ];
    }
}
```

### Customize Who Receives Notifications

Modify the listener logic to determine who should be notified based on your business rules.

## Testing

1. Enable notifications in config
2. Post a comment or like a comment
3. Check the `notifications` table in database
4. Verify notifications appear for the correct users

## Troubleshooting

- **Notifications not appearing**: Check queue worker is running if using queues
- **Database notifications not saving**: Ensure `notifications` table exists
- **Mail not sending**: Check mail configuration and logs
- **Broadcast not working**: Verify broadcasting configuration and frontend setup

