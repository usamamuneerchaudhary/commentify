<?php

namespace Usamamuneerchaudhary\Commentify\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class CommentPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Comment $comment)
    {
        return $user->id === $comment->user_id;
    }

    public function destroy(User $user, Comment $comment)
    {
        return $user->id === $comment->user_id;
    }
}
