# Filament Integration for Commentify

This guide explains how to set up Filament admin panel integration for managing Commentify reports, comments, and settings.

## Installation

First, install Filament in your main Laravel application:

```bash
composer require filament/filament:"^4.0"
php artisan filament:install --panels
```

## Features

Once Filament is installed, Commentify will automatically register:

1. **Comments Resource** - View, edit, and manage all comments with:
   - User information
   - Likes count
   - Replies count
   - Reports count
   - Commentable type and ID
   - Parent/child relationship
   - Soft delete support
2. **Comment Reports Resource** - Manage and review reported comments
3. **Commentify Settings Page** - Configure Commentify settings from Filament

## Usage

After installing Filament, you'll find:

- **Comments** management interface with full CRUD operations
- **Comment Reports** for reviewing reported comments
- **Commentify Settings** page for configuration

## Customization

You can customize the Filament resources by publishing them:

```bash
php artisan vendor:publish --tag=commentify-filament-resources
```

This will publish the resources to `app/Filament/Resources/Commentify/` where you can customize them.

