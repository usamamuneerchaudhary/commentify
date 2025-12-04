## Commentify - Laravel Livewire Comments

[![Latest Version on Packagist](https://img.shields.io/packagist/v/usamamuneerchaudhary/commentify?style=flat-square&g)](https://packagist.org/packages/usamamuneerchaudhary/commentify)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/usamamuneerchaudhary/commentify/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/usamamuneerchaudhary/commentify/?branch=main)
[![CodeFactor](https://www.codefactor.io/repository/github/usamamuneerchaudhary/commentify/badge)](https://www.codefactor.io/repository/github/usamamuneerchaudhary/commentify)
[![Build Status](https://scrutinizer-ci.com/g/usamamuneerchaudhary/commentify/badges/build.png?b=main)](https://scrutinizer-ci.com/g/usamamuneerchaudhary/commentify/build-status/main)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/usamamuneerchaudhary/commentify/badges/code-intelligence.svg?b=main)](https://scrutinizer-ci.com/code-intelligence)
[![Total Downloads](https://img.shields.io/packagist/dt/usamamuneerchaudhary/commentify?style=flat-square)](https://packagist.org/packages/usamamuneerchaudhary/commentify)
[![Licence](https://img.shields.io/packagist/l/usamamuneerchaudhary/commentify?style=flat-square)](https://github.com/usamamuneerchaudhary/commentify/blob/HEAD/LICENSE.md)

![commentify](public/images/commentify.gif)

## Introduction

Commentify is a powerful Laravel Livewire package designed to provide an easy-to-integrate commenting system for any
model in your Laravel application. Powered by Livewire, this package offers a seamless commenting experience that is
powered by Tailwind UI, making it easy for users to engage with your content. With features like comments pagination
and YouTube-style like/unlike buttons, this package is perfect for applications that require robust commenting
capabilities. Additionally, guest users can like and unlike comments based on their IP addresses. Mentions can be
used with "@" to tag specific users in replies and edits, while Markdown support allows for rich formatting in
comments. Whether you're building a blog, an e-commerce platform, or any other type of web application, Commentify is a
powerful tool for enhancing user engagement and collaboration.

## Features

- ✅ Easy to integrate
- ✅ Laravel 12+ support
- ✅ Livewire 3 support
- ✅ Livewire powered commenting system
- ✅ TailwindCSS UI
- ✅ Read-only mode (configurable via `config/commentify.php`)
- ✅ Add comments to any model
- ✅ Nested Comments
- ✅ Temporary user comment bans (block users from commenting until a set date)
- ✅ Comments Pagination
- ✅ YouTube style Like/unlike feature
- ✅ Guest like/unlike of comments (based on `IP` & `UserAgent`)
- ✅ Mention User with @ in Replies/Edits
- ✅ Markdown Support
- ✅ Full language/translation support (publish and override as needed)
- ✅ Customizable views (publish and override as needed)
- ✅ Policy-based authorization for all comment actions

## Prerequisites

- PHP 8.2 or higher
- Laravel 12 or higher
- [Livewire 3](https://livewire.laravel.com/docs/installation)
- [TailwindCSS](https://tailwindcss.com/)
- [AlpineJS](https://alpinejs.dev/essentials/installation) (included with Livewire 3)

## Installation Guide

You can install the package via composer:

```bash
composer require usamamuneerchaudhary/commentify
```

### Service Provider Registration

The service provider is **automatically discovered** in Laravel 12+ when installed via Composer from Packagist.

However, if you're using a local development setup (e.g., path repository with symlinks) or need to manually register it, add it to `bootstrap/providers.php`:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    // ... other providers
    Usamamuneerchaudhary\Commentify\Providers\CommentifyServiceProvider::class,
];
```

> **Note:** For local development with symlinked packages, explicit registration in `bootstrap/providers.php` ensures migrations and other package resources are loaded correctly.

### Run Migrations

Once the package is installed, you can run migrations:

```bash
php artisan migrate
```

### Publish config, views, and lang files as needed

```bash
php artisan vendor:publish --tag="commentify-config"
php artisan vendor:publish --tag=commentify-views
php artisan vendor:publish --tag=commentify-lang
php artisan vendor:publish --tag=commentify-migrations
```

This will publish `commentify.php` file in config directory. Here you can configure user route and pagination count etc.

### Publish `tailwind.config.js` file

This package utilizes TailwindCSS, and uses some custom configurations. You can publish the package's `tailwind.config.js` file by running the following command:

```bash
php artisan vendor:publish --tag="commentify-tailwind-config"
```

## Usage
In your model, where you want to integrate comments, simply add the `Commentable` trait in that model.
For example: 
```php
use Usamamuneerchaudhary\Commentify\Traits\Commentable;

class Article extends Model
{
    use Commentable;
}
```

Next, in your view, pass in the livewire comment component. For example, if your view file is `articles/show.blade.
php`. We can add the following code:
```html
<livewire:comments :model="$article"/>
```

#### Additionally, add the `HasUserAvatar` trait in `App\Models\User`, to use avatars:
```php
use Usamamuneerchaudhary\Commentify\Traits\HasUserAvatar;

class User extends Model
{
    use HasUserAvatar;
}
```
---
## 🔒 Read-Only Mode

Temporarily disable all commenting (for maintenance, etc):

- In `config/commentify.php`:
    ```php
    'read_only' => true,
    ```

---

## 🚫 Temporarily Block Users from Commenting

- Add the provided migration to your app to add a `comment_banned_until` column to your `users` table.
- Add the `HasCommentBan` trait to your User model:
    ```php
    use Usamamuneerchaudhary\Commentify\Traits\HasCommentBan;
    class User extends Authenticatable
    {
        use HasCommentBan;
    }
    ```
- Set `comment_banned_until` to a future date to block a user.

---

## 🌍 Language Support

- All strings are translatable.
- Publish lang files and override as needed in `lang/vendor/commentify`.

---

## 🛡️ Authorization

- All comment actions use Laravel policies.
- You can customize permissions and ban logic in your `CommentPolicy`.

---

## Tests

Run the test suite:

```bash
composer test
```

## Security

If you discover any security related issues, please email hello@usamamuneer.me instead of using the issue tracker.

## Credits

- [Laravel](https://laravel.com)
- [Tailwind](https://tailwindcss.com/)
- [Livewire](https://laravel-livewire.com/)
- [FlowBite](https://flowbite.com)
- [All Contributors](https://github.com/usamamuneerchaudhary/commentify/graphs/contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.


