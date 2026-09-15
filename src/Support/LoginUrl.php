<?php

namespace Usamamuneerchaudhary\Commentify\Support;

use Symfony\Component\Routing\Exception\RouteNotFoundException;

final class LoginUrl
{
    public static function url(): ?string
    {
        $name = config('commentify.login_route', 'login');

        if (! is_string($name) || $name === '') {
            return null;
        }

        try {
            return route($name, ['redirect' => request()->url()]);
        } catch (RouteNotFoundException) {
            return null;
        }
    }
}
