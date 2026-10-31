<?php

namespace Usamamuneerchaudhary\Commentify\Filament;

use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Usamamuneerchaudhary\Commentify\Filament\Pages\CommentifySettings;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentReportResource;
use Usamamuneerchaudhary\Commentify\Filament\Resources\CommentResource;

class CommentifyPlugin implements Plugin
{
    /**
     * Panel plugin id used to resolve this plugin.
     */
    public function getId(): string
    {
        return 'commentify';
    }

    /**
     * Register the comment resources and the settings page on the panel.
     */
    public function register(Panel $panel): void
    {
        // Check if Filament is installed
        if (! class_exists(Filament::class)) {
            return;
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

    /**
     * The plugin has nothing to boot after registration.
     */
    public function boot(Panel $panel): void
    {
        //
    }

    /**
     * Resolve a plugin instance from the container.
     */
    public static function make(): static
    {
        return app(static::class);
    }

    /**
     * Return the plugin instance registered on the current panel.
     */
    public static function get(): static
    {
        return filament(app(static::class)->getId());
    }
}
