<?php

namespace Usamamuneerchaudhary\Commentify;

use Closure;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class Commentify
{
    protected static ?Closure $commentUrlResolver = null;

    public static function resolveCommentUrlUsing(Closure $resolver): void
    {
        static::$commentUrlResolver = $resolver;
    }

    public static function commentUrl(Comment $comment, ?string $fragment = null): string
    {
        if (static::$commentUrlResolver !== null) {
            $url = call_user_func(static::$commentUrlResolver, $comment);
        } else {
            $commentable = $comment->commentable;

            if ($commentable !== null && method_exists($commentable, 'commentifyUrl')) {
                $url = $commentable->commentifyUrl();
            } else {
                $url = url()->previous() ?: url('/');
            }
        }

        $fragment ??= 'comment-'.$comment->id;

        return rtrim($url, '#').'#'.ltrim($fragment, '#');
    }
}
