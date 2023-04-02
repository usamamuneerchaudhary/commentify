<?php

namespace Usamamuneerchaudhary\Commentify\Traits;

trait HasUserAvatar
{

    public function avatar()
    {
        return 'https://gravatar.com/avatar/'.md5($this->email).'?s=80&d=mp';
    }
}
