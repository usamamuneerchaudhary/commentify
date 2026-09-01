<?php

namespace Usamamuneerchaudhary\Commentify\Http\Livewire\Concerns;

use Usamamuneerchaudhary\Commentify\Models\Presenters\CommentPresenter;

trait PreviewsMarkdown
{
    public function previewMarkdown(?string $body = ''): string
    {
        return CommentPresenter::preview((string) $body);
    }
}
