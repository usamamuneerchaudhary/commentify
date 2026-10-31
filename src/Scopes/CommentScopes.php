<?php

namespace Usamamuneerchaudhary\Commentify\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait CommentScopes
{
    protected static ?bool $pinnedAtColumn = null;

    protected static ?bool $threadColumn = null;

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

    protected static function hasPinnedAtColumn(): bool
    {
        if (static::$pinnedAtColumn === null) {
            static::$pinnedAtColumn = Schema::hasColumn((new static)->getTable(), 'pinned_at');
        }

        return static::$pinnedAtColumn;
    }
}
