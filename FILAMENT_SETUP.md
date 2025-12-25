# Filament Integration for Commentify

This guide explains how to set up Filament admin panel integration for managing Commentify reports, comments, and settings.

## Installation

First, install Filament in your main Laravel application:

```bash
composer require filament/filament:"^4.0"
php artisan filament:install --panels
```

## Plugin Registration

Register the Commentify plugin in your Filament panel configuration (e.g., `app/Providers/Filament/AdminPanelProvider.php`):

```php
use Filament\Panel;
use Usamamuneerchaudhary\Commentify\Filament\CommentifyPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(CommentifyPlugin::make());
}
```

This will automatically register all Commentify resources and pages.

## Features

Once the plugin is registered, you'll have access to:

1. **Comments Resource** - View, edit, and manage all comments with:
   - User information
   - Likes count
   - Replies count
   - Reports count
   - Commentable type and ID
   - Parent/child relationship
   - Soft delete support
   - **Approve/Disapprove actions** (when moderation is enabled)
   - **Bulk approve/disapprove actions**
   - Filter by approval status (Approved/Pending/All)

2. **Comment Reports Resource** - Manage and review reported comments

3. **Commentify Settings Page** - Configure Commentify settings from Filament, including:
   - CSS framework selection
   - Comment moderation (require approval)
   - Reporting settings
   - Theme configuration
   - And more...

## Comment Moderation

When comment approval is enabled (`require_approval => true` in config), you can manage comment approval through Filament:

### Approving Individual Comments

1. Navigate to **Comments** in the Filament admin panel
2. Find the comment you want to approve
3. Click the **Approve** action button (visible only for unapproved comments)
4. Confirm the action

### Disapproving Comments

1. Navigate to **Comments** in the Filament admin panel
2. Find the approved comment you want to disapprove
3. Click the **Disapprove** action button (visible only for approved comments)
4. Confirm the action

### Bulk Actions

1. Select multiple comments using checkboxes
2. Use the **Approve Selected** or **Disapprove Selected** bulk actions from the toolbar
3. Confirm the action

### Filtering by Approval Status

Use the **Approval Status** filter in the Comments table to view:
- **All Comments** - Show all comments regardless of approval status
- **Approved** - Show only approved comments
- **Pending** - Show only unapproved comments awaiting approval

## Customization

You can customize the Filament views by publishing them:

```bash
php artisan vendor:publish --tag="commentify-filament-views"
```

This will publish the views to `resources/views/vendor/commentify/filament/` where you can customize them.

