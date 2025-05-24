<?php

namespace Usamamuneerchaudhary\Commentify\Traits;

use Carbon\Carbon;

trait HasCommentBan
{
    /**
     * Check if the user is banned from commenting.
     *
     * @return bool
     */
    public function isCommentBanned(): bool
    {
        if (!isset($this->comment_banned_until)) {
            return false;
        }
        return $this->comment_banned_until && Carbon::parse($this->comment_banned_until)->isFuture();
    }
}
