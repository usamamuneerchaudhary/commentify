<?php

namespace Usamamuneerchaudhary\Commentify\Providers;


use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comment;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Comments;
use Usamamuneerchaudhary\Commentify\Http\Livewire\Like;
use Usamamuneerchaudhary\Commentify\Policies\CommentPolicy;

class CommentifyServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(CommentPolicy::class, function ($app) {
            return new CommentPolicy;
        });

        Gate::policy(\Usamamuneerchaudhary\Commentify\Models\Comment::class, CommentPolicy::class);

        $this->app->register(MarkdownServiceProvider::class);
    }


    /**
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            // Publish config file
            $this->publishes([
                __DIR__ . '/../../config/commentify.php' => config_path('commentify.php'),
            ], 'commentify-config');

            $this->publishes([
                __DIR__ . '/../../tailwind.config.js' => base_path('tailwind.config.js'),
            ], 'commentify-tailwind-config');

            // Add this line to publish your views
            $this->publishes([
                __DIR__ . '/../../resources/views' => resource_path('views/vendor/commentify'),
            ], 'commentify-views');

            // Publish language files
            $this->publishes([
                __DIR__ . '/../../lang' => resource_path('../lang/vendor/commentify'),
            ], 'commentify-lang');

            // Publish CSS file for dark mode support
            $this->publishes([
                __DIR__ . '/../../resources/css/commentify.css' => resource_path('css/vendor/commentify.css'),
            ], 'commentify-css');
        }

        $migrationPath = realpath(__DIR__ . '/../../database/migrations');
        if ($migrationPath && is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }

        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'commentify');
        $this->loadTranslationsFrom(__DIR__ . '/../../lang', 'commentify');
        Livewire::component('comments', Comments::class);
        Livewire::component('comment', Comment::class);
        Livewire::component('like', Like::class);
    }
}
