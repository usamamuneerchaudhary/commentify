<?php

namespace Usamamuneerchaudhary\Commentify\Support;

use Illuminate\Database\Eloquent\Model;

class CommentThreadKey
{
    /**
     * Livewire key for one commentable record, so a new record mounts a new thread.
     */
    public static function for(Model $model): string
    {
        return 'commentify-'.$model->getMorphClass().'-'.$model->getKey();
    }
}
