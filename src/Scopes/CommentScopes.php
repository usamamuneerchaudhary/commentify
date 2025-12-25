<?php

namespace Usamamuneerchaudhary\Commentify\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait CommentScopes
{
    public function scopeParent(Builder $builder): void
    {
        $builder->whereNull('parent_id');
    }

    public function scopeNewest(Builder $builder): Builder
    {
        return $builder->latest();
    }

    public function scopeOldest(Builder $builder): Builder
    {
        return $builder->oldest();
    }

    public function scopeMostLiked(Builder $builder): Builder
    {
        return $builder->orderBy('likes_count', 'desc');
    }

    public function scopeMostReplied(Builder $builder): Builder
    {
        return $builder->orderBy('children_count', 'desc');
    }

    public function scopeApproved(Builder $builder): Builder
    {
        return $builder->where('is_approved', true);
    }

    public function scopePending(Builder $builder): Builder
    {
        return $builder->where('is_approved', false);
    }
}
