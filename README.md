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

## Some Features Highlight

- Easy to integrate
- Supports Laravel 10+
- Supports Livewire 3
- Livewire powered commenting system
- Tailwind UI
- Add comments to any model
- Nested Comments
- Comments Pagination
- Youtube style Like/unlike feature
- Guest like/unlike of comments (based on `IP` & `UserAgent`)
- Mention User with @ in Replies/Edits
- Markdown Support

## Prerequisites

- [Livewire](https://laravel-livewire.com/docs/2.x/installation)
- [TailwindCSS](https://tailwindcss.com/)
- [AlpineJS](https://alpinejs.dev/essentials/installation)

## Installation Guide

You can install the package via composer:

```composer require usamamuneerchaudhary/commentify```

### Register Service Provider

Add the service provider in `config/app.php`:

```php
Usamamuneerchaudhary\Commentify\Providers\CommentifyServiceProvider::class,
```

### Run Migrations

Once the package is installed, you can run migrations,
```php artisan migrate```

### Publish Config File

```php
 php artisan vendor:publish --tag="commentify-config"
```
This will publish `commentify.php` file in config directory. Here you can configure user route and pagination count etc.

### Publish `tailwind.config.js` file, 

This package utilizes TailwindCSS, and use some custom configurations. You can publish package's `tailwind.config.
js` file by running the following command:

```php
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

## Tests

`composer test`

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


