<?php

namespace Usamamuneerchaudhary\Commentify\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use Usamamuneerchaudhary\Commentify\Models\Comment;
use Usamamuneerchaudhary\Commentify\Models\User;

class CommentPolicy
{
    use HandlesAuthorization;


    public function update($user, Comment $comment): Response
    {
        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::denyWithStatus(401);
    }


    public function destroy($user, Comment $comment): Response
    {
        return $user->id === $comment->user_id
            ? Response::allow()
            : Response::denyWithStatus(401);
    }
}
