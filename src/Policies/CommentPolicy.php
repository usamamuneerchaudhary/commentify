<?php

namespace Usamamuneerchaudhary\Commentify\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Usamamuneerchaudhary\Commentify\Models\Comment;

class CommentPolicy
{
    use HandlesAuthorization;

    /**
     * @param  User  $user
     * @param  Comment  $comment
     * @return bool
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    /**
     * @param  User  $user
     * @param  Comment  $comment
     * @return bool
     */
    public function destroy(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }
}
