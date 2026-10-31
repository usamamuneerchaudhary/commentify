<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire\Concerns;

use Usamamuneerchaudhary\Commentify\Models\Presenters\CommentPresenter;

trait PreviewsMarkdown
{
    /**
     * Render a composer draft as sanitized HTML for the preview tab.
     */
    public function previewMarkdown(?string $body = ''): string
    {
        return CommentPresenter::preview((string) $body);
    }
}
