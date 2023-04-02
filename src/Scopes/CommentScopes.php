<?php

namespace Usamamuneerchaudhary\Commentify\Scopes;


use Illuminate\Database\Eloquent\Builder;

trait CommentScopes
{
    public function scopeParent(Builder $builder)
    {
        $builder->whereNull('parent_id');
    }
}
