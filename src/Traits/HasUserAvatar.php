<?php

namespace Usamamuneerchaudhary\Commentify\Traits;

trait HasUserAvatar
{
    /**
     * Return the Gravatar URL for this user.
     */
    public function avatar(): string
    {
        return 'https://gravatar.com/avatar/'.md5($this->email).'?s=80&d=mp';
    }
}
