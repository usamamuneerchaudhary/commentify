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

    /**
     * @param  Builder  $builder
     * @return Builder
     */
    public function scopeNewest(Builder $builder): Builder
    {
        return $builder->latest();
    }

    /**
     * @param  Builder  $builder
     * @return Builder
     */
    public function scopeOldest(Builder $builder): Builder
    {
        return $builder->oldest();
    }

    /**
     * @param  Builder  $builder
     * @return Builder
     */
    public function scopeMostLiked(Builder $builder): Builder
    {
        return $builder->orderBy('likes_count', 'desc');
    }

    /**
     * @param  Builder  $builder
     * @return Builder
     */
    public function scopeMostReplied(Builder $builder): Builder
    {
        return $builder->orderBy('children_count', 'desc');
    }
}
