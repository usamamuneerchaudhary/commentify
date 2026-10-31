<?php

namespace Usamamuneerchaudhary\Commentify\Providers;

use Illuminate\Support\ServiceProvider;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownServiceProvider extends ServiceProvider
{
    /**
     * Register the CommonMark converter that renders comment bodies and strips raw HTML.
     */
    public function register(): void
    {
        $this->app->singleton('markdown', function () {
            $environment = new Environment([
                'allow_unsafe_links' => false,
                'html_input' => 'strip',
            ]);

            $environment->addExtension(new CommonMarkCoreExtension);
            $environment->addExtension(new StrikethroughExtension);
            $environment->addExtension(new AutolinkExtension);
            $environment->addExtension(new TaskListExtension);

            return new MarkdownConverter($environment);
        });
    }

    /**
     * Markdown is configured during registration, so there is nothing to boot.
     */
    public function boot(): void
    {
        //
    }
}
