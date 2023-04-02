<?php

namespace Usamamuneerchaudhary\Commentify\Traits;

use Usamamuneerchaudhary\Commentify\Models\Comment;

trait Commentable
{

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

}
