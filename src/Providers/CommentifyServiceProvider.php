<?php

namespace Usamamuneerchaudhary\Commentify\Providers;

use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\ComponentHookRegistry;
use Livewire\Features\SupportLazyLoading\SupportLazyLoading;
use Livewire\Livewire;
use Usamamuneerchaudhary\Commentify\Events\CommentPosted;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comment;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comments;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Like;
use Usamamuneerchaudhary\Commentify\Listeners\SendCommentPostedNotification;
use Usamamuneerchaudhary\Commentify\Policies\CommentPolicy;

use function Livewire\after;

class CommentifyServiceProvider extends ServiceProvider
{
    /**
     * Merge the package config, bind the comment policy, and register Markdown.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/commentify.php', 'commentify');

        $this->app->bind(CommentPolicy::class, function ($app) {
            return new CommentPolicy;
        });

        Gate::policy(\Usamamuneerchaudhary\Commentify\Models\Comment::class, CommentPolicy::class);

        $this->app->register(MarkdownServiceProvider::class);
    }

    /**
     * Publish assets, load views, translations, and migrations, and register the Livewire components.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config file
            $this->publishes([
                __DIR__.'/../../config/commentify.php' => config_path('commentify.php'),
            ], 'commentify-config');

            $this->publishes([
                __DIR__.'/../../tailwind.config.js' => base_path('tailwind.config.js'),
            ], 'commentify-tailwind-config');

            // Publish Tailwind views
            $this->publishes([
                __DIR__.'/../../resources/views/tailwind' => resource_path('views/vendor/commentify'),
            ], 'commentify-tailwind-views');

            // Publish Bootstrap views
            $this->publishes([
                __DIR__.'/../../resources/views/bootstrap' => resource_path('views/vendor/commentify'),
            ], 'commentify-bootstrap-views');

            // Only register Filament views for publishing if Filament is installed
            if (class_exists(Filament::class)) {
                $this->publishes([
                    __DIR__.'/../../resources/views/filament' => resource_path('views/vendor/commentify'),
                ], 'commentify-filament-views');
            }

            // Publish language files
            $this->publishes([
                __DIR__.'/../../lang' => resource_path('../lang/vendor/commentify'),
            ], 'commentify-lang');

            // Publish migrations (optional - for users who want to customize them)
            $this->publishes([
                __DIR__.'/../../database/migrations' => database_path('migrations'),
            ], 'commentify-migrations');
        }

        $migrationPath = realpath(__DIR__.'/../../database/migrations');
        if ($migrationPath && is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }

        // Load views based on CSS framework
        $config = $this->app->make('config');
        $framework = $config->get('commentify.css_framework', 'tailwind');

        // Validate framework value
        if (! in_array($framework, ['tailwind', 'bootstrap'])) {
            $framework = 'tailwind';
        }

        $frameworkPath = __DIR__.'/../../resources/views/'.$framework;

        if (is_dir($frameworkPath)) {
            $this->loadViewsFrom($frameworkPath, 'commentify');
        } else {
            // Fallback to tailwind if framework directory doesn't exist
            $this->loadViewsFrom(__DIR__.'/../../resources/views/tailwind', 'commentify');
        }

        // Only load Filament views if Filament is installed
        if (class_exists(Filament::class)) {
            $filamentPath = __DIR__.'/../../resources/views/filament';
            $filamentPathTailwind = __DIR__.'/../../resources/views/tailwind/filament';
            $filamentPathBootstrap = __DIR__.'/../../resources/views/bootstrap/filament';

            if (is_dir($filamentPath)) {
                $this->loadViewsFrom($filamentPath, 'commentify');
            } elseif (is_dir($filamentPathTailwind)) {
                $this->loadViewsFrom($filamentPathTailwind, 'commentify');
            } elseif (is_dir($filamentPathBootstrap)) {
                $this->loadViewsFrom($filamentPathBootstrap, 'commentify');
            }
        }

        $this->loadViewsFrom(__DIR__.'/../../resources/views/shared', 'commentify');
        $this->loadTranslationsFrom(__DIR__.'/../../lang', 'commentify');
        Event::listen(CommentPosted::class, SendCommentPostedNotification::class);
        $this->registerLazyComments();
        Livewire::component('comments', Comments::class);
        Livewire::component('comment', Comment::class);
        Livewire::component('like', Like::class);
    }

    /**
     * Livewire only lazy-loads when the tag passes `lazy` or the class has #[Lazy].
     * The config flag applies the same mount path unless a tag sets `lazy` itself.
     */
    protected function registerLazyComments(): void
    {
        after('mount', function (object $component, array $params): void {
            if (! $component instanceof Comments) {
                return;
            }

            if (array_key_exists('lazy', $params) || ! config('commentify.lazy')) {
                return;
            }

            ComponentHookRegistry::getHook($component, SupportLazyLoading::class)
                ?->mount(['lazy' => true] + $params);
        });
    }
}
