<?php

namespace Usamamuneerchaudhary\Commentify\Scopes;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Usamamuneerchaudhary\Commentify\Models\CommentLike;
use Usamamuneerchaudhary\Commentify\Support\IntegerAuthId;

trait HasLikes
{
    /**
     * Likes cast on this comment.
     *
     * @return HasMany<CommentLike, $this>
     */
    public function likes(): HasMany
    {
        return $this->hasMany(CommentLike::class);
    }

    /**
     * Constraint for withExists() so a list can mark the viewer's like without loading every row.
     *
     * @return array<string, callable>
     */
    public static function viewerLikeExists(): array
    {
        return [
            'likes as liked' => function ($query): void {
                $userId = IntegerAuthId::get();

                if ($userId !== null) {
                    $query->where('user_id', $userId);

                    return;
                }

                $ip = request()->ip();
                $userAgent = request()->userAgent();

                if ($ip && $userAgent) {
                    $query->forIp($ip)->forUserAgent($userAgent);

                    return;
                }

                $query->whereRaw('0 = 1');
            },
        ];
    }

    /**
     * Whether the current user or guest has liked this comment.
     */
    public function isLiked(): bool
    {
        $ip = request()->ip();
        $userAgent = request()->userAgent();
        $userId = IntegerAuthId::get();

        if ($userId !== null) {
            if ($this->relationLoaded('likes')) {
                return $this->likes->contains('user_id', $userId);
            }

            return $this->likes()->where('user_id', $userId)->exists();
        }

        if ($ip && $userAgent) {
            if ($this->relationLoaded('likes')) {
                return $this->likes->filter(function ($like) use ($ip, $userAgent) {
                    return $like->ip === $ip && $like->user_agent === $userAgent;
                })->isNotEmpty();
            }

            return $this->likes()->forIp($ip)->forUserAgent($userAgent)->exists();
        }

        return false;
    }

    /**
     * Delete the current viewer like when one exists.
     */
    public function removeLike(): bool
    {
        $ip = request()->ip();
        $userAgent = request()->userAgent();
        $userId = IntegerAuthId::get();

        if ($userId !== null) {
            return (bool) $this->likes()->where('user_id', $userId)->where('comment_id', $this->id)->delete();
        }

        if ($ip && $userAgent) {
            return (bool) $this->likes()->forIp($ip)->forUserAgent($userAgent)->delete();
        }

        return false;
    }
}
