<?php

namespace Usamamuneerchaudhary\Commentify\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Usamamuneerchaudhary\Commentify\Models\Comment;

trait Commentable
{
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
