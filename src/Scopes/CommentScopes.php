<?php

namespace Usamamuneerchaudhary\Commentify\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait CommentScopes
{
    protected static ?bool $pinnedAtColumn = null;

    protected static ?bool $threadColumn = null;

    /**
     * Limit the query to top-level comments.
     */
    public function scopeParent(Builder $builder): void
    {
        $builder->whereNull('parent_id');
    }

    /**
     * Order comments from newest to oldest.
     */
    public function scopeNewest(Builder $builder): Builder
    {
        return $builder->latest();
    }

    /**
     * Order comments from oldest to newest.
     */
    public function scopeOldest(Builder $builder): Builder
    {
        return $builder->oldest();
    }

    /**
     * Order comments by like count, highest first.
     */
    public function scopeMostLiked(Builder $builder): Builder
    {
        return $builder->orderBy('likes_count', 'desc');
    }

    /**
     * Order comments by reply count, highest first.
     */
    public function scopeMostReplied(Builder $builder): Builder
    {
        return $builder->orderBy('children_count', 'desc');
    }

    /**
     * Limit the query to comments that have been approved.
     */
    public function scopeApproved(Builder $builder): Builder
    {
        return $builder->where('is_approved', true);
    }

    /**
     * Limit the query to comments waiting for approval.
     */
    public function scopePending(Builder $builder): Builder
    {
        return $builder->where('is_approved', false);
    }

    /**
     * Pinned comments first. No-ops when the Pro `pinned_at` column is absent.
     */
    public function scopePinnedFirst(Builder $builder): Builder
    {
        if (! static::hasPinnedAtColumn()) {
            return $builder;
        }

        return $builder->orderByRaw('case when pinned_at is null then 1 else 0 end');
    }

    /**
     * Limit a thread to one named lane. No-ops when the Pro `thread` column is absent.
     */
    public function scopeForThread(Builder $builder, string $thread): Builder
    {
        if (! static::hasThreadColumn()) {
            return $builder;
        }

        return $builder->where($builder->qualifyColumn('thread'), $thread);
    }

    /**
     * Whether the comments table has the Pro thread column. The result is cached.
     */
    public static function hasThreadColumn(): bool
    {
        if (static::$threadColumn === null) {
            static::$threadColumn = Schema::hasColumn((new static)->getTable(), 'thread');
        }

        return static::$threadColumn;
    }

    /**
     * Drop cached column lookups. Pro tests migrate the comments table per test.
     */
    public static function flushColumnCache(): void
    {
        static::$pinnedAtColumn = null;
        static::$threadColumn = null;
    }

    /**
     * Whether the comments table has the pinned_at column. The result is cached.
     */
    protected static function hasPinnedAtColumn(): bool
    {
        if (static::$pinnedAtColumn === null) {
            static::$pinnedAtColumn = Schema::hasColumn((new static)->getTable(), 'pinned_at');
        }

        return static::$pinnedAtColumn;
    }
}
