<?php

namespace Usamamuneerchaudhary\Commentify\Scopes;


use Illuminate\Database\Eloquent\Builder;

trait CommentScopes
{
    /**
     * @param  Builder  $builder
     * @return void
     */
    public function scopeParent(Builder $builder): void
    {
        $builder->whereNull('parent_id');
    }
}
