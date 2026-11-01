<?php

namespace Usamamuneerchaudhary\Commentify\Filament;

use Filament\Schemas\Components\View;

class CommentsEntry
{
    /**
     * Filament view that mounts the thread for the record open in the form or modal.
     */
    public static function make(): View
    {
        return View::make('commentify::filament.thread')
            ->hiddenOn(['create']);
    }
}
