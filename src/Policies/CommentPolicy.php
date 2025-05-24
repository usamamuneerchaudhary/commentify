<?php

namespace Usamamuneerchaudhary\Commentify\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class CommentPolicy
{
    use HandlesAuthorization;


    /**
     * Determine whether the user can view any comments.
     * @param $user
     * @return Response
     */
    public function create($user): Response
    {
        // Check if the user is temporarily banned from commenting
        if (method_exists($user, 'isCommentBanned') && $user->isCommentBanned()) {
            return Response::deny(__('commentify::commentify.comments.banned_message'), 403);
        }

        return Response::allow();
    }

    /**
     * @param $user
     * @param  Comment  $comment
     * @return Response
     */
    public function update($user, Comment $comment): Response
    {
        if (method_exists($user, 'isCommentBanned') && $user->isCommentBanned()) {
            return Response::deny(__('commentify::commentify.comments.banned_message'), 403);
        }
        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::denyWithStatus(401);
    }


    /**
     * @param $user
     * @param  Comment  $comment
     * @return Response
     */
    public function destroy($user, Comment $comment): Response
    {
        if (method_exists($user, 'isCommentBanned') && $user->isCommentBanned()) {
            return Response::deny(__('commentify::commentify.comments.banned_message'), 403);
        }
        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::denyWithStatus(401);
    }
}
