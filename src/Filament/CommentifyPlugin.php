<?php

namespace Usamamuneerchaudhary\Commentify\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Usamamuneerchaudhary\Commentify\Filament\Pages\CommentifySettings;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentReportResource;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentResource;

class CommentifyPlugin implements Plugin
{
    public function getId(): string
    {
        return 'commentify';
    }

    public function register(Panel $panel): void
    {
        // Check if Filament is installed
        if (!class_exists(\Filament\Filament::class)) {
            throw new \RuntimeException(
                'Filament is not installed. Please install it first: composer require filament/filament'
            );
        }
        $panel
            ->resources([
                CommentResource::class,
                CommentReportResource::class,
            ])
            ->pages([
                CommentifySettings::class,
            ]);
    }

    public function boot(Panel $panel): void
    {
        //
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }
}

