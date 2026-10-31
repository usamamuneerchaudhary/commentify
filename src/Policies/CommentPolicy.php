<?php

namespace Usamamuneerchaudhary\Commentify\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether this visitor may create a comment.
     */
    public function create(?Authenticatable $user = null): Response
    {
        if ($user === null && config('commentify.allow_guests', false)) {
            return Response::allow();
        }

        if ($user === null) {
            return Response::deny(__('commentify::commentify.comments.login_to_comment'), 401);
        }

        // Check if the user is temporarily banned from commenting
        if (method_exists($user, 'isCommentBanned') && $user->isCommentBanned()) {
            return Response::deny(__('commentify::commentify.comments.banned_message'), 403);
        }

        return Response::allow();
    }

    /**
     * Allow the author to edit the comment, and deny guests and banned users.
     *
     * @param  Authenticatable  $user
     */
    public function update($user, Comment $comment): Response
    {
        if ($comment->isGuest()) {
            return Response::denyWithStatus(401);
        }
        if (method_exists($user, 'isCommentBanned') && $user->isCommentBanned()) {
            return Response::deny(__('commentify::commentify.comments.banned_message'), 403);
        }

        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::denyWithStatus(401);
    }

    /**
     * Allow the author to delete the comment, and deny guests and banned users.
     *
     * @param  Authenticatable  $user
     */
    public function destroy($user, Comment $comment): Response
    {
        if ($comment->isGuest()) {
            return Response::denyWithStatus(401);
        }
        if (method_exists($user, 'isCommentBanned') && $user->isCommentBanned()) {
            return Response::deny(__('commentify::commentify.comments.banned_message'), 403);
        }

        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::denyWithStatus(401);
    }
}
