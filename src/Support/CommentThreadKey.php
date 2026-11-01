<?php

namespace Usamamuneerchaudhary\Commentify\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CommentThreadKey
{
    /**
     * Livewire key for one commentable record and thread name, so a new record or thread mounts a new component.
     */
    public static function for(Model $model, ?string $thread = null): string
    {
        $key = 'commentify-'.$model->getMorphClass().'-'.$model->getKey();

        if (filled($thread)) {
            $slug = Str::slug($thread);

            if ($slug !== '') {
                $key .= '-'.$slug;
            }
        }

        return $key;
    }
}
